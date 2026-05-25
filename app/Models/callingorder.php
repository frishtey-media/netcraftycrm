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
        'order_date',
        'product_name',
        'quantity',
        'customer_name',
        'customer_phone',
        'shipping_address',
        'father_name',
        'age',
        'state',
        'pincode',
        'payment_mode',
        'city',
        'amount',
        'status',
        'order_source',
        'assigned_to'
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
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'id');
    }
}
