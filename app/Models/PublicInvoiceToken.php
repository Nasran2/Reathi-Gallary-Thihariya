<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PublicInvoiceToken extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['expires_at' => 'datetime'];
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }
}
