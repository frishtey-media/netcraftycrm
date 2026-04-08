<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    protected $fillable = [
        'warehouse_id',
        'invoice_no',
        'sale_date',
        'total'
    ];

    protected $dates = ['sale_date'];

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function items()
    {
        return $this->hasMany(SaleItem::class);
    }
}
