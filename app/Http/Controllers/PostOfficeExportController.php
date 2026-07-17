<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\ShopifyOrder;
use App\Models\DuplicateOrderLog;
use App\Exports\PostOfficeMultiSheetExport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;
use App\Models\Client;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PostOfficeExportController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Client Helpers
    |--------------------------------------------------------------------------
    */

    private function isClient()
    {
        return auth()->check()
            && auth()->user()->role === 'client';
    }

    private function clientId()
    {
        return auth()->user()->client_id;
    }

    /*
    |--------------------------------------------------------------------------
    | Export Post Office Excel
    |--------------------------------------------------------------------------
    */

    public function export(Request $request)
    {

        //  dd('Export method reached');
        $request->validate([
            'import_date' => 'required|date',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Copy Shopify Orders to Orders Table
        |--------------------------------------------------------------------------
        */

        $duplicates = $this->copyShopifyOrdersToOrders(
            $request->import_date
        );
        //dd($duplicates);
        if (
            !empty($duplicates['duplicate_orders']) ||
            !empty($duplicates['duplicate_barcodes'])
        ) {

            return back()->with([
                'duplicate_orders'   => $duplicates['duplicate_orders'],
                'duplicate_barcodes' => $duplicates['duplicate_barcodes'],
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Shopify Orders
        |--------------------------------------------------------------------------
        */

        $query = ShopifyOrder::query();

        if ($this->isClient()) {

            $query->where(
                'client_id',
                $this->clientId()
            );
        }

        $query->whereDate(
            'created_at',
            $request->import_date
        );

        $orders = $query
            ->orderBy('id')
            ->get();

        if ($orders->isEmpty()) {

            return back()->with(
                'error',
                'No orders found for selected Import Date.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Show Label Button
        |--------------------------------------------------------------------------
        */

        session([
            'show_label' => true
        ]);

        /*
        |--------------------------------------------------------------------------
        | Export Excel
        |--------------------------------------------------------------------------
        */
        ShopifyOrder::whereDate('created_at', $request->import_date)
            ->when($this->isClient(), function ($q) {
                $q->where('client_id', $this->clientId());
            })
            ->update([
                'postoffice_exported' => 1
            ]);

        return Excel::download(
            new PostOfficeMultiSheetExport($orders),
            'india_post_' . now()->format('YmdHis') . '.xlsx'
        );
    }/*
|--------------------------------------------------------------------------
| COPY SHOPIFY ORDERS TO ORDERS TABLE
|--------------------------------------------------------------------------
*/

    private function copyShopifyOrdersToOrders($importDate)
    {
        /*
    |--------------------------------------------------------------------------
    | Shopify Orders
    |--------------------------------------------------------------------------
    */

        $query = ShopifyOrder::query();

        if ($this->isClient()) {

            $query->where(
                'client_id',
                $this->clientId()
            );
        }

        $query->whereDate(
            'created_at',
            $importDate
        );

        $shopifyOrders = $query->get();

        if ($shopifyOrders->isEmpty()) {

            return [
                'duplicate_orders' => [],
                'duplicate_barcodes' => [],
            ];
        }

        /*
    |--------------------------------------------------------------------------
    | Existing Order IDs
    |--------------------------------------------------------------------------
    */

        $existingOrderIds = Order::whereIn(
            'order_id',
            $shopifyOrders->pluck('order_id')->unique()
        )
            ->pluck('order_id')
            ->flip();

        /*
    |--------------------------------------------------------------------------
    | Existing Barcodes
    |--------------------------------------------------------------------------
    */

        $existingBarcodes = Order::whereIn(
            'barcode',
            $shopifyOrders
                ->pluck('barcode')
                ->filter()
        )
            ->pluck('barcode')
            ->toArray();

        $duplicateOrderIds = [];
        $duplicateBarcodes = [];

        DB::beginTransaction();

        try {

            foreach ($shopifyOrders as $order) {

                /*
            |--------------------------------------------------------------------------
            | Duplicate Order ID
            |--------------------------------------------------------------------------
            */

                if (isset($existingOrderIds[$order->order_id])) {

                    $duplicateOrderIds[] = $order->order_id;

                    continue;
                }

                /*
            |--------------------------------------------------------------------------
            | Duplicate Barcode
            |--------------------------------------------------------------------------
            */

                if (
                    !empty($order->barcode) &&
                    isset($existingBarcodes[$order->barcode])
                ) {

                    $duplicateBarcodes[] = $order->barcode;

                    continue;
                }

                /*
            |--------------------------------------------------------------------------
            | Insert Order
            |--------------------------------------------------------------------------
            */

                $newOrder = Order::create([

                    'order_id'          => $order->order_id,

                    'client_id'         => $order->client_id,
                    'date'              => $order->order_date,
                    'barcode'           => $order->barcode,
                    'payment_mode'      => $order->payment_mode,
                    'amount'            => $order->amount,
                    'customer_name'     => $order->customer_name,
                    'father_name'       => $order->father_name,
                    'customer_phone'    => $order->customer_phone,
                    'shipping_address'  => $order->shipping_address,
                    'city'              => $order->city,
                    'state'             => $order->state,
                    'pincode'           => ltrim($order->pincode, "'"),
                    'product'           => $order->shopify_product_name,
                    'quantity'          => $order->quantity,
                    'weight'            => $order->total_weight ?? $order->weight,

                    // IMPORTANT
                    'shopify_order_id'  => $order->shopify_order_id,

                    'created_at'        => $order->created_at,
                    'updated_at'        => $order->updated_at,

                ]);


                $this->shopifyFulfillment($newOrder);
            }

            DB::commit();
        } catch (\Exception $e) {

            DB::rollBack();

            return [

                'duplicate_orders' => [],

                'duplicate_barcodes' => [

                    $e->getMessage()

                ]

            ];
        }

        return [

            'duplicate_orders' => array_unique($duplicateOrderIds),

            'duplicate_barcodes' => array_unique($duplicateBarcodes),

        ];
    }
    /*
|--------------------------------------------------------------------------
| COPY SHOPIFY ORDERS TO ORDERS TABLE
|--------------------------------------------------------------------------
*/
    private function shopifyFulfillment(Order $order)
    {
        if (empty($order->barcode)) {
            return false;
        }

        $client = Client::find($order->client_id);

        if (!$client) {
            return false;
        }

        try {

            $fulfillmentOrderId = $this->getFulfillmentOrderId(
                $client,
                $order->shopify_order_id
            );

            $response = Http::withHeaders([

                'X-Shopify-Access-Token' => $client->shopify_access_token,

                'Content-Type' => 'application/json',

            ])->post(

                "https://{$client->shopify_store_url}/admin/api/2024-04/fulfillments.json",

                [

                    "fulfillment" => [

                        "line_items_by_fulfillment_order" => [

                            [

                                "fulfillment_order_id" => $fulfillmentOrderId

                            ]

                        ],

                        "tracking_info" => [

                            "number" => $order->barcode,

                            "company" => "India Post"

                        ],

                        "notify_customer" => true

                    ]

                ]

            );

            if (!$response->successful()) {

                Log::error(

                    "Shopify Fulfillment Failed",

                    [

                        'order_id' => $order->order_id,

                        'response' => $response->body()

                    ]

                );

                return false;
            }

            return true;
        } catch (\Exception $e) {

            Log::error(

                "Shopify Fulfillment Exception",

                [

                    'order_id' => $order->order_id,

                    'message' => $e->getMessage()

                ]

            );

            return false;
        }
    }

    private function getFulfillmentOrderId(Client $client, $shopifyOrderId)
    {
        $response = Http::withHeaders([

            'X-Shopify-Access-Token' => $client->shopify_access_token,

            'Content-Type' => 'application/json'

        ])->get(

            "https://{$client->shopify_store_url}/admin/api/2024-04/orders/{$shopifyOrderId}/fulfillment_orders.json"

        );

        if (!$response->successful()) {

            throw new \Exception($response->body());
        }

        $data = $response->json();

        if (empty($data['fulfillment_orders'])) {

            throw new \Exception("Fulfillment Order Not Found");
        }

        return $data['fulfillment_orders'][0]['id'];
    }
    /*
|--------------------------------------------------------------------------
| EXPORT SELECTED ORDERS
|--------------------------------------------------------------------------
*/

    public function fulfillOrder(Order $order)
    {
        $client = Client::findOrFail($order->client_id);

        if (empty($order->barcode)) {

            return false;
        }

        $fulfillmentOrderId = $this->getFulfillmentOrderId(
            $client,
            $order->shopify_order_id
        );

        $response = Http::withHeaders([

            'X-Shopify-Access-Token' => $client->shopify_access_token,

            'Content-Type' => 'application/json'

        ])->post(

            "https://{$client->shopify_store_url}/admin/api/2024-04/fulfillments.json",

            [

                "fulfillment" => [

                    "line_items_by_fulfillment_order" => [

                        [

                            "fulfillment_order_id" => $fulfillmentOrderId

                        ]

                    ],

                    "tracking_info" => [

                        "number" => $order->barcode,

                        "company" => "India Post"

                    ],

                    "notify_customer" => true

                ]

            ]

        );

        if (!$response->successful()) {

            throw new \Exception(

                "Fulfillment Failed : "

                    . $response->body()

            );
        }

        return $response->json();
    }


    public function updateTracking($fulfillmentId, Order $order)
    {
        $client = Client::findOrFail($order->client_id);

        $response = Http::withHeaders([

            'X-Shopify-Access-Token' => $client->shopify_access_token,

            'Content-Type' => 'application/json'

        ])->put(

            "https://{$client->shopify_store_url}/admin/api/2024-04/fulfillments/{$fulfillmentId}/update_tracking.json",

            [

                "fulfillment" => [

                    "notify_customer" => true,

                    "tracking_info" => [

                        "number" => $order->barcode,

                        "company" => "India Post"

                    ]

                ]

            ]

        );

        if (!$response->successful()) {

            throw new \Exception(

                "Tracking Update Failed"

            );
        }

        return $response->json();
    }


    public function cancelFulfillment($fulfillmentId, Client $client)
    {
        $response = Http::withHeaders([

            'X-Shopify-Access-Token' => $client->shopify_access_token,

            'Content-Type' => 'application/json'

        ])->post(

            "https://{$client->shopify_store_url}/admin/api/2024-04/fulfillments/{$fulfillmentId}/cancel.json"

        );

        if (!$response->successful()) {

            throw new \Exception(

                "Cancel Failed"

            );
        }

        return true;
    }

    public function postOfficeExcel(Request $request)
    {
        $request->validate([
            'ids' => 'required'
        ]);

        $ids = explode(',', $request->ids);

        $query = Order::whereIn('id', $ids);

        if ($this->isClient()) {

            $query->where(
                'client_id',
                $this->clientId()
            );
        }

        if ($request->filled('import_date')) {

            $query->whereDate(
                'created_at',
                $request->import_date
            );
        }

        $orders = $query
            ->orderBy('id')
            ->get();

        if ($orders->isEmpty()) {

            return back()->with(
                'error',
                'No orders found.'
            );
        }

        return Excel::download(
            new PostOfficeMultiSheetExport($orders),
            'india_post_selected_' .
                now()->format('YmdHis') .
                '.xlsx'
        );
    }
    /*
|--------------------------------------------------------------------------
| CLEAR DUPLICATE ORDERS
|--------------------------------------------------------------------------
*/

    public function clearDuplicates(Request $request)
    {
        $orderIds = json_decode(
            $request->order_ids,
            true
        );

        if (empty($orderIds)) {

            return back()->with(
                'error',
                'No duplicate orders found.'
            );
        }

        $query = ShopifyOrder::whereIn(
            'order_id',
            $orderIds
        );

        if ($this->isClient()) {

            $query->where(
                'client_id',
                $this->clientId()
            );
        }

        if ($request->filled('import_date')) {

            $query->whereDate(
                'created_at',
                $request->import_date
            );
        }

        $orders = $query->get();

        DB::beginTransaction();

        try {

            foreach ($orders as $order) {

                DuplicateOrderLog::create([

                    'order_id'         => $order->order_id,

                    'client_id'        => $order->client_id,

                    'barcode'          => $order->barcode,

                    'customer_name'    => $order->customer_name,

                    'customer_phone'   => $order->customer_phone,

                    'shipping_address' => $order->shipping_address,

                    'city'             => $order->city,

                    'state'            => $order->state,

                    'pincode'          => $order->pincode,

                    'product'          => $order->shopify_product_name,

                    'quantity'         => $order->quantity,

                    'amount'           => $order->amount,

                    'reason'           => 'duplicate_order_id',

                    'created_at'       => now(),

                    'updated_at'       => now(),

                ]);
            }

            ShopifyOrder::whereIn(
                'order_id',
                $orderIds
            )->delete();

            DB::commit();
        } catch (\Exception $e) {

            DB::rollBack();

            return back()->with(
                'error',
                $e->getMessage()
            );
        }

        return back()->with(
            'success',
            'Duplicate orders cleared successfully.'
        );
    }
}
