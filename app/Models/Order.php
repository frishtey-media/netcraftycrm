<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'date',
        'barcode',
        'payment_mode',
        'amount',
        'customer_name',
        'customer_phone',
        'shipping_address',
        'city',
        'state',
        'pincode',
        'product',
        'quantity',
        'weight',
    ];
}
