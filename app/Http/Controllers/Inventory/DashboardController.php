<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\SaleItem;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        // ================= BASIC STATS =================
        $totalProducts = Product::count();
        $totalSales    = SaleItem::sum('quantity');

        $totalRTO = DB::table('stock_movements')
            ->where('type', 'rto_restored')
            ->sum('quantity');

        $products = Product::pluck('name', 'id');

        // ================= MONTHLY SALES =================
        $monthlySales = SaleItem::select(
            'product_id',
            DB::raw('MONTH(created_at) as month'),
            DB::raw('SUM(quantity) as total')
        )
            ->groupBy('product_id', 'month')
            ->get();

        // ================= MONTHLY RTO =================
        $monthlyRTO = DB::table('stock_movements')
            ->select(
                'product_id',
                DB::raw('MONTH(movement_date) as month'),
                DB::raw('SUM(quantity) as total')
            )
            ->where('type', 'rto_restored')
            ->groupBy('product_id', 'month')
            ->get();

        // ================= LOW STOCK (CORRECT) =================
        // 👉 using products.stock (IMPORTANT)
        $lowStockList = Product::where('low_stock_alert', '<=', 250)
            ->get()
            ->map(function ($p) {
                return [
                    'name' => $p->name,
                    'qty'  => (int)$p->low_stock_alert
                ];
            });

        // ================= FORMAT DATA =================
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

    // ================= API FOR AUTO ALERT =================
    public function lowStockApi()
    {
        $data = Product::where('stock', '<=', 250)
            ->select('id', 'name', 'stock')
            ->get();

        return response()->json([
            'status' => true,
            'count'  => $data->count(),
            'data'   => $data
        ]);
    }
}
