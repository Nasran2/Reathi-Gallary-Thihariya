<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $guarded = [];

    public function saleItems()
    {
        return $this->hasMany(SaleItem::class);
    }

    protected function casts(): array
    {
        return ['average_cost' => 'decimal:8', 'main_selling_price' => 'decimal:4', 'remnant_selling_price' => 'decimal:4', 'minimum_stock' => 'decimal:6', 'reorder_level' => 'decimal:6', 'tax_rate' => 'decimal:4', 'track_rolls' => 'boolean', 'active' => 'boolean'];
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function baseUnit()
    {
        return $this->belongsTo(Unit::class, 'base_unit_id');
    }

    public function defaultSellingUnit()
    {
        return $this->belongsTo(Unit::class, 'default_selling_unit_id');
    }

    public function units()
    {
        return $this->belongsToMany(Unit::class, 'product_units')->using(ProductUnit::class)->withPivot(['id', 'conversion_rate', 'main_selling_price', 'remnant_selling_price', 'can_purchase', 'can_sell'])->withTimestamps();
    }

    public function productUnits()
    {
        return $this->hasMany(ProductUnit::class);
    }

    public function balances()
    {
        return $this->hasMany(InventoryBalance::class);
    }

    public function suppliers()
    {
        return $this->belongsToMany(Supplier::class, 'product_suppliers')->withPivot(['last_cost', 'lowest_cost', 'highest_cost', 'last_purchased_at']);
    }

    public function balanceFor(string $type, ?int $storeId = null): string
    {
        $q = $this->balances()->where('inventory_type', $type);
        if ($storeId) {
            $q->where('store_id', $storeId);
        }

        return (string) $q->sum('quantity');
    }
}
