<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShopifyImportOrder extends Model
{
    protected $fillable = [
        'order_id',
        'order_date',
        'payment_mode',
        'amount',
        'customer_name',
        'customer_father_name',
        'customer_phone',
        'shipping_address',
        'city',
        'state',
        'pincode',
        'product',
        'quantity',
        'barcode',
        'weight'
    ];
}
