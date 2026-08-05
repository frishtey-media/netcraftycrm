<?php

namespace App\Exports;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class RTODetailExport implements FromCollection, WithHeadings
{
    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function collection()
    {
        $query = DB::table('orders')
            ->leftJoin('callingorder', 'orders.order_id', '=', 'callingorder.order_id')
            ->leftJoin('calling_users', 'callingorder.assigned_to', '=', 'calling_users.id')
            ->leftJoin('clients', 'orders.client_id', '=', 'clients.id')
            ->where('orders.delivery_status', 'RTO');

        // Client Filter
        if ($this->request->client_id) {
            $query->where('orders.client_id', $this->request->client_id);
        }

        // Staff Filter
        if ($this->request->staff_id) {

            if ($this->request->staff_id == 'other') {
                $query->whereNull('callingorder.assigned_to');
            } else {
                $query->where('callingorder.assigned_to', $this->request->staff_id);
            }
        }

        // Date Filter
        if ($this->request->from_date) {
            $query->whereDate('orders.created_at', '>=', $this->request->from_date);
        }

        if ($this->request->to_date) {
            $query->whereDate('orders.created_at', '<=', $this->request->to_date);
        }

        // Status Filter
        switch ($this->request->status) {

            case 'received':
                $query->where('orders.rtorecivedsts', 1);
                break;

            case 'pending':
                $query->where('orders.rtorecivedsts', 0);
                break;

            case 'web':
                $query->whereNull('callingorder.order_source');
                break;

            case 'whatsapp':
                $query->where('callingorder.order_source', 'whatsapp');
                break;

                // total = no extra condition
        }

        return $query
            ->select(
                'orders.order_id',
                'orders.barcode',
                'orders.customer_name',
                'orders.customer_phone',
                'orders.product',
                'orders.quantity',
                'orders.amount',
                'clients.client_name',
                'calling_users.name as staff_name',
                'callingorder.order_source',
                DB::raw("CASE WHEN orders.rtorecivedsts=1 THEN 'Received' ELSE 'Pending' END as rto_status"),
                'orders.rtoreciveddate',
                'orders.created_at'
            )
            ->get();
    }

    public function headings(): array
    {
        return [
            'Order ID',
            'Barcode',
            'Customer Name',
            'Phone',
            'Product',
            'Quantity',
            'Amount',
            'Client',
            'Staff',
            'Source',
            'RTO Status',
            'Received Date',
            'Created At',
        ];
    }
}
