<?php

namespace App\Exports;

use App\Models\CallingOrder;
use Carbon\Carbon;

use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class StaffPerformanceOrdersExport implements
    FromQuery,
    WithHeadings,
    WithMapping,
    ShouldAutoSize
{
    protected $request;


    public function __construct($request)
    {
        $this->request = $request;
    }


    public function query()
    {
        $request = $this->request;


        /*
        |--------------------------------------------------------------------------
        | Dates
        |--------------------------------------------------------------------------
        */
        $from = $request->filled('from')
            ? Carbon::parse($request->from)->startOfDay()
            : Carbon::today()->startOfDay();

        $to = $request->filled('to')
            ? Carbon::parse($request->to)->endOfDay()
            : Carbon::today()->endOfDay();


        /*
        |--------------------------------------------------------------------------
        | Base Query
        |--------------------------------------------------------------------------
        */
        $query = CallingOrder::query()
            ->with('client')
            ->where(
                'assigned_to',
                $request->staff_id
            )
            ->whereBetween(
                'updated_at',
                [$from, $to]
            );


        /*
        |--------------------------------------------------------------------------
        | Client
        |--------------------------------------------------------------------------
        */
        if ($request->filled('client_id')) {

            $query->where(
                'client_id',
                $request->client_id
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Source
        |--------------------------------------------------------------------------
        */
        if ($request->filled('source')) {

            if ($request->source === 'whatsapp') {

                $query->whereRaw(
                    'LOWER(order_source) = ?',
                    ['whatsapp']
                );
            } elseif ($request->source === 'web') {

                $query->where(function ($q) {

                    $q->whereNull('order_source')
                        ->orWhere('order_source', '')
                        ->orWhereRaw(
                            'LOWER(order_source) = ?',
                            ['web']
                        );
                });
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Payment
        |--------------------------------------------------------------------------
        */
        if ($request->filled('payment_mode')) {

            if ($request->payment_mode === 'cod') {

                $query->where(function ($q) {

                    $q->whereRaw(
                        'LOWER(payment_mode) = ?',
                        ['cod']
                    )
                        ->orWhereRaw(
                            'LOWER(payment_mode) = ?',
                            ['vpp']
                        );
                });
            } elseif ($request->payment_mode === 'prepaid') {

                $query->where(function ($q) {

                    $q->whereRaw(
                        'LOWER(payment_mode) = ?',
                        ['prepaid']
                    )
                        ->orWhereRaw(
                            'LOWER(payment_mode) = ?',
                            ['paid']
                        );
                });
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Status
        |--------------------------------------------------------------------------
        */
        if ($request->filled('status')) {

            if ($request->status === 'other') {

                $query->where(function ($q) {

                    $q->whereNull('status')
                        ->orWhere('status', '')
                        ->orWhereNotIn('status', [
                            'pending',
                            'verified',
                            'cancel',
                            'not_reachable',
                            'same_order'
                        ]);
                });
            } else {

                $query->where(
                    'status',
                    $request->status
                );
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Search
        |--------------------------------------------------------------------------
        */
        if ($request->filled('search')) {

            $search = trim($request->search);

            $query->where(function ($q) use ($search) {

                $q->where(
                    'order_id',
                    'LIKE',
                    "%{$search}%"
                )
                    ->orWhere(
                        'customer_name',
                        'LIKE',
                        "%{$search}%"
                    )
                    ->orWhere(
                        'customer_phone',
                        'LIKE',
                        "%{$search}%"
                    )
                    ->orWhere(
                        'product_name',
                        'LIKE',
                        "%{$search}%"
                    )
                    ->orWhere(
                        'remarks',
                        'LIKE',
                        "%{$search}%"
                    );
            });
        }


        return $query->orderByDesc('updated_at');
    }


    /*
    |--------------------------------------------------------------------------
    | Excel Headings
    |--------------------------------------------------------------------------
    */
    public function headings(): array
    {
        return [
            'Order ID',
            'Client',
            'Customer',
            'Phone',
            'Product',
            'Quantity',
            'Amount',
            'Source',
            'Payment Mode',
            'Call Status',
            'Remarks',
            'Updated Date'
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Excel Row
    |--------------------------------------------------------------------------
    */
    public function map($order): array
    {
        /*
        |--------------------------------------------------------------------------
        | Source
        |--------------------------------------------------------------------------
        */
        $source = strtolower(
            trim($order->order_source ?? '')
        );

        $sourceName = $source === 'whatsapp'
            ? 'WhatsApp'
            : 'Web';


        /*
        |--------------------------------------------------------------------------
        | Status
        |--------------------------------------------------------------------------
        */
        $validStatuses = [
            'pending',
            'verified',
            'cancel',
            'not_reachable',
            'same_order'
        ];

        if (
            empty($order->status) ||
            !in_array($order->status, $validStatuses)
        ) {

            $status = 'Other';
        } else {

            $status = ucwords(
                str_replace(
                    '_',
                    ' ',
                    $order->status
                )
            );
        }


        return [

            $order->order_id,

            optional($order->client)->client_name,

            $order->customer_name,

            $order->customer_phone,

            $order->product_name,

            $order->quantity,

            $order->amount,

            $sourceName,

            strtoupper(
                $order->payment_mode ?? ''
            ),

            $status,

            $order->remarks,

            optional($order->updated_at)
                ->format('d-m-Y h:i A')
        ];
    }
}
