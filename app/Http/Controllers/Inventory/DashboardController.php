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
        $totalProducts = Product::count();

        // Total Sales
        $totalSales = SaleItem::sum('quantity');

        // Total RTO (from stock_movements)
        $totalRTO = DB::table('stock_movements')
            ->where('type', 'rto_restored')
            ->sum('quantity');

        // Product list
        $products = Product::pluck('name', 'id');

        // ✅ Product-wise Monthly Sales
        $monthlySales = SaleItem::select(
            'product_id',
            DB::raw('MONTH(created_at) as month'),
            DB::raw('SUM(quantity) as total')
        )
            ->groupBy('product_id', 'month')
            ->get();
        // ✅ Product-wise Monthly RTO (FROM stock_movements)
        $monthlyRTO = DB::table('stock_movements')
            ->select(
                'product_id',
                DB::raw('MONTH(movement_date) as month'),
                DB::raw('SUM(quantity) as total')
            )
            ->where('type', 'rto_restored')
            ->groupBy('product_id', 'month')
            ->get();
        // Low Stock Products (<=100)
        $lowStockProducts = DB::table('stock_movements')
            ->select('product_id', DB::raw('SUM(quantity) as total_qty'))
            ->groupBy('product_id')
            ->havingRaw('SUM(quantity) <= 100')
            ->get();

        // Get product names
        $productNames = Product::pluck('name', 'id');

        // Map names with quantity
        $lowStockList = [];

        foreach ($lowStockProducts as $item) {
            $lowStockList[] = [
                'name' => $productNames[$item->product_id] ?? 'Unknown',
                'qty' => $item->total_qty
            ];
        }
        // Format data
        $salesData = [];
        foreach ($monthlySales as $sale) {
            $salesData[$sale->product_id][$sale->month] = (int)$sale->total;
        }

        $rtoData = [];
        foreach ($monthlyRTO as $rto) {
            $rtoData[$rto->product_id][$rto->month] = (int)$rto->total;
        }
        return view('inventory.dashboard', compact(
            'totalProducts',
            'totalSales',
            'totalRTO',
            'products',
            'salesData',
            'rtoData',
            'lowStockList'
        ));
    }
}
