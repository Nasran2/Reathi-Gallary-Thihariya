<?php

namespace App\Models;

use App\Support\Decimal;
use Illuminate\Database\Eloquent\Model;

class Cheque extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['cheque_date' => 'date', 'received_date' => 'date', 'issue_date' => 'date', 'return_date' => 'date', 'amount' => 'decimal:4', 'bank_charge' => 'decimal:4', 'charge_customer' => 'boolean', 'deposited_at' => 'datetime', 'processed_at' => 'datetime'];
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function customerPayments()
    {
        return $this->hasMany(CustomerPayment::class);
    }

    public function supplierPayments()
    {
        return $this->hasMany(SupplierPayment::class);
    }

    public function endorsements()
    {
        return $this->hasMany(ChequeEndorsement::class);
    }

    public function events()
    {
        return $this->hasMany(ChequeEvent::class)->orderBy('occurred_at');
    }

    public function getEndorsedAmountAttribute(): string
    {
        return (string) $this->endorsements()->sum('amount');
    }

    public function getRemainingAmountAttribute(): string
    {
        return Decimal::sub($this->amount, $this->endorsed_amount, 4);
    }
}
