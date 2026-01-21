<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClientProduct extends Model
{
    protected $fillable = [
        'client_id',
        'shopify_product_name',
        'weight_per_unit'
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
