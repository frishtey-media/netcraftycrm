<?php

namespace App\Exports;

use App\Models\ShopifyOrder;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class FinalLabelExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection()
    {
        return ShopifyOrder::all();
    }

    public function headings(): array
    {
        return [
            'Order ID',
            'Date',
            'Barcode',
            '*Payment Mode',
            'Amount',
            '*Customer Name',
            '*Customer Father Name',
            '*Customer Phone',
            '*Shipping Address Line1',
            'City',
            'State',
            '*Shipping Pincode',
            'Product',
            'Quantity',
            'Weight (in GM)',
        ];
    }

    public function map($order): array
    {
        return [
            $order->order_id,
            optional($order->created_at)->format('d-m-Y'),
            $order->barcode,
            'COD',
            $order->amount,
            $order->customer_name,
            $order->father_name,
            $order->customer_phone,
            $order->shipping_address,
            $order->city,
            $order->state,
            $order->pincode,
            $order->shopify_product_name,
            $order->quantity,
            $order->total_weight,
        ];
    }
}
