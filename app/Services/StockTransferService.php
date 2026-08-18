<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StockTransfer;
use App\Support\Decimal;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class StockTransferService
{
    public function __construct(private InventoryService $inventory, private RemnantService $remnants) {}

    public function transfer(array $data, ?int $userId = null): StockTransfer
    {
        return DB::transaction(function () use ($data, $userId) {
            $sameStore = (int) $data['from_store_id'] === (int) $data['to_store_id'];
            $mainToRemnant = $sameStore && $data['from_type'] === 'main' && $data['to_type'] === 'remnant';
            $storeToStore = ! $sameStore && $data['from_type'] === 'main' && $data['to_type'] === 'main';
            if (! $mainToRemnant && ! $storeToStore) {
                throw ValidationException::withMessages(['to_store_id' => 'Use Main → Remnant within one store, or Main → Main between different stores.']);
            }
            $product = Product::lockForUpdate()->findOrFail($data['product_id']);
            $unit = $product->productUnits()->where('unit_id', $data['unit_id'])->first();
            if (! $unit) {
                throw ValidationException::withMessages(['unit_id' => 'The selected unit is not configured for this product.']);
            }
            $base = Decimal::mul($data['quantity'], $unit->conversion_rate, 6);
            $transfer = StockTransfer::create(['uuid' => Str::uuid(), 'transfer_no' => 'TRF-'.now()->format('ymdHis').'-'.random_int(10, 99), 'product_id' => $product->id, 'unit_id' => $data['unit_id'], 'from_store_id' => $data['from_store_id'], 'to_store_id' => $data['to_store_id'], 'from_type' => $data['from_type'], 'to_type' => $data['to_type'], 'quantity' => $data['quantity'], 'base_quantity' => $base, 'transfer_date' => $data['transfer_date'], 'reason' => $data['reason'], 'notes' => $data['notes'] ?? null, 'created_by' => $userId]);
            if ($mainToRemnant) {
                $this->remnants->transfer(['product_id' => $product->id, 'store_id' => $data['from_store_id'], 'unit_id' => $data['unit_id'], 'quantity' => $data['quantity'], 'remnant_price' => $product->remnant_selling_price, 'discount_percent' => 0, 'reason' => $data['reason'], 'notes' => trim(($data['notes'] ?? '')."\n{$transfer->transfer_no}")], $userId);
            } else {
                $this->inventory->move($product, $data['from_store_id'], $data['from_type'], 'stock_transfer_out', 0, $base, $product->average_cost, $transfer, $userId, $data['reason']);
                $this->inventory->move($product, $data['to_store_id'], $data['to_type'], 'stock_transfer_in', $base, 0, $product->average_cost, $transfer, $userId, $data['reason']);
            }

            return $transfer->load('product', 'unit', 'fromStore', 'toStore', 'user');
        }, 3);
    }
}
