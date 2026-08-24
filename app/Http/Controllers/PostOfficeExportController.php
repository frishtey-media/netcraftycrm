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
        ini_set('memory_limit', '1024M');
        ini_set('max_execution_time', 300);

        /*
    |--------------------------------------------------------------------------
    | VALIDATE IMPORT DATE
    |--------------------------------------------------------------------------
    */

        $request->validate([
            'import_date' => 'required|date',
        ]);

        $importDate = $request->import_date;

        /*
    |--------------------------------------------------------------------------
    | COPY SHOPIFY ORDERS TO MAIN ORDERS TABLE
    |--------------------------------------------------------------------------
    |
    | shopify_orders = temporary table
    | orders         = permanent/main table
    |
    */

        $duplicates = $this->copyShopifyOrdersToOrders(
            $importDate
        );

        /*
    |--------------------------------------------------------------------------
    | STOP ON DUPLICATES
    |--------------------------------------------------------------------------
    |
    | IMPORTANT:
    | If duplicate orders/barcodes are found,
    | DO NOT DELETE shopify_orders.
    |
    */

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
    | GET SHOPIFY ORDERS
    |--------------------------------------------------------------------------
    */

        $query = ShopifyOrder::query();

        /*
    |--------------------------------------------------------------------------
    | CLIENT SECURITY
    |--------------------------------------------------------------------------
    */

        if ($this->isClient()) {
            $query->where(
                'client_id',
                $this->clientId()
            );
        }

        /*
    |--------------------------------------------------------------------------
    | SELECTED IMPORT DATE
    |--------------------------------------------------------------------------
    */

        $query->whereDate(
            'created_at',
            $importDate
        );

        $orders = $query
            ->orderBy('id')
            ->get();

        /*
    |--------------------------------------------------------------------------
    | NO ORDERS FOUND
    |--------------------------------------------------------------------------
    */

        if ($orders->isEmpty()) {
            return back()->with(
                'error',
                'No orders found for selected Import Date.'
            );
        }

        /*
    |--------------------------------------------------------------------------
    | GENERATE INDIA POST EXCEL
    |--------------------------------------------------------------------------
    |
    | Excel is generated in memory first.
    | We will clear the temporary table ONLY
    | after Excel generation succeeds.
    |
    */

        try {

            $excelContent = Excel::raw(
                new PostOfficeMultiSheetExport($orders),
                \Maatwebsite\Excel\Excel::XLSX
            );
        } catch (\Throwable $e) {

            /*
        |--------------------------------------------------------------------------
        | EXCEL GENERATION FAILED
        |--------------------------------------------------------------------------
        |
        | DO NOT DELETE TEMP DATA.
        |
        */

            return back()->with(
                'error',
                'India Post Excel could not be generated: ' .
                    $e->getMessage()
            );
        }

        /*
    |--------------------------------------------------------------------------
    | MARK INDIA POST EXPORT COMPLETED
    |--------------------------------------------------------------------------
    */

        ShopifyOrder::whereDate(
            'created_at',
            $importDate
        )
            ->when(
                $this->isClient(),
                function ($q) {
                    $q->where(
                        'client_id',
                        $this->clientId()
                    );
                }
            )
            ->update([
                'postoffice_exported' => 1,
            ]);

        /*
    |--------------------------------------------------------------------------
    | CLEAR TEMP SHOPIFY ORDERS
    |--------------------------------------------------------------------------
    |
    | At this point:
    |
    | 1. Orders copied successfully to orders table
    | 2. No duplicates found
    | 3. India Post Excel generated successfully
    |
    | Therefore it is now safe to clear ONLY
    | this import date from shopify_orders.
    |
    */

        ShopifyOrder::whereDate(
            'created_at',
            $importDate
        )
            ->when(
                $this->isClient(),
                function ($q) {
                    $q->where(
                        'client_id',
                        $this->clientId()
                    );
                }
            )
            ->delete();

        /*
    |--------------------------------------------------------------------------
    | DOWNLOAD INDIA POST EXCEL
    |--------------------------------------------------------------------------
    */

        $fileName =
            'india_post_' .
            now()->format('YmdHis') .
            '.xlsx';

        return response(
            $excelContent,
            200,
            [
                'Content-Type' =>
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',

                'Content-Disposition' =>
                'attachment; filename="' . $fileName . '"',

                'Content-Length' =>
                strlen($excelContent),

                'Cache-Control' =>
                'max-age=0, no-cache, no-store, must-revalidate',

                'Pragma' =>
                'public',
            ]
        );
    }

    /*
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

                "https://{$client->shopify_store_url}/admin/api/2025-10/fulfillments.json",

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

            "https://{$client->shopify_store_url}/admin/api/2025-10/orders/{$shopifyOrderId}/fulfillment_orders.json"

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

            "https://{$client->shopify_store_url}/admin/api/2025-10/fulfillments.json",

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

            "https://{$client->shopify_store_url}/admin/api/2025-10/fulfillments/{$fulfillmentId}/update_tracking.json",

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

            "https://{$client->shopify_store_url}/admin/api/2025-10/fulfillments/{$fulfillmentId}/cancel.json"

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
