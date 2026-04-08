<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\ProductWarehouse;
use App\Models\Product;
use App\Models\Stock;
use App\Models\Supplier;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class PurchaseController extends Controller
{
    public function index()
    {
        $purchases = Purchase::with(['supplier', 'warehouse'])
            ->latest()
            ->get();

        return view('inventory.purchases.index', compact('purchases'));
    }
    public function create()
    {
        $suppliers = Supplier::all();
        $products = Product::all();
        $warehouses = Warehouse::all();

        return view('inventory.purchases.create', compact(
            'suppliers',
            'products',
            'warehouses'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'purchase_date' => 'required|date',
            'products' => 'required|array|min:1',
        ]);

        DB::transaction(function () use ($request) {

            $purchase = Purchase::create([
                'invoice_no' => 'PUR-' . time(),
                'supplier_id' => $request->supplier_id,
                'warehouse_id' => $request->warehouse_id,
                'purchase_date' => $request->purchase_date,
                'total_amount' => $request->total_amount,
            ]);
            foreach ($request->input('items', []) as $item) {


                $subtotal = $item['quantity'] * $item['price'];

                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'subtotal' => $subtotal,
                ]);

                $stock = Stock::firstOrCreate(
                    [
                        'product_id' => $item['product_id'],
                        'warehouse_id' => $request->warehouse_id,
                    ],
                    ['quantity' => 0]
                );

                $stock->increment('quantity', $item['quantity']);
            }
        });

        return redirect()->route('purchases.index')
            ->with('success', 'Purchase Saved Successfully');
    }
}
