<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Unit extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['allows_decimal' => 'boolean', 'active' => 'boolean'];
    }
}
