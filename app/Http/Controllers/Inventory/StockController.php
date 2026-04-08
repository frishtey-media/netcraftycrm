<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Stock;


class StockController extends Controller
{
    public function index()
    {
        $stocks = Stock::with(['product', 'warehouse'])
            ->orderBy('warehouse_id')
            ->get();

        return view('inventory.stocks.index', compact('stocks'));
    }
}
