<?php

namespace App\Exports;

use App\Models\Order;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class RTOOrdersExport implements FromCollection, WithHeadings
{
    protected $orderIds;

    public function __construct($orderIds)
    {
        $this->orderIds = $orderIds;
    }

    public function collection()
    {
        return Order::whereIn('id', $this->orderIds)
            ->select(
                'order_id',
                'barcode',
                'customer_name',
                'customer_phone',
                'shipping_address',
                'payment_mode',
                'amount',
                'product',
                'quantity',
                'weight',
                'date'
            )
            ->get();
    }

    public function headings(): array
    {
        return [
            'Order ID',
            'Barcode',
            'Customer Name',
            'Customer Phone',
            'Shipping Address',
            'Payment Mode',
            'Amount',
            'Product',
            'Quantity',
            'Weight',
            'Date',
        ];
    }
}
