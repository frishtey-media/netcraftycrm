<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class OrdersReportExport implements FromCollection, WithHeadings
{
    protected $orders;

    public function __construct(Collection $orders)
    {
        $this->orders = $orders;
    }

    public function collection()
    {
        return $this->orders->map(function ($order) {

            return [

                'Order ID'   => $order->order_id,
                'Barcode'    => $order->barcode,
                'Client'     => optional($order->client)->client_name,
                'Customer'   => $order->customer_name,
                'Phone'      => $order->customer_phone,
                'Product'    => $order->product,
                'Status'     => $order->delivery_status,

            ];
        });
    }

    public function headings(): array
    {
        return [

            'Order ID',
            'Barcode',
            'Client',
            'Customer',
            'Phone',
            'Product',
            'Status',

        ];
    }
}
