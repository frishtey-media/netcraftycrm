<?php

namespace App\Http\Controllers;

use Maatwebsite\Excel\Excel as ExcelExcel;
use App\Models\Order;
use App\Models\ShopifyOrder;
use App\Exports\PostOfficeExport;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\DuplicateOrderLog;
use App\Exports\PostOfficeMultiSheetExport;

class PostOfficeExportController extends Controller
{
    public function export()
    {
        $duplicates = $this->copyShopifyOrdersToOrders();

        if (!empty($duplicates)) {
            return redirect()->back()->with([
                'duplicate_orders' => array_values($duplicates)
            ]);
        }

        $orders = ShopifyOrder::latest()->get();

        if ($orders->isEmpty()) {
            return redirect()->back()->with('error', 'No Shopify orders found.');
        }

        $fileName = "india_post_" . Carbon::now()->format('Y-m-d_H-i-s') . ".xlsx";

        return Excel::download(
            new PostOfficeMultiSheetExport($orders),
            $fileName
        );
    }

    private function copyShopifyOrdersToOrders()
    {
        $shopifyOrders = ShopifyOrder::all();
        $existingIds = Order::pluck('order_id')->toArray();

        $duplicateOrderIds = [];

        // 🔴 Check duplicates
        foreach ($shopifyOrders as $order) {
            if (in_array($order->order_id, $existingIds)) {
                $duplicateOrderIds[] = (string) $order->order_id;
            }
        }

        if (!empty($duplicateOrderIds)) {
            return array_unique($duplicateOrderIds);
        }

        // ✅ Insert
        foreach ($shopifyOrders as $order) {
            Order::create([
                'order_id'         => $order->order_id,
                'client_id'        => $order->client_id,
                'date'             => $order->order_date ?? now(),
                'barcode'          => $order->barcode,
                'payment_mode'     => $order->payment_mode,
                'amount'           => $order->amount,
                'age'    => $order->age,
                'customer_name'    => trim($order->customer_name),
                'father_name'      => trim($order->father_name),
                'customer_phone'   => $order->customer_phone,
                'shipping_address' => $order->shipping_address,
                'city'             => $order->city,
                'state'            => $order->state,
                'pincode'          => ltrim($order->pincode, "'"),
                'product'          => $order->shopify_product_name,
                'quantity'         => $order->quantity,
                'weight'           => $order->total_weight ?? $order->weight,
            ]);
        }

        return [];
    }

    public function postOfficeExcel(Request $request)
    {
        $ids = explode(',', $request->ids);

        $orders = Order::whereIn('id', $ids)->get();

        if ($orders->isEmpty()) {
            return back()->with('error', 'No orders found.');
        }

        return Excel::download(
            new PostOfficeMultiSheetExport($orders),
            'india-post-bulk-booking.xlsx'
        );
    }

    public function clearDuplicates(Request $request)
    {
        $orderIds = json_decode($request->order_ids, true);

        $orders = ShopifyOrder::whereIn('order_id', $orderIds)->get();

        foreach ($orders as $order) {
            DuplicateOrderLog::create([
                'order_id'        => $order->order_id,
                'client_id'       => $order->client_id,
                'barcode'         => $order->barcode,
                'customer_name'   => $order->customer_name,
                'customer_phone'  => $order->customer_phone,
                'shipping_address' => $order->shipping_address,
                'city'            => $order->city,
                'state'           => $order->state,
                'pincode'         => $order->pincode,
                'product'         => $order->shopify_product_name,
                'quantity'        => $order->quantity,
                'amount'          => $order->amount,
                'reason'          => 'duplicate_order_id',
            ]);
        }

        // Delete from Shopify
        ShopifyOrder::whereIn('order_id', $orderIds)->delete();

        return redirect()->back()->with('success', 'Duplicates cleared! Now download file.');
    }
}
