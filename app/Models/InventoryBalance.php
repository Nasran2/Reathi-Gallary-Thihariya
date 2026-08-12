<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryBalance extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['quantity' => 'decimal:6'];
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
