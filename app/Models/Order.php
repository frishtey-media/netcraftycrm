<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'client_id',
        'date',
        'barcode',
        'payment_mode',
        'amount',
        'customer_name',
        'father_name',
        'customer_phone',
        'shipping_address',
        'city',
        'state',
        'pincode',
        'product',
        'quantity',
        'weight',
        'delivery_status',
        'recivedpaysts',
        'rtorecivedsts',
        'delivery_date',
        'delivery_remark',
        'manual_delivery',

        'manual_delivery_by',

        'manual_delivery_date',
    ];

    public function client()
    {
        return $this->belongsTo(
            \App\Models\Client::class,
            'client_id',
            'id'
        );
    }
    public function callingOrder()
    {
        return $this->hasOne(
            \App\Models\callingorder::class,
            'order_id',
            'order_id'
        );
    }
}
