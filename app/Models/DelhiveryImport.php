<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DelhiveryImport extends Model
{
    protected $fillable = [
        'client_id',
        'order_id',
        'order_date',
        'shopify_order_id',
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
        'age',
        'assigned_staff',
        'status',
        'awb',
        'error_message',
        'serviceability_response',
        'booking_response',
    ];

    protected $casts = [
        'order_date' => 'datetime',
        'amount' => 'decimal:2',
        'weight' => 'decimal:2',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}
