<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductWarehouse extends Model
{
    protected $table = 'product_warehouse';

    protected $fillable = [
        'product_id',
        'warehouse_id',
        'stock'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }
}
