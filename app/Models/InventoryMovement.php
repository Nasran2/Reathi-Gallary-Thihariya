<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryMovement extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['quantity_in' => 'decimal:6', 'quantity_out' => 'decimal:6', 'balance_after' => 'decimal:6', 'unit_cost' => 'decimal:8'];
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function reference()
    {
        return $this->morphTo();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }
}
