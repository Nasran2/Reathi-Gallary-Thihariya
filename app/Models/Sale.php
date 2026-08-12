<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['sold_at' => 'datetime', 'subtotal' => 'decimal:4', 'discount_total' => 'decimal:4', 'tax_total' => 'decimal:4', 'grand_total' => 'decimal:4', 'paid_total' => 'decimal:4', 'due_total' => 'decimal:4', 'cost_total' => 'decimal:4', 'profit_total' => 'decimal:4'];
    }

    public function items()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function payments()
    {
        return $this->hasMany(SalePayment::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function publicToken()
    {
        return $this->hasOne(PublicInvoiceToken::class);
    }
}
