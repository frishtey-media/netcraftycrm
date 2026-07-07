<?php

namespace App\Exports;

use App\Models\Order;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class SelectedOrdersExport implements FromCollection, WithHeadings
{
    protected $ids;

    public function __construct($ids)
    {
        $this->ids = $ids;
    }

    public function collection()
    {
        return Order::with('callingOrder.staff')
            ->whereIn('id', $this->ids)
            ->get()
            ->map(function ($order) {

                return [
                    'order_id' => $order->order_id,
                    'staff_name' => optional(optional($order->callingOrder)->staff)->name,
                    'barcode' => $order->barcode,
                    'customer_name' => $order->customer_name,
                    'customer_phone' => $order->customer_phone,
                    'shipping_address' => $order->shipping_address,
                    'city' => $order->city,
                    'state' => $order->state,
                    'pincode' => $order->pincode,
                    'payment_mode' => $order->payment_mode,
                    'amount' => $order->amount,
                    'product' => $order->product,
                    'quantity' => $order->quantity,
                    'weight' => $order->weight,
                    'delivery_status' => $order->delivery_status,
                    'delivery_remarks' => $order->delivery_remarks,
                    'date' => $order->date,
                ];
            });
    }

    public function headings(): array
    {
        return [
            'Order ID',
            'Staff Name',
            'Barcode',
            'Customer Name',
            'Customer Phone',
            'Shipping Address',
            'City',
            'State',
            'Pincode',
            'Payment Mode',
            'Amount',
            'Product',
            'Quantity',
            'Weight',
            'Delivery Status',
            'Delivery Remarks',
            'Date'
        ];
    }
}
