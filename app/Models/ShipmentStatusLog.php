<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShipmentStatusLog extends Model
{
    protected $fillable = [
        'order_id',
        'awb',
        'status',
        'event_id',
        'webhook_status',
        'attempts',
        'response_status',
        'response_body',
        'sent_at',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
