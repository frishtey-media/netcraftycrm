<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\ShopifyOrder;
use App\Models\DuplicateOrderLog;

use App\Exports\PostOfficeMultiSheetExport;

use Illuminate\Http\Request;

use Maatwebsite\Excel\Facades\Excel;

use Carbon\Carbon;

use Illuminate\Support\Facades\DB;

class PostOfficeExportController extends Controller
{
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
    | EXPORT POST OFFICE FILE
    |--------------------------------------------------------------------------
    */

    public function export()
    {
        // dd('route hit');
        $duplicates = $this->copyShopifyOrdersToOrders();
        $duplicates = [];
        if (
            !empty($duplicates['duplicate_orders'])
            ||
            !empty($duplicates['duplicate_barcodes'])
        ) {

            return redirect()->back()->with([

                'duplicate_orders' =>
                $duplicates['duplicate_orders'] ?? [],

                'duplicate_barcodes' =>
                $duplicates['duplicate_barcodes'] ?? [],
            ]);
        }

        $query = ShopifyOrder::query();

        if ($this->isClient()) {

            $query->where(
                'client_id',
                $this->clientId()
            );
        }

        $orders = $query
            ->latest()
            ->get();

        if ($orders->isEmpty()) {

            return back()->with(
                'error',
                'No Shopify orders found.'
            );
        }

        $fileName =
            'india_post_' .
            Carbon::now()->format('Y-m-d_H-i-s') .
            '.xlsx';

        return Excel::download(
            new PostOfficeMultiSheetExport($orders),
            $fileName
        );
    }

    /*
    |--------------------------------------------------------------------------
    | COPY SHOPIFY ORDERS
    |--------------------------------------------------------------------------
    */

    private function copyShopifyOrdersToOrders()
    {
        $query = ShopifyOrder::query();

        if ($this->isClient()) {

            $query->where(
                'client_id',
                $this->clientId()
            );
        }

        $shopifyOrders = $query->get();

        if ($shopifyOrders->isEmpty()) {

            return [];
        }

        // Existing DB Data
        $existingOrderIds = Order::pluck(
            'order_id'
        )->toArray();

        $existingBarcodes = Order::whereNotNull('barcode')
            ->pluck('barcode')
            ->toArray();

        // Current Batch Duplicate Check
        $batchOrderIds = [];
        $batchBarcodes = [];

        $duplicateOrderIds = [];
        $duplicateBarcodes = [];

        foreach ($shopifyOrders as $order) {

            // ================= ORDER ID =================

            if (
                in_array(
                    $order->order_id,
                    $existingOrderIds
                )
                ||
                in_array(
                    $order->order_id,
                    $batchOrderIds
                )
            ) {

                $duplicateOrderIds[] =
                    (string) $order->order_id;
            }

            // ================= BARCODE =================

            if (
                !empty($order->barcode)
                &&
                (
                    in_array(
                        $order->barcode,
                        $existingBarcodes
                    )
                    ||
                    in_array(
                        $order->barcode,
                        $batchBarcodes
                    )
                )
            ) {

                $duplicateBarcodes[] =
                    (string) $order->barcode;
            }

            // Store current batch data
            $batchOrderIds[] = $order->order_id;

            if (!empty($order->barcode)) {

                $batchBarcodes[] = $order->barcode;
            }
        }

        // ================= STOP INSERT =================

        if (
            !empty($duplicateOrderIds)
            ||
            !empty($duplicateBarcodes)
        ) {

            return [

                'duplicate_orders' =>
                array_values(
                    array_unique($duplicateOrderIds)
                ),

                'duplicate_barcodes' =>
                array_values(
                    array_unique($duplicateBarcodes)
                ),
            ];
        }

        // ================= INSERT =================

        DB::beginTransaction();

        try {

            foreach ($shopifyOrders as $order) {

                Order::create([

                    'order_id' =>
                    $order->order_id,

                    'client_id' =>
                    $order->client_id,

                    'date' =>
                    $order->order_date ?? now(),

                    'barcode' =>
                    $order->barcode,

                    'payment_mode' =>
                    $order->payment_mode,

                    'amount' =>
                    $order->amount,

                    'age' =>
                    $order->age,

                    'customer_name' =>
                    trim($order->customer_name),

                    'father_name' =>
                    trim($order->father_name),

                    'customer_phone' =>
                    $order->customer_phone,

                    'shipping_address' =>
                    $order->shipping_address,

                    'city' =>
                    $order->city,

                    'state' =>
                    $order->state,

                    'pincode' =>
                    ltrim($order->pincode, "'"),

                    'product' =>
                    $order->shopify_product_name,

                    'quantity' =>
                    $order->quantity,

                    'weight' =>
                    $order->total_weight
                        ?? $order->weight,
                ]);
            }

            DB::commit();
        } catch (\Exception $e) {

            DB::rollBack();

            return [

                'duplicate_orders' => [],

                'duplicate_barcodes' => [
                    'Insert Failed : ' . $e->getMessage()
                ]
            ];
        }

        return [];
    }

    /*
    |--------------------------------------------------------------------------
    | EXPORT SELECTED ORDERS
    |--------------------------------------------------------------------------
    */

    public function postOfficeExcel(Request $request)
    {
        $ids = explode(',', $request->ids);

        $query = Order::whereIn('id', $ids);

        if ($this->isClient()) {

            $query->where(
                'client_id',
                $this->clientId()
            );
        }

        $orders = $query->get();

        if ($orders->isEmpty()) {

            return back()->with(
                'error',
                'No orders found.'
            );
        }

        return Excel::download(
            new PostOfficeMultiSheetExport($orders),
            'india-post-bulk-booking.xlsx'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | CLEAR DUPLICATES
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

        $orders = $query->get();

        DB::beginTransaction();

        try {

            foreach ($orders as $order) {

                DuplicateOrderLog::create([

                    'order_id' =>
                    $order->order_id,

                    'client_id' =>
                    $order->client_id,

                    'barcode' =>
                    $order->barcode,

                    'customer_name' =>
                    $order->customer_name,

                    'customer_phone' =>
                    $order->customer_phone,

                    'shipping_address' =>
                    $order->shipping_address,

                    'city' =>
                    $order->city,

                    'state' =>
                    $order->state,

                    'pincode' =>
                    $order->pincode,

                    'product' =>
                    $order->shopify_product_name,

                    'quantity' =>
                    $order->quantity,

                    'amount' =>
                    $order->amount,

                    'reason' =>
                    'duplicate_order_id',
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
            'Duplicates cleared successfully.'
        );
    }
}
