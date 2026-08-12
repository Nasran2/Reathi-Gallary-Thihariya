<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['opening_balance' => 'decimal:4', 'active' => 'boolean'];
    }

    public function purchases()
    {
        return $this->hasMany(Purchase::class);
    }

    public function ledger()
    {
        return $this->hasMany(SupplierLedger::class);
    }

    public function getBalanceAttribute()
    {
        return (string) ($this->ledger()->latest('id')->value('balance_after') ?? $this->opening_balance);
    }
}
