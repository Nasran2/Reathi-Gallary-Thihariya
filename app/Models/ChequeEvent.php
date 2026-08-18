<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChequeEvent extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['metadata' => 'array', 'occurred_at' => 'datetime'];
    }

    public function cheque()
    {
        return $this->belongsTo(Cheque::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
