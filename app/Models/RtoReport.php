<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RtoReport extends Model
{
    protected $fillable = [
        'order_id',
        'tracking_no',
        'customer_name',
        'customer_phone',
        'shipping_address',
        'payment_mode',
        'amount',
        'product',
        'quantity',
        'weight',
        'order_date',
        'is_exported',
        'assign_staff',
        'assigndate'
    ];
    public function callingOrder()
    {
        return $this->hasOne(CallingOrder::class, 'order_id', 'order_id');
    }
}
