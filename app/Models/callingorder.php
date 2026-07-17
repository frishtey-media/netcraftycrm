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
        'assigned_to',

        'order_id',
        'order_date',
        'shopify_order_id',
        'product_name',
        'shopify_product_name',

        'quantity',
        'weight',
        'total_weight',

        'customer_name',
        'father_name',
        'customer_phone',

        'shipping_address',
        'city',
        'state',
        'pincode',
        'age',
        'payment_mode',
        'amount',

        'status',
        'order_source',
        'created_at',
        'updated_at'
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
