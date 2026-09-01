<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaleReturn extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['return_date' => 'date', 'return_total' => 'decimal:4', 'cost_total' => 'decimal:4', 'refund_amount' => 'decimal:4', 'is_manual' => 'boolean'];
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function items()
    {
        return $this->hasMany(SaleReturnItem::class);
    }
}
