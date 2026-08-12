<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Remnant extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['original_quantity' => 'decimal:6', 'remaining_quantity' => 'decimal:6', 'conversion_rate' => 'decimal:8', 'original_base_quantity' => 'decimal:6', 'remaining_base_quantity' => 'decimal:6', 'cost_per_base_unit' => 'decimal:8', 'normal_price' => 'decimal:4', 'remnant_price' => 'decimal:4'];
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
