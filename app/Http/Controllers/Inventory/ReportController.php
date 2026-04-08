<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\ProductWarehouse;
use App\Models\Sale;

class ReportController extends Controller
{
    public function stock()
    {
        return view('reports.stock', [
            'stocks' => ProductWarehouse::with('product', 'warehouse')->get()
        ]);
    }

    public function profit()
    {
        $profit = 0;

        $sales = Sale::with('items.product')->get();

        foreach ($sales as $sale) {
            foreach ($sale->items as $item) {
                $cost = $item->product->price;
                $profit += ($item->price - $cost) * $item->quantity;
            }
        }

        return view('reports.profit', compact('profit'));
    }
}
