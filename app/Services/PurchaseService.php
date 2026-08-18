<?php

namespace App\Services;

use App\Models\InventoryBalance;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Support\Decimal;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PurchaseService
{
    public function __construct(private InventoryService $inventory, private LedgerService $ledger) {}

    public function receive(array $data, ?int $userId = null): Purchase
    {
        return DB::transaction(function () use ($data, $userId) {
            $supplierTotal = collect($data['items'])->reduce(
                fn (BigDecimal $total, array $i) => $total->plus(
                    Decimal::of($i['quantity'])->multipliedBy(Decimal::of($i['supplier_unit_cost']))
                        ->minus(Decimal::of($i['discount_amount'] ?? 0))->plus(Decimal::of($i['tax_amount'] ?? 0))
                ), BigDecimal::zero()
            );
            $extraTotal = (string) ($data['extra_cost_total'] ?? 0);
            $purchase = Purchase::create([
                'uuid' => Str::uuid(), 'purchase_no' => 'PUR-'.now()->format('ymdHis').'-'.random_int(10, 99),
                'supplier_id' => $data['supplier_id'], 'store_id' => $data['store_id'], 'created_by' => $userId,
                'supplier_invoice_no' => $data['supplier_invoice_no'] ?? null, 'reference_no' => $data['reference_no'] ?? null,
                'purchase_date' => $data['purchase_date'], 'due_date' => $data['due_date'] ?? null, 'status' => 'received',
                'supplier_total' => Decimal::money($supplierTotal), 'extra_cost_total' => Decimal::money($extraTotal),
                'paid_total' => 0, 'pending_total' => 0, 'due_total' => Decimal::money($supplierTotal), 'returned_total' => 0,
                'notes' => $data['notes'] ?? null, 'received_at' => now(),
            ]);

            foreach ($data['items'] as $line) {
                $product = Product::with('baseUnit')->lockForUpdate()->findOrFail($line['product_id']);
                $productUnit = $product->productUnits()->where('unit_id', $line['unit_id'])->firstOrFail();
                $baseQty = Decimal::mul($line['quantity'], $productUnit->conversion_rate, 6);
                $supplierLine = Decimal::of($line['quantity'])->multipliedBy(Decimal::of($line['supplier_unit_cost']))
                    ->minus(Decimal::of($line['discount_amount'] ?? 0))->plus(Decimal::of($line['tax_amount'] ?? 0));
                $allocationRatio = $supplierTotal->isZero() ? Decimal::of(0) : $supplierLine->dividedBy($supplierTotal, 12, RoundingMode::HalfUp);
                $allocated = Decimal::of($extraTotal)->multipliedBy($allocationRatio);
                $landedTotal = $supplierLine->plus($allocated);
                $landedUnit = $landedTotal->dividedBy(Decimal::of($baseQty), 8, RoundingMode::HalfUp);
                $currentQty = InventoryBalance::where(['product_id' => $product->id, 'store_id' => $data['store_id'], 'inventory_type' => 'main'])->lockForUpdate()->value('quantity') ?? 0;
                $newAverage = $this->inventory->weightedAverage($currentQty, $product->average_cost, $baseQty, $landedUnit);
                $item = $purchase->items()->create([
                    'product_id' => $product->id, 'unit_id' => $line['unit_id'], 'quantity' => $line['quantity'],
                    'conversion_rate' => $productUnit->conversion_rate, 'base_quantity' => $baseQty,
                    'supplier_unit_cost' => $line['supplier_unit_cost'], 'discount_amount' => $line['discount_amount'] ?? 0,
                    'tax_amount' => $line['tax_amount'] ?? 0, 'supplier_line_total' => $supplierLine,
                    'allocated_extra_cost' => $allocated, 'landed_unit_cost' => $landedUnit,
                    'previous_average_cost' => $product->average_cost, 'new_average_cost' => $newAverage,
                ]);
                $product->update(['average_cost' => $newAverage]);
                $this->inventory->move($product, $data['store_id'], 'main', 'purchase', $baseQty, 0, $landedUnit, $item, $userId, "Received {$purchase->purchase_no}");
                $pivot = DB::table('product_suppliers')->where(['product_id' => $product->id, 'supplier_id' => $data['supplier_id']])->first();
                DB::table('product_suppliers')->updateOrInsert(
                    ['product_id' => $product->id, 'supplier_id' => $data['supplier_id']],
                    ['last_cost' => $line['supplier_unit_cost'], 'lowest_cost' => $pivot ? min((float) $pivot->lowest_cost, (float) $line['supplier_unit_cost']) : $line['supplier_unit_cost'], 'highest_cost' => $pivot ? max((float) $pivot->highest_cost, (float) $line['supplier_unit_cost']) : $line['supplier_unit_cost'], 'last_purchased_at' => now(), 'created_at' => $pivot?->created_at ?? now(), 'updated_at' => now()]
                );
            }
            $this->ledger->supplier(Supplier::findOrFail($data['supplier_id']), 'purchase_invoice', 0, $purchase->supplier_total, $purchase, 'Invoice cost only; extra costs excluded');

            return $purchase->load('items.product', 'supplier');
        }, 3);
    }
}
