<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DuplicateOrderLog extends Model
{
    protected $fillable = [
        'order_id',
        'client_id',
        'barcode',
        'customer_name',
        'customer_phone',
        'shipping_address',
        'city',
        'state',
        'pincode',
        'product',
        'quantity',
        'amount',
        'reason',
    ];
}
