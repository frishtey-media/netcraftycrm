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


        // 👉 using products.stock (IMPORTANT)
        $lowStockList = Product::leftJoin('warehouses', 'products.warehouse_id', '=', 'warehouses.id')
            ->where('products.low_stock_alert', '<=', 250)
            ->select(
                'products.name as product_name',
                'products.low_stock_alert',
                'warehouses.name as warehouse_name'
            )
            ->get()
            ->map(function ($p) {
                return [
                    'name'      => $p->product_name,
                    'qty'       => (int)$p->low_stock_alert,
                    'warehouse' => $p->warehouse_name ?? 'N/A',
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
