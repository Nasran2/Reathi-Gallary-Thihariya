<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['purchase_date' => 'date', 'due_date' => 'date', 'received_at' => 'datetime', 'supplier_total' => 'decimal:4', 'extra_cost_total' => 'decimal:4', 'paid_total' => 'decimal:4'];
    }

    public function items()
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function payments()
    {
        return $this->hasMany(PurchasePayment::class);
    }
}
