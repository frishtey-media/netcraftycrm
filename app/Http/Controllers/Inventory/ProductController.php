<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use App\Models\Warehouse;
use Illuminate\Http\Request;
//use Illuminate\Validation\Rule;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ProductStockExport;
use App\Models\RtoReport;
use App\Exports\RTOReportExport;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $categories = Category::all();
        $warehouses = Warehouse::all();

        $query = Product::with(['category', 'warehouse']);

        if ($request->category_id) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->warehouse_id) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        $products = $query
            ->latest()
            ->paginate(10);

        return view('inventory.products.index', compact('products', 'categories', 'warehouses'));
    }

    public function productreport(Request $request)
    {
        $query = StockMovement::with([
            'product.warehouse',
            'product.category'
        ]);

        // ✅ Date filter
        if ($request->filled('from_date') && $request->filled('to_date')) {
            $query->whereBetween('movement_date', [
                $request->from_date,
                $request->to_date
            ]);
        }

        // ✅ Product search
        if ($request->filled('product_name')) {
            $query->whereHas('product', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->product_name . '%');
            });
        }

        // ✅ Optional: exclude wrong negative RTO data (temporary safety)
        $query->where(function ($q) {
            $q->where('type', '!=', 'rto_restored')
                ->orWhere(function ($sub) {
                    $sub->where('type', 'rto_restored')
                        ->where('quantity', '>', 0); // only valid RTO
                });
        });

        $products = $query->orderBy('product_id')
            ->orderBy('movement_date')
            ->get()
            ->groupBy('product_id');

        return view('inventory.products.report', compact('products'));
    }
    public function exportProductReport(Request $request)
    {
        $query = StockMovement::with([
            'product.warehouse',
            'product.category'
        ]);

        // ✅ Date filter (safe)
        if ($request->filled('from_date') && $request->filled('to_date')) {
            $query->whereBetween('movement_date', [
                $request->from_date,
                $request->to_date
            ]);
        }

        // ✅ Product search (safe)
        if ($request->filled('product_name')) {
            $query->whereHas('product', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->product_name . '%');
            });
        }

        // ✅ IMPORTANT: ignore wrong negative RTO data
        $query->where(function ($q) {
            $q->where('type', '!=', 'rto_restored')
                ->orWhere(function ($sub) {
                    $sub->where('type', 'rto_restored')
                        ->where('quantity', '>', 0);
                });
        });

        $movements = $query->orderBy('product_id')
            ->orderBy('movement_date')
            ->get()
            ->groupBy('product_id');

        return Excel::download(
            new ProductStockExport($movements),
            'product_report.xlsx'
        );
    }
    public function create()

    {
        $Warehouse = Warehouse::all();
        $categories = Category::all();
        return view('inventory.products.create', compact('categories', 'Warehouse'));
    }



    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'low_stock_alert' => 'required|integer|min:0',
            'category_id' => 'required|exists:categories,id',
            'warehouse_id' => 'required|exists:warehouses,id',
        ]);


        $exists = Product::where('name', $request->name)
            ->where('warehouse_id', $request->warehouse_id)
            ->exists();

        if ($exists) {
            return redirect()->route('products.index')
                ->with('error', 'This product already exists in selected warehouse.');
        }

        DB::transaction(function () use ($request) {

            $product = Product::create([
                'name' => $request->name,
                'price' => $request->price,
                'low_stock_alert' => $request->low_stock_alert,
                'total_price' => $request->price * $request->low_stock_alert,
                'category_id' => $request->category_id,
                'warehouse_id' => $request->warehouse_id,
            ]);

            StockMovement::create([
                'product_id' => $product->id,
                'quantity' => $request->low_stock_alert,
                'type' => 'created',
                'price' => $request->price,
                'movement_date' => now(),
            ]);
        });

        return redirect()->route('products.index')
            ->with('success', 'Product created successfully');
    }



    public function edit(Product $product)
    {
        $Warehouse = Warehouse::all();
        $categories = Category::all();
        return view('inventory.products.edit', compact('product', 'categories', 'Warehouse'));
    }

    public function update(Request $request, Product $product)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'required|unique:products,sku,' . $product->id,
            'price' => 'required|numeric|min:0',
            'low_stock_alert' => 'required|integer|min:0',
            'category_id' => 'required|exists:categories,id',
        ]);

        $product->update($request->all());

        return redirect()->route('products.index')
            ->with('success', 'Product updated successfully');
    }

    public function updateStock(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'low_stock_alert' => 'required|integer|min:1',
        ]);

        $product = Product::findOrFail($request->product_id);


        $product->low_stock_alert += $request->low_stock_alert;


        $product->total_price = $product->price * $product->low_stock_alert;

        $product->save();


        StockMovement::create([
            'product_id' => $product->id,
            'quantity' => $request->low_stock_alert,
            'type' => 'updated',
            'price' => $product->price,
            'movement_date' => now(),
        ]);

        return back()->with('success', 'Stock updated successfully');
    }




    public function destroy(Product $product)
    {
        $product->delete();

        return redirect()->route('products.index')
            ->with('success', 'Product deleted successfully');
    }




    public function rtoreport(Request $request)
    {
        $query = RtoReport::query();

        if ($request->from_date) {
            $query->whereDate('order_date', '>=', $request->from_date);
        }

        if ($request->to_date) {
            $query->whereDate('order_date', '<=', $request->to_date);
        }

        if ($request->tracking_no) {
            $query->where('tracking_no', 'like', '%' . $request->tracking_no . '%');
        }

        $rtoReports = $query->orderBy('product')
            ->get()
            ->groupBy('product');

        return view('inventory.rto.report', compact('rtoReports'));
    }


    public function exportRTOReport(Request $request)
    {
        $query = RtoReport::query();

        if ($request->from_date) {
            $query->whereDate('order_date', '>=', $request->from_date);
        }

        if ($request->to_date) {
            $query->whereDate('order_date', '<=', $request->to_date);
        }

        if ($request->tracking_no) {
            $query->where('tracking_no', 'like', '%' . $request->tracking_no . '%');
        }

        $rtoReports = $query->orderBy('product')->get()->groupBy('product');

        return Excel::download(
            new RTOReportExport($rtoReports),
            'rto_report_' . now()->format('d-m-Y') . '.xlsx'
        );
    }



    public function rtoRestock()
    {
        $rtoData = DB::table('rto_reports')
            ->where('is_restocked', 0)
            ->select('product', DB::raw('SUM(quantity) as total_qty'))
            ->groupBy('product')
            ->get();

        if ($rtoData->isEmpty()) {
            return back()->with('error', 'No new RTO data to restock');
        }

        DB::transaction(function () use ($rtoData) {

            foreach ($rtoData as $item) {

                $product = Product::where('name', $item->product)->first();

                if (!$product) continue;

                $qty = $item->total_qty;


                $product->low_stock_alert += $qty;
                $product->total_price = $product->price * $product->low_stock_alert;
                $product->save();


                StockMovement::create([
                    'product_id' => $product->id,
                    'quantity' => $qty,
                    'type' => 'rto_restored',
                    'price' => $product->price,
                    'movement_date' => now(),
                ]);
            }


            DB::table('rto_reports')
                ->where('is_restocked', 0)
                ->update(['is_restocked' => 1]);
        });

        return back()->with('success', 'RTO Restocked Successfully');
    }
}
