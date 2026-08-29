<?php

namespace App\Services;

use App\Models\BusinessSetting;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\PublicInvoiceToken;
use App\Models\Remnant;
use App\Models\Sale;
use App\Support\Decimal;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SaleService
{
    public function __construct(private InventoryService $inventory, private LedgerService $ledger) {}

    public function checkout(array $data, ?int $userId = null): Sale
    {
        if ($existing = Sale::where('idempotency_key', $data['idempotency_key'])->first()) {
            return $existing->load('items.product', 'payments.method', 'publicToken');
        }

        return DB::transaction(function () use ($data, $userId) {
            if ($existing = Sale::where('idempotency_key', $data['idempotency_key'])->lockForUpdate()->first()) {
                return $existing;
            }
            $type = $data['sale_type'];
            if (! in_array($type, ['main', 'remnant'], true)) {
                throw ValidationException::withMessages(['sale_type' => 'Invalid POS type.']);
            }
            $sale = Sale::create([
                'uuid' => Str::uuid(), 'invoice_no' => ($type === 'remnant' ? 'RPOS' : 'POS').'-'.now()->format('ymdHis').'-'.random_int(10, 99),
                'idempotency_key' => $data['idempotency_key'], 'sale_type' => $type, 'store_id' => $data['store_id'],
                'customer_id' => $data['customer_id'] ?? null, 'user_id' => $userId, 'status' => 'completed',
                'subtotal' => 0, 'discount_total' => 0, 'tax_total' => 0, 'grand_total' => 0, 'paid_total' => 0, 'pending_total' => 0, 'due_total' => 0, 'returned_total' => 0, 'cost_total' => 0, 'profit_total' => 0,
                'notes' => trim(($data['notes'] ?? '')."\n".($data['staff_note'] ?? '')), 'sold_at' => now(),
            ]);
            $subtotal = BigDecimal::zero();
            $discount = BigDecimal::zero();
            $tax = BigDecimal::zero();
            $costTotal = BigDecimal::zero();
            $profit = BigDecimal::zero();
            foreach ($data['items'] as $line) {
                $product = Product::with('baseUnit')->lockForUpdate()->findOrFail($line['product_id']);
                $pu = $product->productUnits()->where('unit_id', $line['unit_id'])->firstOrFail();
                $qty = Decimal::of($line['quantity']);
                if ($qty->isLessThanOrEqualTo(0)) {
                    throw ValidationException::withMessages(['items' => 'Quantity must be greater than zero.']);
                }
                $baseQty = $qty->multipliedBy(Decimal::of($pu->conversion_rate))->toScale(6, RoundingMode::HalfUp);
                $remnant = null;
                if ($type === 'remnant') {
                    $remnant = Remnant::lockForUpdate()->findOrFail($line['remnant_id'] ?? 0);
                    if ($remnant->product_id !== $product->id || $remnant->store_id !== (int) $data['store_id']) {
                        throw ValidationException::withMessages(['items' => 'Remnant does not match this product/store.']);
                    }
                    if (Decimal::of($remnant->remaining_base_quantity)->isLessThan($baseQty)) {
                        throw ValidationException::withMessages(['items' => "Only {$remnant->remaining_quantity} {$remnant->unit?->symbol} remains in {$remnant->remnant_no}."]);
                    }
                    $partial = filter_var(BusinessSetting::read('remnant_partial_sale', true), FILTER_VALIDATE_BOOL);
                    if (! $partial && ! $baseQty->isEqualTo(Decimal::of($remnant->remaining_base_quantity))) {
                        throw ValidationException::withMessages(['items' => 'This remnant must be sold as a whole piece.']);
                    }
                }
                $price = Decimal::of($line['unit_price'] ?? ($type === 'main' ? ($product->main_selling_price * $pu->conversion_rate) : $remnant->remnant_price));
                $lineGross = $qty->multipliedBy($price);
                $lineDiscount = Decimal::of($line['discount_amount'] ?? 0);
                $lineTax = Decimal::of($line['tax_amount'] ?? 0);
                $net = $lineGross->minus($lineDiscount)->plus($lineTax);
                $costAtSale = Decimal::of($type === 'main' ? $product->average_cost : $remnant->cost_per_base_unit);
                $lineCost = $baseQty->multipliedBy($costAtSale);
                $lineProfit = $net->minus($lineCost);
                if ($type === 'main' && $net->isLessThan($lineCost) && filter_var(BusinessSetting::read('block_main_below_cost', false), FILTER_VALIDATE_BOOL)) {
                    throw ValidationException::withMessages(['items' => "{$product->name} cannot be sold below cost."]);
                }
                $item = $sale->items()->create([
                    'product_id' => $product->id, 'unit_id' => $line['unit_id'], 'remnant_id' => $remnant?->id,
                    'quantity' => $qty, 'conversion_rate' => $pu->conversion_rate, 'base_quantity' => $baseQty, 'unit_price' => $price,
                    'discount_amount' => $lineDiscount, 'tax_amount' => $lineTax, 'net_revenue' => $net, 'cost_at_sale' => $costAtSale,
                    'cost_total' => $lineCost, 'profit' => $lineProfit, 'notes' => $line['notes'] ?? null,
                ]);
                $this->inventory->move($product, $data['store_id'], $type, 'sale', 0, $baseQty, $costAtSale, $item, $userId, $sale->invoice_no);
                if ($remnant) {
                    $remaining = Decimal::of($remnant->remaining_base_quantity)->minus($baseQty)->toScale(6, RoundingMode::HalfUp);
                    $remainingDisplay = Decimal::of($remaining)->dividedBy(Decimal::of($remnant->conversion_rate), 6, RoundingMode::HalfUp);
                    $remnant->update(['remaining_base_quantity' => $remaining, 'remaining_quantity' => $remainingDisplay, 'status' => $remaining->isZero() ? 'sold' : 'partially_sold']);
                }
                $subtotal = $subtotal->plus($lineGross);
                $discount = $discount->plus($lineDiscount);
                $tax = $tax->plus($lineTax);
                $costTotal = $costTotal->plus($lineCost);
                $profit = $profit->plus($lineProfit);
            }
            $grand = $subtotal->minus($discount)->plus($tax);
            $paid = BigDecimal::zero();
            $chequePayments = [];
            foreach ($data['payments'] ?? [] as $payment) {
                $method = PaymentMethod::findOrFail($payment['payment_method_id']);
                $amount = Decimal::of($payment['amount']);
                if ($amount->isLessThanOrEqualTo(0)) {
                    continue;
                }
                if ($method->requires_reference && empty($payment['reference']) && !in_array($method->code, ['cheque', 'own_cheque', 'endorsed_cheque'], true)) {
                    throw ValidationException::withMessages(['payments' => 'A payment reference is required.']);
                }
                if (in_array($method->code, ['cheque', 'own_cheque', 'endorsed_cheque'], true)) {
                    if (empty($data['customer_id'])) {
                        throw ValidationException::withMessages(['customer_id' => 'Select a customer for cheque payments.']);
                    }
                    if (empty($payment['cheque_number']) || empty($payment['bank']) || empty($payment['cheque_date'])) {
                        throw ValidationException::withMessages(['payments' => 'Cheque payments require cheque number, bank, and date.']);
                    }
                    $chequePayments[] = $payment;
                    continue;
                }
                $feeAmount = BigDecimal::zero();
                $feePercent = Decimal::of($method->bank_charge_percentage ?? 0);
                if ($feePercent->isGreaterThan(0)) {
                    $feeAmount = $amount->multipliedBy($feePercent)->dividedBy(Decimal::of(100), 4, RoundingMode::HalfUp);
                    $profit = $profit->minus($feeAmount);

                    // Save the fee as an expense
                    $expenseCategory = ExpenseCategory::firstOrCreate(['name' => 'Bank Charges'], ['active' => true]);

                    Expense::create([
                        'expense_date' => now(),
                        'expense_category_id' => $expenseCategory->id,
                        'payment_method_id' => $method->id,
                        'store_id' => $data['store_id'],
                        'user_id' => $userId,
                        'amount' => $feeAmount,
                        'reference' => $sale->invoice_no,
                        'description' => $method->name.' fee for '.$sale->invoice_no,
                    ]);
                }

                $sale->payments()->create(['payment_method_id' => $method->id, 'amount' => $amount, 'bank_fee' => $feeAmount, 'reference' => $payment['reference'] ?? null]);
                if ($method->code !== 'credit_due') {
                    $paid = $paid->plus($amount);
                }
            }
            if ($paid->isGreaterThan($grand)) {
                $paid = $grand;
            }
            $due = $grand->minus($paid);
            if ($due->isGreaterThan(0) && empty($data['customer_id'])) {
                throw ValidationException::withMessages(['customer_id' => 'Select a customer for a credit/due sale.']);
            }
            $sale->update(['subtotal' => $subtotal, 'discount_total' => $discount, 'tax_total' => $tax, 'grand_total' => $grand, 'paid_total' => $paid, 'due_total' => $due, 'cost_total' => $costTotal, 'profit_total' => $profit]);
            if (! empty($data['customer_id'])) {
                $customer = Customer::findOrFail($data['customer_id']);
                $this->ledger->customer($customer, 'invoice', $grand, 0, $sale, $sale->invoice_no, 0, $sale->sold_at);
                foreach ($sale->payments()->with('method')->get() as $payment) {
                    if ($payment->method->code !== 'credit_due') {
                        $this->ledger->customer($customer, 'payment', 0, $payment->amount, $payment, $payment->reference ?? $payment->method->name, 0, $sale->sold_at);
                    }
                }
            }

            foreach ($chequePayments as $payment) {
                app(\App\Services\ChequeService::class)->receive([
                    'cheque_number' => $payment['cheque_number'],
                    'bank' => $payment['bank'],
                    'cheque_date' => $payment['cheque_date'],
                    'received_date' => $sale->sold_at->toDateString(),
                    'amount' => $payment['amount'],
                    'customer_id' => $data['customer_id'],
                    'allocation_mode' => 'manual',
                    'allocations' => [['sale_id' => $sale->id, 'amount' => $payment['amount']]],
                    'notes' => 'Received at POS Checkout',
                ], $userId);
            }

            PublicInvoiceToken::updateOrCreate(['sale_id' => $sale->id], ['token' => Str::random(64), 'expires_at' => $this->invoiceExpiry()]);

            return $sale->load('items.product', 'items.unit', 'payments.method', 'customer', 'publicToken');
        }, 3);
    }

    private function invoiceExpiry(): ?CarbonInterface
    {
        return match (BusinessSetting::read('invoice_link_expiry', 'never')) {
            '30_days' => now()->addDays(30),'90_days' => now()->addDays(90),'1_year' => now()->addYear(),default => null
        };
    }

    public function deleteSale(Sale $sale, ?int $userId = null): void
    {
        DB::transaction(function () use ($sale, $userId) {
            $userId = $userId ?? auth()->id();
            
            // Revert Inventory
            foreach ($sale->items as $item) {
                if ($item->product) {
                    $inventoryType = $item->remnant_id ? 'remnant' : 'main';
                    $this->inventory->move(
                        $item->product, 
                        $sale->store_id, 
                        $inventoryType, 
                        'sale_return', 
                        $item->base_quantity, 
                        0, 
                        $item->cost_at_sale, 
                        $item, 
                        $userId, 
                        'Reverted sale ' . $sale->invoice_no
                    );
                    
                    if ($inventoryType === 'remnant' && $item->remnant_id) {
                        $remnant = \App\Models\Remnant::find($item->remnant_id);
                        if ($remnant) {
                            $remaining = Decimal::of($remnant->remaining_base_quantity)
                                ->plus($item->base_quantity)
                                ->toScale(6, RoundingMode::HalfUp);
                            $remainingDisplay = Decimal::of($remaining)
                                ->dividedBy(Decimal::of($remnant->conversion_rate), 6, RoundingMode::HalfUp);
                            $status = $remaining->isEqualTo(Decimal::of($remnant->original_base_quantity)) 
                                ? 'available' 
                                : 'partially_sold';
                                
                            $remnant->update([
                                'remaining_base_quantity' => $remaining,
                                'remaining_quantity' => $remainingDisplay,
                                'status' => $status
                            ]);
                        }
                    }
                }
            }

            // Revert Customer Ledgers
            if ($sale->customer_id) {
                \App\Models\CustomerLedger::where('reference_type', 'App\Models\Sale')->where('reference_id', $sale->id)->delete();
                foreach ($sale->payments as $payment) {
                    \App\Models\CustomerLedger::where('reference_type', 'App\Models\SalePayment')->where('reference_id', $payment->id)->delete();
                }
            }

            // Revert Bank Fee Expenses
            \App\Models\Expense::where('reference', $sale->invoice_no)->delete();

            // Revert Cheque Allocations and Cheques created during this sale
            $allocations = \App\Models\CustomerPaymentAllocation::where('sale_id', $sale->id)->get();
            foreach ($allocations as $allocation) {
                $customerPayment = $allocation->payment;
                $allocation->delete();
                
                if ($customerPayment && $customerPayment->allocations()->count() === 0) {
                    $cheque = $customerPayment->cheque;
                    $customerPayment->delete();
                    if ($cheque) {
                        $cheque->delete();
                    }
                }
            }

            // Delete Records
            $sale->items()->delete();
            $sale->payments()->delete();
            $sale->publicToken()->delete();
            $sale->delete();
        }, 3);
    }
}
