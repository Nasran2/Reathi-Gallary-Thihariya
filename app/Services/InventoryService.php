<?php

namespace App\Services;

use App\Models\BusinessSetting;
use App\Models\InventoryBalance;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Support\Decimal;
use Brick\Math\RoundingMode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class InventoryService
{
    public function move(Product $product, int $storeId, string $inventoryType, string $movementType,
        mixed $quantityIn, mixed $quantityOut, mixed $unitCost, ?Model $reference = null,
        ?int $userId = null, ?string $notes = null): InventoryMovement
    {
        if (! in_array($inventoryType, ['main', 'remnant'], true)) {
            abort(422, 'Invalid inventory type.');
        }

        InventoryBalance::firstOrCreate(
            ['product_id' => $product->id, 'store_id' => $storeId, 'inventory_type' => $inventoryType],
            ['quantity' => 0]
        );
        $balance = InventoryBalance::where('product_id', $product->id)->where('store_id', $storeId)
            ->where('inventory_type', $inventoryType)->lockForUpdate()->firstOrFail();

        $new = Decimal::sub(Decimal::add($balance->quantity, $quantityIn), $quantityOut, 6);
        $allowNegative = filter_var(BusinessSetting::read('allow_negative_stock', false), FILTER_VALIDATE_BOOL);
        if (! $allowNegative && Decimal::of($new)->isNegative()) {
            throw ValidationException::withMessages(['stock' => "Insufficient {$inventoryType} stock for {$product->name}. Available: " . number_format($balance->quantity, 3, '.', '') . " {$product->baseUnit?->symbol}."]);
        }
        $balance->update(['quantity' => $new]);

        return InventoryMovement::create([
            'uuid' => Str::uuid(), 'product_id' => $product->id, 'store_id' => $storeId, 'user_id' => $userId,
            'inventory_type' => $inventoryType, 'movement_type' => $movementType,
            'reference_type' => $reference?->getMorphClass(), 'reference_id' => $reference?->getKey(),
            'quantity_in' => Decimal::qty($quantityIn), 'quantity_out' => Decimal::qty($quantityOut),
            'balance_after' => $new, 'unit_cost' => $unitCost, 'notes' => $notes,
        ]);
    }

    public function weightedAverage(mixed $currentQty, mixed $currentAverage, mixed $incomingQty, mixed $incomingCost): string
    {
        $totalQty = Decimal::of($currentQty)->plus(Decimal::of($incomingQty));
        if ($totalQty->isZero()) {
            return '0.00000000';
        }
        $value = Decimal::of($currentQty)->multipliedBy(Decimal::of($currentAverage))
            ->plus(Decimal::of($incomingQty)->multipliedBy(Decimal::of($incomingCost)));

        return $value->dividedBy($totalQty, 8, RoundingMode::HalfUp)->__toString();
    }
}
