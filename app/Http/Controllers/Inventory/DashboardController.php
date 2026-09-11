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
        $products = Product::leftJoin(
            'client_products',
            'client_products.id',
            '=',
            DB::raw('CAST(products.name AS UNSIGNED)')
        )
            ->select(
                'products.id',
                'client_products.shopify_product_name'
            )
            ->get()
            ->pluck('shopify_product_name', 'id');

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



        $lowStockList = Product::leftJoin(
            'warehouses',
            'products.warehouse_id',
            '=',
            'warehouses.id'
        )
            ->leftJoin(
                'client_products',
                'client_products.id',
                '=',
                DB::raw('CAST(products.name AS UNSIGNED)')
            )
            ->where('products.low_stock_alert', '<=', 250)
            ->select(
                'products.name as product_id',
                'client_products.shopify_product_name as product_name',
                'products.low_stock_alert',
                'warehouses.name as warehouse_name'
            )
            ->get()
            ->map(function ($p) {
                return [
                    'name'      => $p->product_name ?? 'Product ID: ' . $p->product_id,
                    'qty'       => (int) $p->low_stock_alert,
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
        $data = Product::leftJoin(
            'client_products',
            'client_products.id',
            '=',
            DB::raw('CAST(products.name AS UNSIGNED)')
        )
            ->leftJoin(
                'warehouses',
                'products.warehouse_id',
                '=',
                'warehouses.id'
            )
            ->where('products.stock', '<=', 250)
            ->select(
                'products.id',
                'products.name as product_id',
                'client_products.shopify_product_name as product_name',
                'products.stock',
                'warehouses.name as warehouse_name'
            )
            ->get()
            ->map(function ($p) {
                return [
                    'id'        => $p->id,
                    'name'      => $p->product_name ?? 'Product ID: ' . $p->product_id,
                    'stock'     => (int) $p->stock,
                    'warehouse' => $p->warehouse_name ?? 'N/A',
                ];
            });

        return response()->json([
            'status' => true,
            'count'  => $data->count(),
            'data'   => $data
        ]);
    }
}
