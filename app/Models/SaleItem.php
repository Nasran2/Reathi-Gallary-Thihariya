<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaleItem extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['quantity' => 'decimal:6', 'conversion_rate' => 'decimal:8', 'base_quantity' => 'decimal:6', 'unit_price' => 'decimal:4', 'discount_amount' => 'decimal:4', 'tax_amount' => 'decimal:4', 'net_revenue' => 'decimal:4', 'cost_at_sale' => 'decimal:8', 'cost_total' => 'decimal:4', 'profit' => 'decimal:4'];
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function remnant()
    {
        return $this->belongsTo(Remnant::class);
    }
}
