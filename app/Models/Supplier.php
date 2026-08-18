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

    public function payments()
    {
        return $this->hasMany(SupplierPayment::class);
    }

    public function cheques()
    {
        return $this->hasMany(Cheque::class);
    }

    public function returns()
    {
        return $this->hasMany(PurchaseReturn::class);
    }

    public function getBalanceAttribute()
    {
        return (string) ($this->ledger()->latest('id')->value('balance_after') ?? $this->opening_balance);
    }

    public function getPendingBalanceAttribute(): string
    {
        return (string) $this->payments()->where('status', 'pending')->sum('amount');
    }
}
