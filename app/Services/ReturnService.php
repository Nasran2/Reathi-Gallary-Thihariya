<?php

namespace App\Services;

use App\Models\CustomerPaymentAllocation;
use App\Models\Purchase;
use App\Models\PurchaseReturn;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Models\SupplierPaymentAllocation;
use App\Support\Decimal;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ReturnService
{
    public function __construct(private InventoryService $inventory, private LedgerService $ledger) {}

    public function sale(array $data, ?int $userId = null): SaleReturn
    {
        return DB::transaction(function () use ($data, $userId) {
            $sale = Sale::with('items.returnItems')->lockForUpdate()->findOrFail($data['sale_id']);
            $return = SaleReturn::create(['uuid' => Str::uuid(), 'return_no' => 'SRT-'.now()->format('ymdHis').'-'.random_int(10, 99), 'sale_id' => $sale->id, 'customer_id' => $sale->customer_id, 'return_date' => $data['return_date'], 'return_total' => 0, 'cost_total' => 0, 'settlement' => $data['settlement'] ?? 'customer_credit', 'reason' => $data['reason'], 'notes' => $data['notes'] ?? null, 'created_by' => $userId]);
            $total = BigDecimal::zero();
            $cost = BigDecimal::zero();
            $count = 0;
            foreach ($sale->items as $item) {
                $qty = Decimal::of($data['items'][$item->id] ?? 0);
                if ($qty->isLessThanOrEqualTo(0)) {
                    continue;
                }
                $available = Decimal::of($item->quantity)->minus(Decimal::of($item->returnItems->sum('quantity')));
                if ($qty->isGreaterThan($available)) {
                    throw ValidationException::withMessages(['items' => "Return quantity exceeds the remaining sold quantity for {$item->product->name}."]);
                }
                $baseQty = $qty->multipliedBy(Decimal::of($item->conversion_rate))->toScale(6, RoundingMode::HalfUp);
                $amount = Decimal::of($item->net_revenue)->multipliedBy($qty)->dividedBy(Decimal::of($item->quantity), 4, RoundingMode::HalfUp);
                $costAmount = $baseQty->multipliedBy(Decimal::of($item->cost_at_sale))->toScale(4, RoundingMode::HalfUp);
                $line = $return->items()->create(['sale_item_id' => $item->id, 'product_id' => $item->product_id, 'unit_id' => $item->unit_id, 'quantity' => $qty, 'base_quantity' => $baseQty, 'return_amount' => $amount, 'cost_amount' => $costAmount]);
                $this->inventory->move($item->product, $sale->store_id, $sale->sale_type, 'sales_return', $baseQty, 0, $item->cost_at_sale, $line, $userId, $return->return_no);
                if ($item->remnant) {
                    $remnant = $item->remnant()->lockForUpdate()->first();
                    $remainingBase = Decimal::add($remnant->remaining_base_quantity, $baseQty, 6);
                    $remnant->update(['remaining_base_quantity' => $remainingBase, 'remaining_quantity' => Decimal::div($remainingBase, $remnant->conversion_rate, 6), 'status' => 'available']);
                }
                $total = $total->plus($amount);
                $cost = $cost->plus($costAmount);
                $count++;
            }
            if ($count === 0) {
                throw ValidationException::withMessages(['items' => 'Select at least one item and quantity to return.']);
            }
            $return->update(['return_total' => Decimal::money($total), 'cost_total' => Decimal::money($cost)]);
            $creditRemaining = $total;
            $dueReduction = Decimal::of($sale->due_total)->isLessThan($creditRemaining) ? Decimal::of($sale->due_total) : $creditRemaining;
            $remainingDue = Decimal::of($sale->due_total)->minus($dueReduction);
            $creditRemaining = $creditRemaining->minus($dueReduction);
            $remainingPending = Decimal::of($sale->pending_total);
            if ($creditRemaining->isGreaterThan(0)) {
                foreach (CustomerPaymentAllocation::where('sale_id', $sale->id)->where('status', 'pending')->lockForUpdate()->get() as $allocation) {
                    $reduction = Decimal::of($allocation->amount)->isLessThan($creditRemaining) ? Decimal::of($allocation->amount) : $creditRemaining;
                    $allocation->update(['amount' => Decimal::money(Decimal::of($allocation->amount)->minus($reduction))]);
                    $remainingPending = $remainingPending->minus($reduction);
                    $creditRemaining = $creditRemaining->minus($reduction);
                    if ($creditRemaining->isZero()) {
                        break;
                    }
                }
            }
            $sale->update(['returned_total' => Decimal::add($sale->returned_total, $total, 4), 'due_total' => Decimal::money($remainingDue), 'pending_total' => Decimal::money($remainingPending)]);
            if ($sale->customer) {
                $this->ledger->customer($sale->customer, 'sales_return', 0, $total, $return, $return->return_no, 0, $data['return_date']);
                if (($data['settlement'] ?? 'customer_credit') === 'cash_refund') {
                    $this->ledger->customer($sale->customer, 'return_refund', $total, 0, $return, 'Cash refund '.$return->return_no, 0, $data['return_date']);
                }
            }

            return $return->load('sale', 'customer', 'items.product', 'items.unit');
        }, 3);
    }

    public function manualSale(array $data, SaleService $saleService, ?int $userId = null): SaleReturn
    {
        return DB::transaction(function () use ($data, $userId, $saleService) {
            $customer = !empty($data['customer_id']) ? \App\Models\Customer::find($data['customer_id']) : null;
            
            $return = SaleReturn::create([
                'uuid' => Str::uuid(), 
                'return_no' => 'MSR-'.now()->format('ymdHis').'-'.random_int(10, 99), 
                'sale_id' => null, 
                'is_manual' => true,
                'customer_id' => $customer?->id, 
                'return_date' => $data['return_date'], 
                'return_total' => 0, 
                'cost_total' => 0, 
                'settlement' => $data['settlement'], 
                'reason' => $data['reason'], 
                'notes' => $data['notes'] ?? null, 
                'created_by' => $userId
            ]);
            
            $total = BigDecimal::zero();
            $cost = BigDecimal::zero();
            
            foreach ($data['items'] as $itemData) {
                $qty = Decimal::of($itemData['quantity']);
                $price = Decimal::of($itemData['price']);
                if ($qty->isLessThanOrEqualTo(0)) {
                    continue;
                }
                
                $product = \App\Models\Product::find($itemData['product_id']);
                $unit = \App\Models\Unit::find($itemData['unit_id']);
                
                // Assuming default store is 1 for manual returns
                $store_id = 1; 
                
                // Fetch conversion rate
                $productUnit = \App\Models\ProductUnit::where('product_id', $product->id)->where('unit_id', $unit->id)->first();
                $conversionRate = $productUnit ? $productUnit->conversion_rate : 1;
                
                $baseQty = $qty->multipliedBy(Decimal::of($conversionRate))->toScale(6, RoundingMode::HalfUp);
                $amount = $qty->multipliedBy($price)->toScale(4, RoundingMode::HalfUp);
                $costAmount = $baseQty->multipliedBy(Decimal::of($product->average_cost))->toScale(4, RoundingMode::HalfUp);
                
                $line = $return->items()->create([
                    'sale_item_id' => null, 
                    'product_id' => $product->id, 
                    'unit_id' => $unit->id, 
                    'quantity' => $qty, 
                    'base_quantity' => $baseQty, 
                    'return_amount' => $amount, 
                    'cost_amount' => $costAmount
                ]);
                
                $this->inventory->move($product, $store_id, 'main', 'sales_return', $baseQty, 0, $product->average_cost, $line, $userId, $return->return_no);
                
                $total = $total->plus($amount);
                $cost = $cost->plus($costAmount);
            }
            
            

            if ($customer) {
                $this->ledger->customer($customer, 'sales_return', 0, $total, $return, $return->return_no, 0, $data['return_date']);
                
                if ($data['settlement'] === 'cash_refund') {
                    $this->ledger->customer($customer, 'return_refund', $total, 0, $return, 'Cash refund (Manual) '.$return->return_no, 0, $data['return_date']);
                }
            }
            
            $refundAmount = 0;
            if ($data['settlement'] === 'cash_refund') {
                $refundAmount = (float) Decimal::money($total);
            }
            
            // Exchange logic
            if ($data['settlement'] === 'exchange' && !empty($data['exchange_items'])) {
                $exchangeTotal = collect($data['exchange_items'])->sum(function($ex) {
                    return $ex['quantity'] * $ex['price'];
                });
                
                $paymentMethod = \App\Models\PaymentMethod::where('code', 'cash')->first();
                
                // Create a new Sale for the exchange
                $saleData = [
                    'customer_id' => $customer?->id,
                    'store_id' => 1,
                    'sale_type' => 'main',
                    'notes' => 'Exchange from manual return: ' . $return->return_no,
                    'global_discount_type' => 'fixed',
                    'global_discount_value' => 0,
                    'items' => collect($data['exchange_items'])->map(function($ex) {
                        return [
                            'product_id' => $ex['product_id'],
                            'unit_id' => $ex['unit_id'],
                            'quantity' => $ex['quantity'],
                            'unit_price' => $ex['price'],
                            'discount_type' => 'fixed',
                            'discount_value' => 0,
                            'remnant_id' => null,
                        ];
                    })->toArray(),
                    'payments' => []
                ];
                
                $exchangeSale = $saleService->create($saleData, 'EXC-'.$return->return_no, $userId);
                
                if ($exchangeTotal > (float) Decimal::money($total)) {
                    // Customer pays the difference
                    $diff = $exchangeTotal - (float) Decimal::money($total);
                    $exchangeSale->payments()->create([
                        'payment_method_id' => $paymentMethod?->id,
                        'amount' => $diff,
                        'payment_date' => $data['return_date'],
                        'status' => 'completed',
                        'note' => 'Paid difference for exchange'
                    ]);
                    $exchangeSale->update(['due_total' => 0, 'paid_total' => $exchangeSale->paid_total + $diff]);
                    
                    if ($customer) {
                        $this->ledger->customer($customer, 'payment', 0, $diff, $exchangeSale, 'Paid difference for exchange EXC-'.$return->return_no, 0, $data['return_date']);
                    }
                } elseif ($exchangeTotal < (float) Decimal::money($total)) {
                    // Store refunds the difference
                    $diff = (float) Decimal::money($total) - $exchangeTotal;
                    $refundAmount = $diff;
                    if ($customer) {
                        $this->ledger->customer($customer, 'return_refund', $diff, 0, $return, 'Cash refund (Exchange difference) '.$return->return_no, 0, $data['return_date']);
                    }
                }
            }

            $return->update([
                'return_total' => Decimal::money($total), 
                'cost_total' => Decimal::money($cost),
                'refund_amount' => $refundAmount
            ]);

            return $return;
        }, 3);
    }

    public function purchase(array $data, ?int $userId = null): PurchaseReturn
    {
        return DB::transaction(function () use ($data, $userId) {
            $purchase = Purchase::with('items.returnItems')->lockForUpdate()->findOrFail($data['purchase_id']);
            $return = PurchaseReturn::create(['uuid' => Str::uuid(), 'return_no' => 'PRT-'.now()->format('ymdHis').'-'.random_int(10, 99), 'purchase_id' => $purchase->id, 'supplier_id' => $purchase->supplier_id, 'return_date' => $data['return_date'], 'return_total' => 0, 'reason' => $data['reason'], 'notes' => $data['notes'] ?? null, 'created_by' => $userId]);
            $total = BigDecimal::zero();
            $count = 0;
            foreach ($purchase->items as $item) {
                $qty = Decimal::of($data['items'][$item->id] ?? 0);
                if ($qty->isLessThanOrEqualTo(0)) {
                    continue;
                }
                $available = Decimal::of($item->quantity)->minus(Decimal::of($item->returnItems->sum('quantity')));
                if ($qty->isGreaterThan($available)) {
                    throw ValidationException::withMessages(['items' => "Return quantity exceeds the remaining purchased quantity for {$item->product->name}."]);
                }
                $baseQty = $qty->multipliedBy(Decimal::of($item->conversion_rate))->toScale(6, RoundingMode::HalfUp);
                $amount = Decimal::of($item->supplier_line_total)->multipliedBy($qty)->dividedBy(Decimal::of($item->quantity), 4, RoundingMode::HalfUp);
                $line = $return->items()->create(['purchase_item_id' => $item->id, 'product_id' => $item->product_id, 'unit_id' => $item->unit_id, 'quantity' => $qty, 'base_quantity' => $baseQty, 'return_amount' => $amount]);
                $this->inventory->move($item->product, $purchase->store_id, 'main', 'purchase_return', 0, $baseQty, $item->landed_unit_cost, $line, $userId, $return->return_no);
                $total = $total->plus($amount);
                $count++;
            }
            if ($count === 0) {
                throw ValidationException::withMessages(['items' => 'Select at least one item and quantity to return.']);
            }
            $return->update(['return_total' => Decimal::money($total)]);
            $creditRemaining = $total;
            $dueReduction = Decimal::of($purchase->due_total)->isLessThan($creditRemaining) ? Decimal::of($purchase->due_total) : $creditRemaining;
            $remainingDue = Decimal::of($purchase->due_total)->minus($dueReduction);
            $creditRemaining = $creditRemaining->minus($dueReduction);
            $remainingPending = Decimal::of($purchase->pending_total);
            if ($creditRemaining->isGreaterThan(0)) {
                foreach (SupplierPaymentAllocation::where('purchase_id', $purchase->id)->where('status', 'pending')->lockForUpdate()->get() as $allocation) {
                    $reduction = Decimal::of($allocation->amount)->isLessThan($creditRemaining) ? Decimal::of($allocation->amount) : $creditRemaining;
                    $allocation->update(['amount' => Decimal::money(Decimal::of($allocation->amount)->minus($reduction))]);
                    $remainingPending = $remainingPending->minus($reduction);
                    $creditRemaining = $creditRemaining->minus($reduction);
                    if ($creditRemaining->isZero()) {
                        break;
                    }
                }
            }
            $purchase->update(['returned_total' => Decimal::add($purchase->returned_total, $total, 4), 'due_total' => Decimal::money($remainingDue), 'pending_total' => Decimal::money($remainingPending)]);
            $this->ledger->supplier($purchase->supplier, 'purchase_return', $total, 0, $return, $return->return_no, 0, $data['return_date']);

            return $return->load('purchase', 'supplier', 'items.product', 'items.unit');
        }, 3);
    }
}
