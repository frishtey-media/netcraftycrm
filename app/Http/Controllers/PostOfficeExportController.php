<?php

namespace App\Http\Controllers;

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

        return Excel::download(
            new PostOfficeExport,
            'india_post_office_format.xlsx'
        );
    }

    private function copyShopifyOrdersToOrders()
    {
        $shopifyOrders = ShopifyOrder::all();

        foreach ($shopifyOrders as $order) {


            $exists = Order::where('order_id', $order->order_id)->exists();
            if ($exists) {
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
