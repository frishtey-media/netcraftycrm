<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OrdersReportController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | MAIN REPORT
    |--------------------------------------------------------------------------
    */

    public function index(Request $request)
    {
        $dateFrom = $request->date_from
            ?: now()->format('Y-m-d');

        $dateTo = $request->date_to
            ?: now()->format('Y-m-d');


        /*
        |--------------------------------------------------------------------------
        | CLIENTS
        |--------------------------------------------------------------------------
        |
        | callingorder.client_id -> clients.id
        |
        */

        $clients = DB::table('clients')
            ->select(
                'id',
                'client_name'
            )
            ->orderBy('client_name')
            ->get();


        /*
        |--------------------------------------------------------------------------
        | STAFF
        |--------------------------------------------------------------------------
        |
        | Staff belongs to callingorder.assigned_to
        | calling_users.id
        |
        | If client selected:
        | only staff who have orders for that client
        | will be shown.
        |
        */

        $staffQuery = DB::table('calling_users as cu')
            ->join(
                'callingorder as c',
                'c.assigned_to',
                '=',
                'cu.id'
            )
            ->select(
                'cu.id',
                'cu.name'
            )
            ->where('cu.status', 1)
            ->distinct();


        if ($request->filled('client_id')) {

            $staffQuery->where(
                'c.client_id',
                $request->client_id
            );
        }


        $staffs = $staffQuery
            ->orderBy('cu.name')
            ->get();


        /*
        |--------------------------------------------------------------------------
        | SOURCE FILTER
        |--------------------------------------------------------------------------
        */

        $sources = [
            'whatsapp',
            'web',
            'rto',
            're-delivered',
            'abandoned',
        ];


        /*
        |--------------------------------------------------------------------------
        | CALL STATUS FILTER
        |--------------------------------------------------------------------------
        */

        $callStatuses = [
            'pending',
            'verified',
            'cancel',
            'not reachable',
            'same order',
            'other',
        ];


        /*
        |--------------------------------------------------------------------------
        | DELIVERY STATUS FILTER
        |--------------------------------------------------------------------------
        */

        $deliveryStatuses = [
            'Delivered',
            'RTO-intrasit',
            'RTO Received',
            'Customer - Intrasit',
            'Out for Delivery',
            'On Hold',
            'No Status',
        ];


        /*
        |--------------------------------------------------------------------------
        | LATEST ORDER
        |--------------------------------------------------------------------------
        |
        | If same order_id has multiple records in orders,
        | latest ID is taken.
        |
        */

        $latestOrders = DB::table('orders')
            ->select(
                'order_id',
                DB::raw('MAX(id) as latest_id')
            )
            ->whereNotNull('order_id')
            ->where('order_id', '!=', '')
            ->groupBy('order_id');


        /*
        |--------------------------------------------------------------------------
        | MAIN QUERY
        |--------------------------------------------------------------------------
        */

        $query = DB::table('callingorder as c')

            /*
            | STAFF
            */
            ->join(
                'calling_users as cu',
                'cu.id',
                '=',
                'c.assigned_to'
            )

            /*
            | CLIENT
            */
            ->leftJoin(
                'clients as cl',
                'cl.id',
                '=',
                'c.client_id'
            )

            /*
            | LATEST ORDER
            */
            ->leftJoinSub(
                $latestOrders,
                'lo',
                function ($join) {

                    $join->on(
                        'lo.order_id',
                        '=',
                        'c.order_id'
                    );
                }
            )

            /*
            | ORDER
            */
            ->leftJoin(
                'orders as o',
                'o.id',
                '=',
                'lo.latest_id'
            )

            ->select([

                'c.id',

                /*
                |--------------------------------------------------------------------------
                | CLIENT
                |--------------------------------------------------------------------------
                */

                'c.client_id',

                'cl.client_name',

                /*
                |--------------------------------------------------------------------------
                | STAFF
                |--------------------------------------------------------------------------
                */

                'c.assigned_to as staff_id',

                'cu.name as staff_name',

                /*
                |--------------------------------------------------------------------------
                | CALLING ORDER
                |--------------------------------------------------------------------------
                */

                'c.order_id',

                'c.customer_name',

                'c.customer_phone',

                'c.order_source',

                'c.status as call_status',

                'c.is_exported',

                'c.created_at as calling_date',

                'c.updated_at as calling_updated_at',

                /*
                |--------------------------------------------------------------------------
                | ORDER
                |--------------------------------------------------------------------------
                */

                'o.delivery_status',

                'o.created_at as order_date',

                'o.updated_at as delivery_updated_at',

            ]);


        /*
        |--------------------------------------------------------------------------
        | DATE
        |--------------------------------------------------------------------------
        */

        $query->whereDate(
            'c.created_at',
            '>=',
            $dateFrom
        );

        $query->whereDate(
            'c.created_at',
            '<=',
            $dateTo
        );


        /*
        |--------------------------------------------------------------------------
        | CLIENT
        |--------------------------------------------------------------------------
        */

        if ($request->filled('client_id')) {

            $query->where(
                'c.client_id',
                $request->client_id
            );
        }


        /*
        |--------------------------------------------------------------------------
        | STAFF
        |--------------------------------------------------------------------------
        */

        if ($request->filled('staff_id')) {

            $query->where(
                'c.assigned_to',
                $request->staff_id
            );
        }


        /*
        |--------------------------------------------------------------------------
        | SOURCE
        |--------------------------------------------------------------------------
        */

        if ($request->filled('order_source')) {

            $query->where(
                'c.order_source',
                $request->order_source
            );
        }


        /*
        |--------------------------------------------------------------------------
        | CALL STATUS
        |--------------------------------------------------------------------------
        */

        if ($request->filled('call_status')) {

            $query->where(
                'c.status',
                $request->call_status
            );
        }


        /*
        |--------------------------------------------------------------------------
        | DELIVERY STATUS
        |--------------------------------------------------------------------------
        */

        if ($request->filled('delivery_status')) {

            if (
                $request->delivery_status === 'No Status'
            ) {

                $query->where(function ($q) {

                    $q->whereNull(
                        'o.delivery_status'
                    );

                    $q->orWhere(
                        'o.delivery_status',
                        ''
                    );
                });
            } else {

                $query->where(
                    'o.delivery_status',
                    $request->delivery_status
                );
            }
        }


        /*
        |--------------------------------------------------------------------------
        | GET DATA
        |--------------------------------------------------------------------------
        */

        $rows = $query
            ->orderBy('c.client_id')
            ->orderBy('cu.name')
            ->orderByDesc('c.created_at')
            ->get();


        /*
        |--------------------------------------------------------------------------
        | OVERALL CALLING
        |--------------------------------------------------------------------------
        */

        $overall = $this->callingStats(
            $rows
        );


        /*
        |--------------------------------------------------------------------------
        | OVERALL DELIVERY
        |--------------------------------------------------------------------------
        */

        $delivery = $this->deliveryStats(
            $rows
        );


        /*
        |--------------------------------------------------------------------------
        | SOURCE
        |--------------------------------------------------------------------------
        */

        $sourceStats = $this->sourceStats(
            $rows
        );


        /*
        |--------------------------------------------------------------------------
        | STAFF PERFORMANCE
        |--------------------------------------------------------------------------
        |
        | Client + Staff wise
        |
        */

        $staffReport = $rows
            ->groupBy(function ($row) {

                return
                    $row->client_id .
                    '_' .
                    $row->staff_id;
            })
            ->map(function ($staffRows) {

                return $this->staffPerformance(
                    $staffRows
                );
            })
            ->sortByDesc('score')
            ->values();


        /*
        |--------------------------------------------------------------------------
        | BEST STAFF
        |--------------------------------------------------------------------------
        */

        $bestStaff = $staffReport->first();


        /*
        |--------------------------------------------------------------------------
        | OVERALL RATES
        |--------------------------------------------------------------------------
        */

        $confirmationRate =
            $overall['total'] > 0

            ? round(
                (
                    $overall['verified']
                    /
                    $overall['total']
                ) * 100,
                2
            )

            : 0;


        $reachabilityRate =
            $overall['total'] > 0

            ? round(
                (
                    (
                        $overall['total']
                        -
                        $overall['not_reachable']
                    )
                    /
                    $overall['total']
                ) * 100,
                2
            )

            : 0;


        $deliveryRate =
            $overall['verified'] > 0

            ? round(
                (
                    $delivery['delivered']
                    /
                    $overall['verified']
                ) * 100,
                2
            )

            : 0;


        $rtoTotal =
            ($delivery['rto_intransit'] ?? 0)
            +
            ($delivery['rto_received'] ?? 0);


        $rtoRate =
            $overall['verified'] > 0
            ? round(
                (
                    $rtoTotal /
                    $overall['verified']
                ) * 100,
                2
            )
            : 0;


        return view(
            'reports.orders-performance',
            compact(

                'clients',
                'staffs',

                'sources',
                'callStatuses',
                'deliveryStatuses',

                'rows',

                'overall',
                'delivery',
                'sourceStats',

                'staffReport',
                'bestStaff',

                'confirmationRate',
                'reachabilityRate',
                'deliveryRate',
                'rtoRate',

                'dateFrom',
                'dateTo'

            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | CALLING STATISTICS
    |--------------------------------------------------------------------------
    */

    private function callingStats($rows)
    {
        return [

            'total' =>
            $rows->count(),

            'pending' =>
            $this->callStatusCount(
                $rows,
                ['pending']
            ),

            'verified' =>
            $this->callStatusCount(
                $rows,
                [
                    'verified',
                    'confirm',
                    'confirmed'
                ]
            ),

            'cancel' =>
            $this->callStatusCount(
                $rows,
                [
                    'cancel',
                    'cancelled',
                    'canceled'
                ]
            ),

            'not_reachable' =>
            $this->callStatusCount(
                $rows,
                [
                    'not reachable',
                    'not_reachable',
                    'not-reachable'
                ]
            ),

            'same_order' =>
            $this->callStatusCount(
                $rows,
                [
                    'same order',
                    'same_order'
                ]
            ),

            'other' =>
            $this->callStatusCount(
                $rows,
                ['other']
            ),

        ];
    }


    /*
    |--------------------------------------------------------------------------
    | DELIVERY STATISTICS
    |--------------------------------------------------------------------------
    */

    private function deliveryStats($rows)
    {
        return [
            'delivered' => $this->deliveryStatusCount($rows, 'Delivered'),

            'rto_intransit' => $this->deliveryStatusCount(
                $rows,
                'RTO-intrasit'
            ),

            'rto_received' => $this->deliveryStatusCount(
                $rows,
                'RTO Received'
            ),

            'customer_intransit' => $this->deliveryStatusCount(
                $rows,
                'Customer - Intrasit'
            ),

            'ofd' => $this->deliveryStatusCount(
                $rows,
                'Out for Delivery'
            ),

            'on_hold' => $this->deliveryStatusCount(
                $rows,
                'On Hold'
            ),

            'no_status' => $rows->filter(function ($row) {
                return is_null($row->delivery_status)
                    || trim((string) $row->delivery_status) === '';
            })->count(),
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | SOURCE STATISTICS
    |--------------------------------------------------------------------------
    */

    private function sourceStats($rows)
    {
        return [

            // NULL / empty source = WEB
            'web' =>
            $rows->filter(function ($row) {

                $source = strtolower(
                    trim(
                        (string) $row->order_source
                    )
                );

                return $source === '';
            })->count(),


            'whatsapp' =>
            $this->sourceCount(
                $rows,
                'whatsapp'
            ),


            'rto' =>
            $this->sourceCount(
                $rows,
                'rto'
            ),


            'deliveredreorder' =>
            $rows->filter(function ($row) {

                $source = strtolower(
                    trim(
                        (string) $row->order_source
                    )
                );

                return in_array(
                    $source,
                    [
                        'deliveredreorder',
                        'redelivered',
                        're delivered'
                    ]
                );
            })->count(),


            'shopify_abandoned_checkout' =>
            $this->sourceCount(
                $rows,
                'shopify_abandoned_checkout'
            ),

        ];
    }


    /*
    |--------------------------------------------------------------------------
    | STAFF PERFORMANCE
    |--------------------------------------------------------------------------
    */

    private function staffPerformance($rows)
    {
        $calling =
            $this->callingStats($rows);


        $delivery =
            $this->deliveryStats($rows);


        $sources =
            $this->sourceStats($rows);


        $total =
            $calling['total'];


        /*
        |--------------------------------------------------------------------------
        | CONFIRMATION %
        |--------------------------------------------------------------------------
        */

        $confirmationRate =
            $total > 0

            ? (
                $calling['verified']
                /
                $total
            ) * 100

            : 0;


        /*
        |--------------------------------------------------------------------------
        | REACHABILITY %
        |--------------------------------------------------------------------------
        */

        $reachable =
            $total
            -
            $calling['not_reachable'];


        $reachabilityRate =
            $total > 0

            ? (
                $reachable
                /
                $total
            ) * 100

            : 0;


        /*
        |--------------------------------------------------------------------------
        | CANCEL %
        |--------------------------------------------------------------------------
        */

        $cancelRate =
            $total > 0

            ? (
                $calling['cancel']
                /
                $total
            ) * 100

            : 0;


        /*
        |--------------------------------------------------------------------------
        | DELIVERY %
        |--------------------------------------------------------------------------
        */

        $deliveryRate =
            $calling['verified'] > 0

            ? (
                $delivery['delivered']
                /
                $calling['verified']
            ) * 100

            : 0;


        /*
        |--------------------------------------------------------------------------
        | RTO %
        |--------------------------------------------------------------------------
        */

        $rtoTotal =
            $delivery['rto_intransit']
            +
            $delivery['rto_received'];


        $rtoRate =
            $calling['verified'] > 0

            ? (
                $rtoTotal
                /
                $calling['verified']
            ) * 100

            : 0;


        /*
        |--------------------------------------------------------------------------
        | VOLUME SCORE
        |--------------------------------------------------------------------------
        |
        | Maximum 5 points.
        |
        */

        $volumeScore =
            min(
                ($total / 100) * 5,
                5
            );


        /*
        |--------------------------------------------------------------------------
        | FINAL SCORE
        |--------------------------------------------------------------------------
        |
        | Confirmation     30
        | Delivery         30
        | Reachability     15
        | Cancellation     10
        | RTO              10
        | Volume            5
        |
        */

        $score =

            ($confirmationRate * 0.30)

            +

            ($deliveryRate * 0.30)

            +

            ($reachabilityRate * 0.15)

            +

            ((100 - $cancelRate) * 0.10)

            +

            ((100 - $rtoRate) * 0.10)

            +

            $volumeScore;


        $score =
            min(
                round($score, 2),
                100
            );


        /*
        |--------------------------------------------------------------------------
        | RATING
        |--------------------------------------------------------------------------
        */

        if ($score >= 80) {

            $rating = 'Excellent';
        } elseif ($score >= 70) {

            $rating = 'Very Good';
        } elseif ($score >= 60) {

            $rating = 'Good';
        } elseif ($score >= 50) {

            $rating = 'Average';
        } else {

            $rating = 'Needs Improvement';
        }


        return [

            /*
            |--------------------------------------------------------------------------
            | CLIENT
            |--------------------------------------------------------------------------
            */

            'client_id' =>
            $rows->first()->client_id,

            'client_name' =>
            $rows->first()->client_name
                ?: 'Unknown Client',


            /*
            |--------------------------------------------------------------------------
            | STAFF
            |--------------------------------------------------------------------------
            */

            'staff_id' =>
            $rows->first()->staff_id,

            'staff_name' =>
            $rows->first()->staff_name
                ?: 'Unknown Staff',


            /*
            |--------------------------------------------------------------------------
            | CALLING
            |--------------------------------------------------------------------------
            */

            'total' =>
            $total,

            'pending' =>
            $calling['pending'],

            'verified' =>
            $calling['verified'],

            'cancel' =>
            $calling['cancel'],

            'not_reachable' =>
            $calling['not_reachable'],

            'same_order' =>
            $calling['same_order'],

            'other' =>
            $calling['other'],


            /*
            |--------------------------------------------------------------------------
            | SOURCE
            |--------------------------------------------------------------------------
            */

            'whatsapp' =>
            $sources['whatsapp'],

            'web' =>
            $sources['web'],

            'rto_source' =>
            $sources['rto'],

            'deliveredreorder' =>
            $sources['deliveredreorder'],

            'shopify_abandoned_checkout' =>
            $sources['shopify_abandoned_checkout'],


            /*
            |--------------------------------------------------------------------------
            | DELIVERY
            |--------------------------------------------------------------------------
            */

            'delivered' =>
            $delivery['delivered'] ?? 0,

            'rto_intransit' =>
            $delivery['rto_intransit'] ?? 0,

            'rto_received' =>
            $delivery['rto_received'] ?? 0,

            'rto_total' =>
            $rtoTotal,

            'customer_intransit' =>
            $delivery['customer_intransit'] ?? 0,

            'ofd' =>
            $delivery['ofd'] ?? 0,

            'on_hold' =>
            $delivery['on_hold'] ?? 0,

            'no_status' =>
            $delivery['no_status'] ?? 0,


            /*
            |--------------------------------------------------------------------------
            | RATES
            |--------------------------------------------------------------------------
            */

            'confirmation_rate' =>
            round(
                $confirmationRate,
                2
            ),

            'reachability_rate' =>
            round(
                $reachabilityRate,
                2
            ),

            'cancel_rate' =>
            round(
                $cancelRate,
                2
            ),

            'delivery_rate' =>
            round(
                $deliveryRate,
                2
            ),

            'rto_rate' =>
            round(
                $rtoRate,
                2
            ),


            /*
            |--------------------------------------------------------------------------
            | SCORE
            |--------------------------------------------------------------------------
            */

            'volume_score' =>
            round(
                $volumeScore,
                2
            ),

            'score' =>
            $score,

            'rating' =>
            $rating,

        ];
    }


    /*
    |--------------------------------------------------------------------------
    | CALL STATUS COUNT
    |--------------------------------------------------------------------------
    */

    private function callStatusCount(
        $rows,
        array $statuses
    ) {
        $statuses = array_map(
            fn($value) =>
            strtolower(trim($value)),
            $statuses
        );


        return $rows->filter(
            function ($row) use ($statuses) {

                $status =
                    strtolower(
                        trim(
                            (string)
                            $row->call_status
                        )
                    );


                return in_array(
                    $status,
                    $statuses
                );
            }
        )->count();
    }


    /*
    |--------------------------------------------------------------------------
    | DELIVERY STATUS COUNT
    |--------------------------------------------------------------------------
    */
    private function deliveryStatusCount($rows, $status)
    {
        return $rows->filter(function ($row) use ($status) {

            return strtolower(
                trim((string) $row->delivery_status)
            ) === strtolower(
                trim($status)
            );
        })->count();
    }

    /*
    |--------------------------------------------------------------------------
    | SOURCE COUNT
    |--------------------------------------------------------------------------
    */

    private function sourceCount(
        $rows,
        $source
    ) {
        return $rows->filter(
            function ($row) use ($source) {

                return strtolower(
                    trim(
                        (string)
                        $row->order_source
                    )
                ) === strtolower($source);
            }
        )->count();
    }


    /*
    |--------------------------------------------------------------------------
    | EXPORT
    |--------------------------------------------------------------------------
    */

    public function export(Request $request)
    {
        $dateFrom = $request->date_from
            ?: now()->format('Y-m-d');

        $dateTo = $request->date_to
            ?: now()->format('Y-m-d');


        $latestOrders = DB::table('orders')
            ->select(
                'order_id',
                DB::raw('MAX(id) as latest_id')
            )
            ->whereNotNull('order_id')
            ->where('order_id', '!=', '')
            ->groupBy('order_id');


        $query = DB::table('callingorder as c')

            ->join(
                'calling_users as cu',
                'cu.id',
                '=',
                'c.assigned_to'
            )

            ->leftJoin(
                'clients as cl',
                'cl.id',
                '=',
                'c.client_id'
            )

            ->leftJoinSub(
                $latestOrders,
                'lo',
                function ($join) {

                    $join->on(
                        'lo.order_id',
                        '=',
                        'c.order_id'
                    );
                }
            )

            ->leftJoin(
                'orders as o',
                'o.id',
                '=',
                'lo.latest_id'
            )

            ->select([

                'c.client_id',

                'cl.client_name',

                'cu.name as staff_name',

                'c.order_id',

                'c.customer_name',

                'c.customer_phone',

                'c.order_source',

                'c.status as call_status',

                'o.delivery_status',

                'c.created_at as calling_date',

                'o.updated_at as delivery_updated_at',

            ])

            ->whereDate(
                'c.created_at',
                '>=',
                $dateFrom
            )

            ->whereDate(
                'c.created_at',
                '<=',
                $dateTo
            );


        if ($request->filled('client_id')) {

            $query->where(
                'c.client_id',
                $request->client_id
            );
        }


        if ($request->filled('staff_id')) {

            $query->where(
                'c.assigned_to',
                $request->staff_id
            );
        }


        if ($request->filled('order_source')) {

            if ($request->order_source === 'web') {

                // NULL / empty order_source = WEB
                $query->where(function ($q) {

                    $q->whereNull('c.order_source')
                        ->orWhere('c.order_source', '');
                });
            } else {

                $query->where(
                    'c.order_source',
                    $request->order_source
                );
            }
        }


        if ($request->filled('call_status')) {

            $query->where(
                'c.status',
                $request->call_status
            );
        }


        if ($request->filled('delivery_status')) {

            if (
                $request->delivery_status === 'No Status'
            ) {

                $query->where(function ($q) {

                    $q->whereNull(
                        'o.delivery_status'
                    );

                    $q->orWhere(
                        'o.delivery_status',
                        ''
                    );
                });
            } else {

                $query->where(
                    'o.delivery_status',
                    $request->delivery_status
                );
            }
        }


        $rows = $query
            ->orderByDesc('c.created_at')
            ->get();


        $filename =
            'staff-performance-' .
            $dateFrom .
            '-to-' .
            $dateTo .
            '.csv';


        return new StreamedResponse(

            function () use ($rows) {

                $handle =
                    fopen(
                        'php://output',
                        'w'
                    );


                /*
                | UTF-8 BOM
                */
                fprintf(
                    $handle,
                    chr(0xEF) .
                        chr(0xBB) .
                        chr(0xBF)
                );


                fputcsv(
                    $handle,
                    [

                        'Client ID',
                        'Client Name',
                        'Staff',
                        'Order ID',
                        'Customer',
                        'Phone',
                        'Source',
                        'Call Status',
                        'Delivery Status',
                        'Calling Date',
                        'Delivery Updated',

                    ]
                );


                foreach ($rows as $row) {

                    fputcsv(
                        $handle,
                        [

                            $row->client_id,

                            $row->client_name,

                            $row->staff_name,

                            $row->order_id,

                            $row->customer_name,

                            $row->customer_phone,

                            $row->order_source,

                            $row->call_status,

                            $row->delivery_status
                                ?: 'No Status',

                            $row->calling_date,

                            $row->delivery_updated_at,

                        ]
                    );
                }


                fclose($handle);
            },

            200,

            [

                'Content-Type' =>
                'text/csv; charset=UTF-8',

                'Content-Disposition' =>
                'attachment; filename="' .
                    $filename .
                    '"',

            ]

        );
    }
}
