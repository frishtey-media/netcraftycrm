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

class RecordController extends Controller
{

    public function create()
    {
        $orderId = 'ORD-' . now()->format('Ymd') . '-' . strtoupper(Str::random(3));
        $barcodes = Barcode::where('is_used', 0)->pluck('barcode');
        $clients = Client::orderBy('client_name')->get();
        return view('record.create', compact('orderId', 'barcodes', 'clients'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'client_id'     => 'required|exists:clients,id',
            'payment_mode'  => 'required',
            'amount'        => 'required|numeric',
            'customer_name' => 'required',
        ]);

        try {
            DB::transaction(function () use ($request) {


                $barcode = Barcode::where('client_id', $request->client_id)
                    ->where('is_used', 0)
                    ->orderBy('id', 'asc')
                    ->lockForUpdate()
                    ->first();

                if (!$barcode) {
                    throw new \Exception('No unused barcode available for selected client');
                }


                ShopifyOrder::create([
                    'client_id'        => $request->client_id,
                    'order_id'         => $request->order_id,
                    'order_date'       => $request->date,

                    'product_name'     => $request->product,
                    'shopify_product_name' => $request->product,

                    'quantity'         => $request->quantity ?? 1,
                    'weight'           => $request->weight_in_gm ?? 0,
                    'total_weight'     => ($request->quantity ?? 1) * ($request->weight_in_gm ?? 0),


                    'barcode'          => $barcode->barcode,

                    'customer_name'    => $request->customer_name,
                    'father_name'      => $request->father_name,
                    'customer_phone'   => $request->customer_phone,

                    'shipping_address' => trim(
                        ($request->shipping_address_line1 ?? '') . ' ' .
                            ($request->shipping_address_line2 ?? '')
                    ),

                    'city'             => $request->city,
                    'state'            => $request->state,
                    'pincode'          => $request->shipping_pincode,

                    'payment_mode'     => $request->payment_mode,
                    'amount'           => $request->amount,
                ]);


                $barcode->update([
                    'is_used' => 1
                ]);
            });
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->withErrors([
                    'client_id' => $e->getMessage()
                ]);
        }

        return back()->with('success', 'Order saved & barcode auto-assigned');
    }


    public function getClientProducts($clientId)
    {
        $products = ClientProduct::where('client_id', $clientId)
            ->select('id', 'shopify_product_name', 'weight_per_unit')
            ->get();

        return response()->json($products);
    }
}
