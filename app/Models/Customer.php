<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['credit_limit' => 'decimal:4', 'opening_balance' => 'decimal:4', 'is_walk_in' => 'boolean', 'active' => 'boolean'];
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function ledger()
    {
        return $this->hasMany(CustomerLedger::class);
    }

    public function getBalanceAttribute()
    {
        return (string) ($this->ledger()->latest('id')->value('balance_after') ?? $this->opening_balance);
    }
}
