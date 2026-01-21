<?php

namespace App\Exports;

use App\Models\ShopifyOrder;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class FinalLabelExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return ShopifyOrder::whereNotNull('barcode')
            ->get()
            ->map(function ($order) {
                return [
                    $order->order_id,
                    $order->order_date,
                    $order->barcode,
                    $order->payment_mode,
                    $order->amount,
                    $order->customer_name,
                    $order->customer_phone,
                    $order->address,
                    $order->city,
                    $order->state,
                    $order->pincode,
                    $order->product_name,
                    $order->quantity,
                    $order->total_weight,
                ];
            });
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
}
