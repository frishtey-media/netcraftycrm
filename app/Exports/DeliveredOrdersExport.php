<?php

namespace App\Exports;

use App\Models\Order;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class DeliveredOrdersExport implements FromCollection, WithHeadings
{
    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function collection()
    {
        $query = Order::query()

            ->leftJoin(
                'clients',
                'clients.id',
                '=',
                'orders.client_id'
            )

            ->leftJoin(
                'callingorder',
                'callingorder.order_id',
                '=',
                'orders.order_id'
            )

            ->leftJoin(
                'calling_users',
                'calling_users.id',
                '=',
                'callingorder.assigned_to'
            )

            ->where(
                'orders.delivery_status',
                'Delivered'
            );

        /*
        |--------------------------------------------------------------------------
        | Client
        |--------------------------------------------------------------------------
        */

        if ($this->request->filled('client_id')) {

            $query->where(
                'orders.client_id',
                $this->request->client_id
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Staff
        |--------------------------------------------------------------------------
        */

        if ($this->request->filled('staff_id')) {

            if ($this->request->staff_id == 'other') {

                $query->whereNull(
                    'callingorder.assigned_to'
                );
            } else {

                $query->where(
                    'callingorder.assigned_to',
                    $this->request->staff_id
                );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Product
        |--------------------------------------------------------------------------
        */

        if ($this->request->filled('product')) {

            $query->where(
                'orders.product',
                $this->request->product
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Delivery Date
        |--------------------------------------------------------------------------
        */

        if ($this->request->filled('from')) {

            $query->whereDate(
                'orders.delivery_date',
                '>=',
                $this->request->from
            );
        }

        if ($this->request->filled('to')) {

            $query->whereDate(
                'orders.delivery_date',
                '<=',
                $this->request->to
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Order Source
        |--------------------------------------------------------------------------
        */

        if ($this->request->filled('order_source')) {

            if ($this->request->order_source == 'web') {

                $query->whereNull(
                    'callingorder.order_source'
                );
            } else {

                $query->where(
                    'callingorder.order_source',
                    'whatsapp'
                );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Search
        |--------------------------------------------------------------------------
        */

        if ($this->request->filled('search')) {

            $search = trim($this->request->search);

            $query->where(function ($q) use ($search) {

                $q->where(
                    'orders.order_id',
                    'like',
                    "%{$search}%"
                )

                    ->orWhere(
                        'orders.barcode',
                        'like',
                        "%{$search}%"
                    )

                    ->orWhere(
                        'orders.customer_name',
                        'like',
                        "%{$search}%"
                    )

                    ->orWhere(
                        'orders.customer_phone',
                        'like',
                        "%{$search}%"
                    );
            });
        }

        return $query

            ->select(

                'orders.delivery_date',

                'clients.client_name',

                'calling_users.name as staff',

                'orders.order_id',

                'orders.barcode',

                'orders.customer_name',

                'orders.customer_phone',

                'orders.product',

                'orders.quantity',

                'orders.amount',

                'callingorder.order_source',

                'orders.delivery_status'

            )

            ->orderBy(
                'orders.delivery_date',
                'desc'
            )

            ->get()

            ->map(function ($row) {

                return [

                    'Delivery Date' => $row->delivery_date,

                    'Client' => $row->client_name,

                    'Staff' => $row->staff ?? 'Other',

                    'Order ID' => $row->order_id,

                    'Barcode' => $row->barcode,

                    'Customer' => $row->customer_name,

                    'Phone' => $row->customer_phone,

                    'Product' => $row->product,

                    'Quantity' => $row->quantity,

                    'Amount' => $row->amount,

                    'Source' => $row->order_source == 'whatsapp'
                        ? 'WhatsApp'
                        : 'Web',

                    'Status' => $row->delivery_status,

                ];
            });
    }

    public function headings(): array
    {
        return [

            'Delivery Date',

            'Client',

            'Staff',

            'Order ID',

            'Barcode',

            'Customer',

            'Phone',

            'Product',

            'Quantity',

            'Amount',

            'Source',

            'Status'

        ];
    }
}
