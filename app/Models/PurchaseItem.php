<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseItem extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['quantity' => 'decimal:6', 'conversion_rate' => 'decimal:8', 'base_quantity' => 'decimal:6', 'supplier_unit_cost' => 'decimal:8', 'supplier_line_total' => 'decimal:4', 'allocated_extra_cost' => 'decimal:4', 'landed_unit_cost' => 'decimal:8', 'previous_average_cost' => 'decimal:8', 'new_average_cost' => 'decimal:8'];
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }

    public function returnItems()
    {
        return $this->hasMany(PurchaseReturnItem::class);
    }

    public function getReturnedQuantityAttribute(): string
    {
        return (string) $this->returnItems()->sum('quantity');
    }
}
