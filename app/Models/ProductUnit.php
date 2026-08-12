<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class ProductUnit extends Pivot
{
    protected $table = 'product_units';

    public $incrementing = true;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['conversion_rate' => 'decimal:8', 'main_selling_price' => 'decimal:4', 'remnant_selling_price' => 'decimal:4', 'can_purchase' => 'boolean', 'can_sell' => 'boolean'];
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }
}
