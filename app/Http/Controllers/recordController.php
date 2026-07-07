<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\records;
use App\Models\Barcode;
use App\Models\ShopifyOrder;
use Illuminate\Support\Facades\DB;
use App\Models\ClientProduct;
use App\Models\Client;
use App\Models\callingorder;
use App\Models\CallingUser;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\BulkOrderImport;

class RecordController extends Controller
{

    public function create()
    {
        $user = auth()->user();

        // Super Admin => All Clients
        if ($user->role == 'super_admin') {
            $clients = Client::orderBy('client_name')->get();
        } else {
            $clients = Client::where('id', $user->client_id)->get();
        }

        $staffs = CallingUser::where('status', 1)->get();

        // Temporary Order ID
        $orderId = 'AUTO-GENERATED';

        return view(
            'record.create',
            compact(
                'orderId',
                'clients',
                'staffs'
            )
        );
    }
    public function generateOrderId($staffId)
    {
        $staff = CallingUser::findOrFail($staffId);

        $name = trim($staff->name);

        $shortName =
            strtoupper(substr($name, 0, 1)) .
            strtolower(substr($name, -1));

        $date = now()->format('d-m-y');

        $todayCount = CallingOrder::whereDate('created_at', today())
            ->where('assigned_to', $staff->id)
            ->count() + 1;

        $orderId = $shortName . '-' . $date . '-' . $todayCount;

        return response()->json([
            'order_id' => $orderId
        ]);
    }
    public function store(Request $request)
    {
        $request->validate([
            'client_id'                 => 'required',
            'assigned_to'               => 'required',
            'products'                  => 'required|array|min:1',
            'products.*.product'        => 'required',
            'products.*.quantity'       => 'required|integer|min:1',
            'customer_name'             => 'required',
            'customer_phone'            => 'required',
            'payment_mode'              => 'required',
            'age'                       => 'required',
            'amount'                    => 'required|numeric',
        ]);

        $staff = CallingUser::findOrFail($request->assigned_to);

        $name = trim($staff->name);

        $shortName =
            strtoupper(substr($name, 0, 1)) .
            strtolower(substr($name, -1));

        $date = now()->format('d-m-y');

        $todayCount = CallingOrder::whereDate('created_at', today())
            ->where('assigned_to', $staff->id)
            ->count() + 1;

        $orderId = $shortName . '-' . $date . '-' . $todayCount;

        $productNames = [];
        $totalQty = 0;
        $totalWeight = 0;

        foreach ($request->products as $product) {

            $productNames[] = $product['product'];

            $qty = $product['quantity'];

            $weight = $product['weight'] ?? 0;

            $totalQty += $qty;

            $totalWeight += ($qty * $weight);
        }

        CallingOrder::create([

            'client_id' => $request->client_id,

            'assigned_to' => $request->assigned_to,

            'order_id' => $orderId,

            'order_date' => $request->date ?? now(),

            'product_name' => implode(', ', $productNames),

            'shopify_product_name' => implode(', ', $productNames),

            'quantity' => $totalQty,

            'weight' => $totalWeight,

            'total_weight' => $totalWeight,

            'customer_name' => $request->customer_name,

            'father_name' => $request->father_name,

            'age' => $request->age,

            'customer_phone' => $request->customer_phone,

            'shipping_address' => trim(
                ($request->shipping_address_line1 ?? '') . ' ' .
                    ($request->shipping_address_line2 ?? '')
            ),

            'city' => $request->city,

            'state' => $request->state,

            'pincode' => $request->shipping_pincode,

            'payment_mode' => $request->payment_mode,

            'amount' => $request->amount,

            'status' => 'verified',

            'order_source' => 'whatsapp',
        ]);
        $productCount = count($request->products);

        return back()->with(
            'success',
            "Order Saved Successfully. {$productCount} products added with total quantity {$totalQty}."
        );
    }

    public function getClientProducts($clientId)
    {
        $products = ClientProduct::where(
            'client_id',
            $clientId
        )
            ->select(
                'id',
                'shopify_product_name',
                'weight_per_unit'
            )
            ->get();

        return response()->json($products);
    }



    public function whstappimportOrders(Request $request)
    {
        $request->validate([
            'client_id'   => 'required',
            'assigned_to' => 'required',
            'file'        => 'required|mimes:xlsx,xls,csv'
        ]);

        Excel::import(
            new BulkOrderImport(
                $request->client_id,
                $request->assigned_to
            ),
            $request->file('file')
        );

        return back()->with(
            'success',
            'Orders Imported Successfully'
        );
    }
}
