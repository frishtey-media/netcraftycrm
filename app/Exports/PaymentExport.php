<?php

namespace App\Exports;

use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class PaymentExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    protected $request;

    public function __construct($request)
    {
        $this->request = $request;
    }

    public function collection()
    {
        $query = Payment::query()
            ->leftJoin('orders', 'orders.barcode', '=', 'payments.article_number')
            ->leftJoin('clients', 'clients.id', '=', 'orders.client_id');

        if ($this->request->filled('client_id')) {
            $query->where('orders.client_id', $this->request->client_id);
        }

        if ($this->request->filled('product')) {
            $query->where('orders.product', $this->request->product);
        }

        if ($this->request->filled('from')) {
            $query->whereDate('payments.bill_date', '>=', $this->request->from);
        }

        if ($this->request->filled('to')) {
            $query->whereDate('payments.bill_date', '<=', $this->request->to);
        }

        return $query->select(
            'payments.article_number',
            'orders.order_id',
            'clients.client_name',
            'orders.customer_name',
            'orders.customer_phone',
            'orders.shipping_address',
            'orders.city',
            'orders.state',
            'orders.pincode',
            'orders.product',
            'payments.cod_value',
            'payments.cod_commission',
            'payments.bill_date',
            'payments.delivered_date'
        )->get();
    }

    public function headings(): array
    {
        return [
            'Article Number',
            'Order ID',
            'Client',
            'Customer Name',
            'Customer Phone',
            'Shipping Address',
            'City',
            'State',
            'Pincode',
            'Product',
            'COD Amount',
            'COD Commission',
            'Bill Date',
            'Delivered Date',
        ];
    }
}
