<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CallingOrder extends Model
{
    protected $table = 'callingorder';

    protected $fillable = [
        'client_id',
        'order_id',
        'order_date',
        'product_name',
        'quantity',
        'customer_name',
        'customer_phone',
        'shipping_address',
        'father_name',
        'state',
        'pincode',
        'payment_mode',
        'city',
        'amount',
        'status',
        'order_source',
        'assigned_to'
    ];
    public function client()
    {
        return $this->belongsTo(\App\Models\Client::class, 'client_id');
    }
}
