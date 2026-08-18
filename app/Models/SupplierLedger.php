<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupplierLedger extends Model
{
    protected $table = 'supplier_ledger';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['debit' => 'decimal:4', 'credit' => 'decimal:4', 'pending' => 'decimal:4', 'balance_after' => 'decimal:4', 'occurred_at' => 'datetime'];
    }

    public function reference()
    {
        return $this->morphTo();
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }
}
