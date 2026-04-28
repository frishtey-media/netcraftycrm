<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class VerifiedOrdersExport implements FromCollection, WithHeadings
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
            'Order Date',
            'Customer Name',
            'Phone',
            'Father Name',
            'Product',
            'Quantity',
            'Address',
            'City',
            'State',
            'Pincode',
            'Payment Mode',
            'Amount',
            'Client ID',
            'Assigned To',
            'Created At',
            'Updated At'
        ];
    }


    public function collection()
    {
        return $this->orders->map(function ($o) {

            return [
                $o->order_id,
                $o->order_date,

                $o->customer_name,
                $o->customer_phone,
                $o->father_name,

                $o->product_name,
                $o->quantity,

                $o->shipping_address,
                $o->city,
                $o->state,
                $o->pincode,

                $o->payment_mode,
                $o->amount,

                $o->client_id,
                $o->assigned_to,

                $o->created_at,
                $o->updated_at,
            ];
        });
    }
}
