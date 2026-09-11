<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use App\Models\Client;
use App\Models\Warehouse;
use Illuminate\Http\Request;
//use Illuminate\Validation\Rule;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ProductStockExport;
use App\Models\RtoReport;
use App\Exports\RTOReportExport;
use App\Models\ClientProduct;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $clients = Client::orderBy('client_name')->get();

        $warehouses = Warehouse::orderBy('name')->get();

        $query = Product::with([
            'client',
            'clientProduct',
            'warehouse'
        ]);

        // Search
        if ($request->filled('search')) {

            $search = $request->search;

            $query->where(function ($q) use ($search) {

                $q->whereHas('clientProduct', function ($q) use ($search) {
                    $q->where(
                        'shopify_product_name',
                        'like',
                        '%' . $search . '%'
                    );
                })

                    ->orWhereHas('client', function ($q) use ($search) {
                        $q->where(
                            'client_name',
                            'like',
                            '%' . $search . '%'
                        );
                    });
            });
        }

        // Client filter
        if ($request->filled('client_id')) {
            $query->where(
                'client_id',
                $request->client_id
            );
        }

        // Warehouse filter
        if ($request->filled('warehouse_id')) {
            $query->where(
                'warehouse_id',
                $request->warehouse_id
            );
        }

        $products = $query
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view(
            'inventory.products.index',
            compact(
                'products',
                'clients',
                'warehouses'
            )
        );
    }

    public function productreport(Request $request)
    {
        $query = StockMovement::with([
            'product.warehouse',
            'product.category',
            'product.client',
            'product.clientProduct',
        ])
            ->whereHas('product');


        if (
            $request->filled('from_date') &&
            $request->filled('to_date')
        ) {

            $query->whereBetween('movement_date', [
                $request->from_date . ' 00:00:00',
                $request->to_date . ' 23:59:59'
            ]);
        }

        if ($request->filled('product_id')) {

            $query->where(
                'product_id',
                $request->product_id
            );
        }


        if ($request->filled('product_name')) {

            $search = $request->product_name;

            $query->whereHas(
                'product.clientProduct',
                function ($q) use ($search) {

                    $q->where(
                        'shopify_product_name',
                        'like',
                        '%' . $search . '%'
                    );
                }
            );
        }



        $query->whereIn('type', [
            'created',
            'updated',
            'in',
            'rto_restored'
        ]);

        $movements = $query
            ->orderBy('product_id')
            ->orderBy('movement_date')
            ->get();

        $products = $movements
            ->groupBy('product_id')
            ->map(function ($items) {

                // ---------------------------------------------------------
                // NORMAL MOVEMENTS
                // ---------------------------------------------------------
                $normalMovements = $items
                    ->filter(function ($item) {
                        return $item->type !== 'rto_restored';
                    })
                    ->values();


                // ---------------------------------------------------------
                // RTO MOVEMENTS
                // SAME PRODUCT + SAME DATE = ONE ROW
                // ---------------------------------------------------------
                $rtoMovements = $items
                    ->filter(function ($item) {
                        return $item->type === 'rto_restored';
                    })
                    ->groupBy(function ($item) {

                        return $item->movement_date
                            ? \Carbon\Carbon::parse($item->movement_date)->format('Y-m-d')
                            : 'no-date';
                    })
                    ->map(function ($rtoItems) {

                        // First RTO record as base
                        $first = clone $rtoItems->first();

                        // Total RTO quantity for that date
                        $first->quantity = $rtoItems->sum(function ($item) {
                            return (int) $item->quantity;
                        });

                        // Keep date same
                        if ($first->movement_date) {
                            $first->movement_date = \Carbon\Carbon::parse(
                                $first->movement_date
                            )->startOfDay();
                        }

                        return $first;
                    })
                    ->values();


                // ---------------------------------------------------------
                // MERGE NORMAL + COMBINED RTO
                // ---------------------------------------------------------
                return $normalMovements
                    ->concat($rtoMovements)
                    ->sortBy(function ($item) {

                        return $item->movement_date
                            ? \Carbon\Carbon::parse($item->movement_date)->timestamp
                            : 0;
                    })
                    ->values();
            })
            ->filter(function ($items) {
                return $items->count() > 0;
            });

        $allProducts = Product::with([
            'clientProduct'
        ])
            ->orderBy('id')
            ->get();

        return view(
            'inventory.products.report',
            compact(
                'products',
                'allProducts'
            )
        );
    }
    public function exportProductReport(Request $request)
    {
        $query = StockMovement::with([
            'product.warehouse',
            'product.category'
        ]);


        if ($request->filled('from_date') && $request->filled('to_date')) {
            $query->whereBetween('movement_date', [
                $request->from_date,
                $request->to_date
            ]);
        }


        if ($request->filled('product_name')) {
            $query->whereHas('product', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->product_name . '%');
            });
        }


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
        $Client = Client::all();
        $client_products = ClientProduct::all();
        return view('inventory.products.create', compact('Client', 'Warehouse', 'client_products'));
    }



    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|exists:client_products,id',
            'price' => 'required|numeric|min:0',
            'low_stock_alert' => 'required|integer|min:0',
            'client_id' => 'required|exists:clients,id',
            'warehouse_id' => 'required|exists:warehouses,id',
        ]);

        // Client Product ID
        $clientProduct = ClientProduct::findOrFail($request->name);

        // Duplicate check
        $exists = Product::where('name', $clientProduct->id)
            ->where('client_id', $request->client_id)
            ->where('warehouse_id', $request->warehouse_id)
            ->exists();

        if ($exists) {
            return redirect()
                ->route('products.index')
                ->with(
                    'error',
                    'This product already exists for the selected client and warehouse.'
                );
        }

        DB::transaction(function () use ($request, $clientProduct) {

            $product = Product::create([
                // IMPORTANT:
                // Store ClientProduct ID in name column
                'name' => $clientProduct->id,

                // Client ID
                'client_id' => $request->client_id,

                // Warehouse
                'warehouse_id' => $request->warehouse_id,

                // Price
                'price' => $request->price,

                // Stock
                'low_stock_alert' => $request->low_stock_alert,

                // Total
                'total_price' =>
                $request->price * $request->low_stock_alert,
            ]);

            // Stock movement
            StockMovement::create([
                'product_id' => $product->id,
                'quantity' => $request->low_stock_alert,
                'type' => 'created',
                'price' => $request->price,
                'movement_date' => now(),
            ]);
        });

        return redirect()
            ->route('products.index')
            ->with(
                'success',
                'Product created successfully.'
            );
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



    public function rtoStock(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
        ]);


        DB::transaction(function () use ($request) {

            $product = Product::findOrFail(
                $request->product_id
            );


            // Add RTO stock
            $product->low_stock_alert =
                $product->low_stock_alert +
                $request->quantity;


            // Recalculate total price
            $product->total_price =
                $product->price *
                $product->low_stock_alert;


            $product->save();


            // Stock movement
            StockMovement::create([
                'product_id' => $product->id,
                'quantity' => $request->quantity,
                'type' => 'rto_restored',
                'price' => $product->price,
                'movement_date' => now(),
            ]);
        });


        return back()->with(
            'success',
            'RTO Stock added successfully'
        );
    }


    public function updateStock(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'low_stock_alert' => 'required|integer|min:1',
        ]);
        // dd($request->all());
        $product = Product::findOrFail($request->product_id);

        // debug
        // dd($request->all(), $product);

        $product->low_stock_alert = $product->low_stock_alert + (int)$request->low_stock_alert;

        $product->total_price = $product->price * $product->low_stock_alert;

        $saved = $product->save();

        StockMovement::create([
            'product_id' => $product->id,
            'quantity' => $request->low_stock_alert,
            'type' => 'updated',
            'price' => $product->price,
            'movement_date' => now(),
        ]);

        return redirect()->back()->with('success', 'Stock updated successfully');
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

    private function getPackMultiplier($productName)
    {
        $productName = strtolower($productName);

        // Case 1: 1+1, 1+2 etc
        if (preg_match('/(\d+)\s*\+\s*(\d+)/', $productName, $matches)) {
            return (int)$matches[1] + (int)$matches[2];
        }

        // Case 2: Pack Of 2, Pack Of 3
        if (preg_match('/pack\s*of\s*(\d+)/', $productName, $matches)) {
            return (int)$matches[1];
        }

        // Default
        return 1;
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

                // Case-insensitive match (IMPORTANT FIX)
                $product = Product::whereRaw('LOWER(name) = ?', [strtolower(trim($item->product))])->first();

                if (!$product) continue;

                // 🔥 Get pack multiplier
                $multiplier = $this->getPackMultiplier($item->product);

                // 🔥 Final restock quantity
                $qty = $item->total_qty * $multiplier;

                // Update stock
                $product->low_stock_alert += $qty;
                $product->total_price = $product->price * $product->low_stock_alert;
                $product->save();

                // Log stock movement
                StockMovement::create([
                    'product_id' => $product->id,
                    'quantity' => $qty,
                    'type' => 'rto_restored',
                    'price' => $product->price,
                    'movement_date' => now(),
                ]);
            }

            // Mark as restocked
            DB::table('rto_reports')
                ->where('is_restocked', 0)
                ->update(['is_restocked' => 1]);
        });

        return back()->with('success', 'RTO Restocked Successfully');
    }
}
