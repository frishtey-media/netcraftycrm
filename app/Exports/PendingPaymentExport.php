<?php

namespace App\Exports;

use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PendingPaymentExport implements FromCollection, WithHeadings
{
    protected $request;

    public function __construct($request)
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

            ->where('orders.payment_mode', 'COD')   // Only COD Orders

            ->where(
                'orders.delivery_status',
                'Delivered'
            )

            ->where(function ($q) {

                $q->whereNull('orders.recivedpaysts')

                    ->orWhere(
                        'orders.recivedpaysts',
                        0
                    );
            });

        /*
        |--------------------------------------------------------------------------
        | Client Login
        |--------------------------------------------------------------------------
        */

        if (
            Auth::check() &&
            Auth::user()->role == 'client'
        ) {

            $query->where(
                'orders.client_id',
                Auth::user()->client_id
            );
        } else {

            if ($this->request->filled('client_id')) {

                $query->where(
                    'orders.client_id',
                    $this->request->client_id
                );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Date Filter
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
        | Web / WhatsApp
        |--------------------------------------------------------------------------
        */

        if ($this->request->filled('order_source')) {

            if (
                $this->request->order_source == 'web'
            ) {

                $query->whereNull(
                    'orders.order_source'
                );
            } else {

                $query->where(
                    'orders.order_source',
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

            $search = trim(
                $this->request->search
            );

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

        return $query->select(

            'orders.order_id',

            'orders.barcode',

            'clients.client_name',

            'orders.customer_name',

            'orders.customer_phone',

            'orders.shipping_address',

            'orders.city',

            'orders.state',

            'orders.pincode',

            'orders.product',

            'orders.quantity',

            'orders.weight',

            'orders.amount',

            'orders.payment_mode',

            'orders.delivery_date',

            'orders.delivery_status'

        )->get();
    }

    public function headings(): array
    {
        return [

            'Order ID',

            'Barcode',

            'Client',

            'Customer',

            'Phone',

            'Address',

            'City',

            'State',

            'Pincode',

            'Product',

            'Qty',

            'Weight',

            'Amount',

            'Payment Mode',

            'Delivery Date',

            'Delivery Status',

        ];
    }
}
