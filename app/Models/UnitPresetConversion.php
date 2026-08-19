<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UnitPresetConversion extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $fillable = ['unit_id', 'unit_quantity'];

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }
}
