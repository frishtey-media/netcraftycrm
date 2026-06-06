<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class DeliveryReportExport implements FromCollection, WithHeadings
{
    protected $orders;

    public function __construct($orders)
    {
        $this->orders = $orders;
    }

    public function headings(): array
    {
        return [
            'Order ID',
            'Client',
            'Tracking No',
            'Customer',
            'Phone',
            'City',
            'State',
            'Status',
            'Payment Status',
            'RTO Status',
            'Received COD',
            'Bill Date',

        ];
    }

    public function collection()
    {
        return $this->orders->map(function ($order) {
            return [
                $order->order_id,
                $order->client->client_name ?? '',
                $order->barcode,
                $order->customer_name,
                $order->customer_phone,
                $order->city,
                $order->state,
                $order->delivery_status,
                $order->recivedpaysts ? 'Received' : 'Pending',
                $order->rtorecivedsts ? 'Received' : 'Pending',
                $order->receivedcodamt,
                $order->pay_bill_date,
            ];
        });
    }
}
