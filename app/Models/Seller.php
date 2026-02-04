<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Seller extends Model
{
    protected $fillable = [
        'client_id',
        'seller_name',
        'trade_name',
        'gst_no',
        'pan_no',
        'address'
    ];
}
