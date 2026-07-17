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
            'Date',
            'Shopify Order id',
            'Payment Mode',
            'Amount',
            'Customer Name',
            'Father Name',
            'Customer Phone',
            'Shipping address',
            'City',
            'State',
            'Shipping Pincode',
            'Product',
            'Quantity',
            'Weight (in GM)',
            'Age',
            'Client Name',
            'Assigned Staff',
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
                $o->shopify_order_id,
                $o->payment_mode,
                $o->amount,
                $o->customer_name,
                $o->father_name,
                $o->customer_phone,
                $o->shipping_address,
                $o->city,
                $o->state,
                $o->pincode,
                $o->product_name,
                $o->quantity,
                $o->total_weight,
                $o->age,
                optional($o->client)->client_name,

                optional($o->staff)->name,

                $o->created_at,
                $o->updated_at,
            ];
        });
    }
}
