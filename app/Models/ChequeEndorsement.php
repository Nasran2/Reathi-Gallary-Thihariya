<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChequeEndorsement extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['amount' => 'decimal:4', 'transfer_date' => 'date'];
    }

    public function cheque()
    {
        return $this->belongsTo(Cheque::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function payment()
    {
        return $this->belongsTo(SupplierPayment::class, 'supplier_payment_id');
    }
}
