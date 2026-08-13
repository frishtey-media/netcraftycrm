<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\CallingUser;
use App\Models\Client;

class CallingOrder extends Model
{
    protected $table = 'callingorder';

    protected $fillable = [
        'client_id',
        'order_id',
        'shopify_order_id',
        'shopify_checkout_id',
        'checkout_url',
        'customer_email',
        'order_date',
        'product_name',
        'shopify_product_name',
        'weight_per_unit',
        'quantity',
        'weight',
        'total_weight',
        'barcode',
        'customer_name',
        'father_name',
        'customer_phone',
        'shipping_address',
        'age',
        'city',
        'state',
        'pincode',
        'payment_mode',
        'amount',
        'status',
        'remarks',
        'order_source',
        'assigned_to',
        'is_exported',
    ];

    // Client Relation
    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id', 'id');
    }

    // Staff Relation
    public function staff()
    {
        return $this->belongsTo(CallingUser::class, 'assigned_to', 'id');
    }
    //public function order()
    //{
    // return $this->belongsTo(Order::class, 'order_id', 'id');
    //}

    public function order()
    {
        return $this->belongsTo(
            Order::class,
            'order_id',
            'order_id'
        );
    }
}
