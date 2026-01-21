<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShopifyOrder extends Model
{
    protected $table = 'shopify_orders';
    protected $fillable = [
        'client_id',
        'order_id',
        'order_date',
        'product_name',
        'shopify_product_name',
        'quantity',
        'weight_per_unit',
        'total_weight',
        'barcode',
        'customer_name',
        'customer_phone',
        'father_name',
        'shipping_address',
        'city',
        'state',
        'pincode',
        'payment_mode',
        'amount',
    ];


    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
