<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Product;
//use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\RtoReport;

use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $totalProducts = Product::sum('low_stock_alert');
        $totalRTO = RtoReport::count();
        $totalSales = SaleItem::sum('quantity');

        // Monthly Sales
        $monthlySales = SaleItem::select(
            DB::raw('MONTH(created_at) as month'),
            DB::raw('SUM(quantity) as total')
        )
            ->groupBy('month')
            ->pluck('total', 'month')
            ->toArray();

        // Monthly RTO
        $monthlyRTO = RtoReport::select(
            DB::raw('MONTH(created_at) as month'),
            DB::raw('COUNT(id) as total')
        )
            ->groupBy('month')
            ->pluck('total', 'month')
            ->toArray();

        return view('inventory.dashboard', compact(
            'totalProducts',
            'totalRTO',
            'totalSales',
            'monthlySales',
            'monthlyRTO'
        ));
    }
}
