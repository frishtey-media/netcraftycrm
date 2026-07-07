<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [

        'order_id',

        'article_number',

        'article_count',

        'cod_invoice_number',

        'delivered_date',

        'cod_value',

        'cod_commission',

        'office_id',

        'office_name',

        'customer_id',

        'customer_name',

        'bill_date',

        'contract_id',

        'contract_mode'

    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
