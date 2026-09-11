<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'category_id',
        'client_id',
        'name',
        'sku',
        'price',
        'total_price',
        'low_stock_alert',
        'warehouse_id',
    ];

    public function clientProduct()
    {
        return $this->belongsTo(
            ClientProduct::class,
            'name',
            'id'
        );
    }
    public function client()
    {
        return $this->belongsTo(
            Client::class,
            'client_id'
        );
    }


    public function warehouse()
    {
        return $this->belongsTo(
            Warehouse::class,
            'warehouse_id'
        );
    }


    public function category()
    {
        return $this->belongsTo(
            Category::class,
            'category_id'
        );
    }


    public function warehouseStocks()
    {
        return $this->hasMany(ProductWarehouse::class);
    }


    public function purchaseItems()
    {
        return $this->hasMany(PurchaseItem::class);
    }


    public function saleItems()
    {
        return $this->hasMany(SaleItem::class);
    }


    public function stocks()
    {
        return $this->hasMany(Stock::class);
    }


    public function movements()
    {
        return $this->hasMany(StockMovement::class);
    }
}
