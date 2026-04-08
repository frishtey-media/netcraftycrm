<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'quantity',
        'type',
        'price',
        'movement_date',
    ];

    protected $casts = [
        'movement_date' => 'date',
        'price' => 'decimal:2',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    // Total value of this movement
    public function getTotalValueAttribute()
    {
        return $this->quantity * $this->price;
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes (Optional but Useful)
    |--------------------------------------------------------------------------
    */

    public function scopeStockIn($query)
    {
        return $query->where('type', 'in');
    }

    public function scopeStockOut($query)
    {
        return $query->where('type', 'out');
    }
}
