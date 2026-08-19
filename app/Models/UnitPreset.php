<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UnitPreset extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'base_unit_id'];

    public function baseUnit()
    {
        return $this->belongsTo(Unit::class, 'base_unit_id');
    }

    public function conversions()
    {
        return $this->hasMany(UnitPresetConversion::class);
    }
}
