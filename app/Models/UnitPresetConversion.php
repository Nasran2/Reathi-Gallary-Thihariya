<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UnitPresetConversion extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $fillable = ['unit_id', 'base_quantity', 'unit_quantity'];

    protected function casts(): array
    {
        return [
            'base_quantity' => 'decimal:8',
            'unit_quantity' => 'decimal:8',
        ];
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }
}
