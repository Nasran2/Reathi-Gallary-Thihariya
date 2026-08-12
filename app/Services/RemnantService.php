<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Remnant;
use App\Support\Decimal;
use Illuminate\Support\Facades\DB;

class RemnantService
{
    public function __construct(private InventoryService $inventory) {}

    public function transfer(array $data, ?int $userId = null): Remnant
    {
        return DB::transaction(function () use ($data, $userId) {
            $product = Product::with('baseUnit')->lockForUpdate()->findOrFail($data['product_id']);
            $pu = $product->productUnits()->where('unit_id', $data['unit_id'])->firstOrFail();
            $baseQty = Decimal::mul($data['quantity'], $pu->conversion_rate, 6);
            $remnant = Remnant::create([
                'remnant_no' => 'REM-'.str_pad((string) ((Remnant::max('id') ?? 0) + 1), 6, '0', STR_PAD_LEFT),
                'barcode' => 'R'.now()->format('ymdHis').random_int(10, 99), 'product_id' => $product->id, 'store_id' => $data['store_id'],
                'source_roll_id' => $data['source_roll_id'] ?? null, 'unit_id' => $data['unit_id'], 'transferred_by' => $userId,
                'original_quantity' => $data['quantity'], 'remaining_quantity' => $data['quantity'], 'conversion_rate' => $pu->conversion_rate,
                'original_base_quantity' => $baseQty, 'remaining_base_quantity' => $baseQty, 'cost_per_base_unit' => $product->average_cost,
                'normal_price' => $product->main_selling_price * $pu->conversion_rate, 'remnant_price' => $data['remnant_price'],
                'discount_percent' => $data['discount_percent'] ?? 0, 'status' => 'available', 'reason' => $data['reason'] ?? 'Cut piece', 'notes' => $data['notes'] ?? null,
            ]);
            $this->inventory->move($product, $data['store_id'], 'main', 'remnant_transfer', 0, $baseQty, $product->average_cost, $remnant, $userId, 'Transfer to remnant');
            $this->inventory->move($product, $data['store_id'], 'remnant', 'transfer_in', $baseQty, 0, $product->average_cost, $remnant, $userId, 'Transfer from main');

            return $remnant->load('product','unit');
        }, 3);
    }
}
