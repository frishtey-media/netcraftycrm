<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Barcode extends Model
{
    protected $fillable = [
        'barcode',
        'is_used',
    ];

    protected $casts = [
        'is_used' => 'boolean',
    ];
}
