<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Stock extends Model
{
    protected $fillable = [
        'product_id',
        'warehouse_id',
        'quantity'
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    // Stock belongs to Product
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // Stock belongs to Warehouse
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    // Increase stock
    public function increase($qty)
    {
        $this->increment('quantity', $qty);
    }

    // Decrease stock
    public function decrease($qty)
    {
        if ($this->quantity < $qty) {
            throw new \Exception("Insufficient stock");
        }

        $this->decrement('quantity', $qty);
    }
}
