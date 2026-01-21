<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LabelSender extends Model
{
    protected $fillable = [
        'customer_name',
        'customer_phone',
    ];
}
