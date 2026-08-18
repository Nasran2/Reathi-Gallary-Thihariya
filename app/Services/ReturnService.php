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
