<?php

namespace App\Services;

use App\Models\BusinessSetting;
use App\Models\Customer;
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
                'subtotal' => 0, 'discount_total' => 0, 'tax_total' => 0, 'grand_total' => 0, 'paid_total' => 0, 'due_total' => 0, 'cost_total' => 0, 'profit_total' => 0,
                'notes' => trim(($data['notes'] ?? '') . "\n" . ($data['staff_note'] ?? '')), 'sold_at' => now(),
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
                $price = Decimal::of($line['unit_price'] ?? ($type === 'main' ? $pu->main_selling_price : $remnant->remnant_price));
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
            foreach ($data['payments'] ?? [] as $payment) {
                $method = PaymentMethod::findOrFail($payment['payment_method_id']);
                $amount = Decimal::of($payment['amount']);
                if ($amount->isLessThanOrEqualTo(0)) {
                    continue;
                }
                if ($method->requires_reference && empty($payment['reference'])) {
                    throw ValidationException::withMessages(['payments' => 'A payment reference is required.']);
                }
                $feeAmount = BigDecimal::zero();
                $feePercent = Decimal::of($method->bank_charge_percentage ?? 0);
                if ($feePercent->isGreaterThan(0)) {
                    $feeAmount = $amount->multipliedBy($feePercent)->dividedBy(Decimal::of(100), 4, RoundingMode::HalfUp);
                    $profit = $profit->minus($feeAmount);
                    
                    // Save the fee as an expense
                    $expenseCategory = \App\Models\Category::firstOrCreate(
                        ['name' => 'Bank Charges', 'type' => 'expense'],
                        ['slug' => 'bank-charges', 'active' => true]
                    );
                    
                    \App\Models\Expense::create([
                        'uuid' => Str::uuid(),
                        'expense_date' => now(),
                        'category_id' => $expenseCategory->id,
                        'amount' => $feeAmount,
                        'reference_no' => $sale->invoice_no,
                        'note' => $method->name . ' Fee for ' . $sale->invoice_no,
                        'created_by' => $userId,
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
            if ($due->isGreaterThan(0)) {
                $this->ledger->customer(Customer::findOrFail($data['customer_id']), 'invoice', $due, 0, $sale, $sale->invoice_no);
            }
            PublicInvoiceToken::create(['sale_id' => $sale->id, 'token' => Str::random(64), 'expires_at' => $this->invoiceExpiry()]);

            return $sale->load('items.product', 'items.unit', 'payments.method', 'customer', 'publicToken');
        }, 3);
    }

    private function invoiceExpiry(): ?CarbonInterface
    {
        return match (BusinessSetting::read('invoice_link_expiry', 'never')) {
            '30_days' => now()->addDays(30),'90_days' => now()->addDays(90),'1_year' => now()->addYear(),default => null
        };
    }
}
