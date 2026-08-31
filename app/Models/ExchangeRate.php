<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExchangeRate extends Model
{
    protected $fillable = [
        'date',
        'rate',
        'is_manual',
    ];

    protected $casts = [
        'date' => 'date',
        'rate' => 'float',
        'is_manual' => 'boolean',
    ];
}