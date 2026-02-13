<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Barcode extends Model
{
    protected $fillable = [
        'barcode',
        'is_used',
        'client_id'
    ];

    protected $casts = [
        'is_used' => 'boolean',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
