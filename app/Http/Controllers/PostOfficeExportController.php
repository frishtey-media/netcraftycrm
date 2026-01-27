<?php

namespace App\Http\Controllers;

use Maatwebsite\Excel\Excel as ExcelExcel;
use App\Models\Order;
use App\Models\ShopifyOrder;
use App\Exports\PostOfficeExport;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;

class PostOfficeExportController extends Controller
{
    public function export()
    {
        $this->copyShopifyOrdersToOrders();


        $clientId = ShopifyOrder::whereNotNull('client_id')->value('client_id') ?? 'unknown';


        $dateTime = Carbon::now()->format('Y-m-d_H-i-s');


        $fileName = "india_post_client_{$clientId}_{$dateTime}.xlsx";

        return Excel::download(
            new PostOfficeExport($clientId),
            $fileName,
            \Maatwebsite\Excel\Excel::XLSX
        );
    }

    private function copyShopifyOrdersToOrders()
    {
        $shopifyOrders = ShopifyOrder::all();

        foreach ($shopifyOrders as $order) {

            if (Order::where('order_id', $order->order_id)->exists()) {
                continue;
            }

            Order::create([
                'order_id'         => $order->order_id,
                'date'             => $order->order_date ?? Carbon::now(),
                'barcode'          => $order->barcode,
                'payment_mode'     => $order->payment_mode,
                'amount'           => $order->amount,
                'customer_name'    => trim($order->customer_name),
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
    }
}
