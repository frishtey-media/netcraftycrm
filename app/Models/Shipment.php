<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shipment extends Model
{
    protected $fillable = [
        'order_id',
        'courier',
        'awb',
        'status',
        'booking_response',
        'tracking_response',

        'label_path',
        'label_url',
        'pickup_request_id',
        'booked_at',
        'label_response',
        'label_generated_at',
        'picked_up_at',
        'delivered_at',
    ];

    protected $casts = [
        'booked_at' => 'datetime',
        'label_generated_at' => 'datetime',
        'picked_up_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(
            \App\Models\Order::class,
            'order_id'
        );
    }
}
