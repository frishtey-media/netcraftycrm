<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class whatsapporder extends Model
{
    protected $table = 'shopify_orders';

    protected $fillable = [
        'client_id',
        'order_id',
        'order_date',
        'product_name',
        'shopify_product_name',
        'quantity',
        'weight',
        'total_weight',
        'barcode',
        'customer_name',
        'father_name',
        'customer_phone',
        'shipping_address',
        'city',
        'state',
        'pincode',
        'payment_mode',
        'amount'
    ];
}
