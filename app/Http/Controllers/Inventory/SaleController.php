<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Product;
//use App\Models\Stock;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\SalesReportExport;
//use App\Models\Client;

class SaleController extends Controller
{
    public function index()
    {
        $sales = Sale::with(['warehouse', 'items.product'])
            ->withSum('items', 'quantity')
            ->latest()
            ->get();

        return view('inventory.sales.index', compact('sales'));
    }
    public function create()
    {
        $products = Product::all();
        $warehouses = Warehouse::all();


        return view('inventory.sales.create', compact(
            'products',
            'warehouses',

        ));
    }


    public function salesreport(Request $request)
    {
        $query = SaleItem::with(['sale', 'product.warehouse', 'product.client']);

        // Date filter
        if ($request->from && $request->to) {
            $query->whereBetween('created_at', [
                $request->from . ' 00:00:00',
                $request->to . ' 23:59:59'
            ]);
        }

        // ✅ Product filter
        if ($request->product_id) {
            $query->where('product_id', $request->product_id);
        }

        $sales = $query->orderBy('product_id')
            ->orderBy('created_at')
            ->get()
            ->groupBy('product_id');

        // Send products for dropdown
        $products = Product::all();

        return view('inventory.sales.report', compact('sales', 'products'));
    }



    public function store(Request $request)
    {
        $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'sale_date' => 'required|date',
            'items' => 'required|array',
        ]);

        DB::transaction(function () use ($request) {

            $sale = Sale::create([
                'invoice_no' => 'SAL-' . time(),
                'warehouse_id' => $request->warehouse_id,
                'sale_date' => $request->sale_date,
                'total_amount' => 0,
            ]);

            $grandTotal = 0;

            foreach ($request->items as $item) {

                $product = Product::where('id', $item['product_id'])
                    ->where('warehouse_id', $request->warehouse_id)
                    ->first();

                if (!$product) {
                    throw new \Exception('Product not found');
                }


                if ($product->low_stock_alert < $item['quantity']) {
                    throw new \Exception('Insufficient stock for ' . $product->name);
                }

                $subtotal = $item['quantity'] * $product->price;
                // dd($subtotal);
                $grandTotal += $subtotal;

                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'price' => $product->price,
                    'subtotal' => $subtotal,
                ]);


                $newStock = $product->low_stock_alert - $item['quantity'];

                $product->update([
                    'low_stock_alert' => $newStock,
                    'total_price' => $newStock * $product->price
                ]);
            }

            $sale->update([
                'total_amount' => $grandTotal
            ]);
        });

        return redirect()->route('sales.index')
            ->with('success', 'Sale Saved Successfully');
    }


    public function invoice(Sale $sale)
    {
        return view('inventory.sales.invoice', compact('sale'));
    }

    public function exportsalesReport(Request $request)
    {
        $query = SaleItem::with(['sale', 'product']);

        if ($request->from_date) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }

        if ($request->to_date) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        $salesreport = $query->orderBy('product_id')
            ->latest()
            ->get();

        return Excel::download(
            new SalesReportExport($salesreport),
            'sales_report_' . now()->format('d-m-Y') . '.xlsx'
        );
    }
}
