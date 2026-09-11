<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Carbon\Carbon;

class OrdersReportController extends Controller
{
    public function index(Request $request)
    {


        $dateFrom = $request->date_from
            ?: now()->format('Y-m-d');

        $dateTo = $request->date_to
            ?: now()->format('Y-m-d');


        $clients = DB::table('clients')
            ->select(
                'id',
                'client_name'
            )
            ->orderBy('client_name')
            ->get();


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

        $staffQuery->whereBetween(
            'c.updated_at',
            [
                Carbon::parse($dateFrom)->startOfDay(),
                Carbon::parse($dateTo)->endOfDay()
            ]
        );

        $staffs = $staffQuery
            ->orderBy('cu.name')
            ->get();


        $sources = [
            'web' =>
            'Web',

            'whatsapp' =>
            'WhatsApp',

            'rto' =>
            'RTO',

            'deliveredreorder' =>
            'Re-delivered',

            'shopify_abandoned_checkout' =>
            'Abandoned',
        ];


        $callStatuses = [
            'pending',
            'verified',
            'cancel',
            'not reachable',
            'same order',
            'other',
        ];

        $deliveryStatuses = [
            'Delivered',
            'RTO-intrasit',
            'RTO Received',
            'Customer - Intrasit',
            'Out for Delivery',
            'On Hold',
            'No Status',
        ];

        $latestOrders = DB::table('orders')
            ->select(
                DB::raw(
                    'TRIM(order_id) as normalized_order_id'
                ),
                DB::raw(
                    'MAX(id) as latest_id'
                )
            )
            ->whereNotNull('order_id')
            ->whereRaw(
                "TRIM(order_id) <> ''"
            )
            ->groupBy(
                DB::raw('TRIM(order_id)')
            );

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
                        'lo.normalized_order_id',
                        '=',
                        DB::raw(
                            'TRIM(c.order_id)'
                        )
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

                'c.id',

                'c.client_id',

                'cl.client_name',

                'c.assigned_to as staff_id',

                'cu.name as staff_name',

                'c.order_id',

                'c.customer_name',

                'c.customer_phone',

                'c.order_source',

                'c.status as call_status',

                'c.is_exported',

                'c.created_at as calling_date',

                'c.updated_at as calling_updated_at',

                'o.delivery_status',

                'o.payment_mode',

                'o.created_at as order_date',

                'o.updated_at as delivery_updated_at',

            ]);

        $query->whereBetween(
            'c.updated_at',
            [
                Carbon::parse($dateFrom)->startOfDay(),
                Carbon::parse($dateTo)->endOfDay()
            ]
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

            if (
                $request->order_source === 'web'
            ) {

                $query->where(function ($q) {

                    $q->whereNull(
                        'c.order_source'
                    )
                        ->orWhere(
                            'c.order_source',
                            ''
                        );
                });
            } else {

                if (
                    $request->order_source ===
                    'deliveredreorder'
                ) {

                    $query->whereIn(
                        'c.order_source',
                        [
                            'deliveredreorder',
                            'redelivered',
                            're delivered',
                            're-delivered',
                            're-delivred',
                        ]
                    );
                } elseif (
                    $request->order_source ===
                    'shopify_abandoned_checkout'
                ) {

                    $query->whereIn(
                        'c.order_source',
                        [
                            'shopify_abandoned_checkout',
                            'abandoned',
                            'abanded',
                        ]
                    );
                } else {

                    $query->where(
                        'c.order_source',
                        $request->order_source
                    );
                }
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
                $request->delivery_status ===
                'No Status'
            ) {

                $query->where(function ($q) {

                    $q->whereNull(
                        'o.delivery_status'
                    )
                        ->orWhere(
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
            ->orderBy('c.client_id')
            ->orderBy('cu.name')
            ->orderByDesc('c.updated_at')
            ->get();

        $overall = $this->callingStats(
            $rows
        );

        $delivery = $this->deliveryStats(
            $rows
        );

        $sourceStats = $this->sourceStats(
            $rows
        );

        $staffReport = $rows
            ->groupBy(function ($row) {

                return
                    $row->client_id
                    . '_'
                    . $row->staff_id;
            })

            ->map(function ($staffRows) {

                $performance =
                    $this->staffPerformance(
                        $staffRows
                    );

                $verifiedStaffRows =
                    $staffRows->filter(
                        function ($row) {

                            $status =
                                strtolower(
                                    trim(
                                        (string)
                                        ($row->call_status ?? '')
                                    )
                                );

                            return in_array(
                                $status,
                                [
                                    'verified',
                                    'confirm',
                                    'confirmed'
                                ],
                                true
                            );
                        }
                    );

                $paymentStats =
                    $this->paymentStats(
                        $verifiedStaffRows
                    );

                $codPoints =
                    (
                        (int)
                        (
                            $paymentStats['pure_cod']
                            ?? 0
                        )
                    ) * 2;

                $vppPoints =
                    (
                        (int)
                        (
                            $paymentStats['vpp']
                            ?? 0
                        )
                    ) * 2;

                $prepaidPoints =
                    (
                        (int)
                        (
                            $paymentStats['prepaid']
                            ?? 0
                        )
                    ) * 4;

                $deliveredCount =
                    $verifiedStaffRows
                    ->filter(function ($row) {

                        return strtolower(
                            trim(
                                (string)
                                ($row->delivery_status ?? '')
                            )
                        ) === 'delivered';
                    })
                    ->unique(function ($row) {

                        return trim(
                            (string)
                            ($row->order_id ?? '')
                        );
                    })
                    ->count();


                $deliveredPoints =
                    $deliveredCount * 4;

                $rtoPointCount =
                    $staffRows
                    ->filter(function ($row) {

                        return strtolower(
                            trim(
                                (string)
                                ($row->delivery_status ?? '')
                            )
                        ) === 'rto-intrasit';
                    })
                    ->unique(function ($row) {

                        return trim(
                            (string)
                            ($row->order_id ?? '')
                        );
                    })
                    ->count();


                $rtoPoints =
                    $rtoPointCount * -1;

                $performance['cod_orders'] =
                    $paymentStats['pure_cod']
                    ?? 0;


                $performance['vpp_orders'] =
                    $paymentStats['vpp']
                    ?? 0;


                $performance['cod_vpp_orders'] =
                    $paymentStats['cod']
                    ?? 0;


                $performance['cod_rate'] =
                    $paymentStats['cod_rate']
                    ?? 0;


                $performance['prepaid_orders'] =
                    $paymentStats['prepaid']
                    ?? 0;


                $performance['prepaid_rate'] =
                    $paymentStats['prepaid_rate']
                    ?? 0;


                $performance['payment_orders'] =
                    $paymentStats['total']
                    ?? 0;

                $performance['delivered_orders'] =
                    $deliveredCount;


                $performance['delivered_points'] =
                    $deliveredPoints;

                $performance['rto_point_count'] =
                    $rtoPointCount;


                $performance['rto_points'] =
                    $rtoPoints;


                $performance['cod_points'] =
                    $codPoints;


                $performance['vpp_points'] =
                    $vppPoints;


                $performance['prepaid_points'] =
                    $prepaidPoints;

                $performance['points'] =
                    $codPoints
                    + $vppPoints
                    + $prepaidPoints
                    + $deliveredPoints
                    + $rtoPoints;


                return $performance;
            })

            ->sort(function ($a, $b) {

                $scoreA =
                    (float)
                    ($a['score'] ?? 0);

                $scoreB =
                    (float)
                    ($b['score'] ?? 0);


                if ($scoreA != $scoreB) {

                    return
                        $scoreB
                        <=>
                        $scoreA;
                }

                $pointsA =
                    (float)
                    ($a['points'] ?? 0);

                $pointsB =
                    (float)
                    ($b['points'] ?? 0);


                return
                    $pointsB
                    <=>
                    $pointsA;
            })
            ->values();

        $verifiedRows =
            $rows->filter(function ($row) {

                $status =
                    strtolower(
                        trim(
                            (string)
                            ($row->call_status ?? '')
                        )
                    );

                return in_array(
                    $status,
                    [
                        'verified',
                        'confirm',
                        'confirmed'
                    ],
                    true
                );
            });


        $payment =
            $this->paymentStats(
                $verifiedRows
            );

        $bestStaff =
            $staffReport
            ->sort(function ($a, $b) {

                $pointsA =
                    (float)
                    ($a['points'] ?? 0);

                $pointsB =
                    (float)
                    ($b['points'] ?? 0);

                if ($pointsA != $pointsB) {

                    return
                        $pointsB
                        <=>
                        $pointsA;
                }

                $scoreA =
                    (float)
                    ($a['score'] ?? 0);

                $scoreB =
                    (float)
                    ($b['score'] ?? 0);


                return
                    $scoreB
                    <=>
                    $scoreA;
            })
            ->first();

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
                    $rtoTotal
                    /
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
                'payment',

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

    private function sourceStats($rows)
    {
        return [

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
                        're delivered',
                        're-delivered',
                        're-delivred'
                    ],
                    true
                );
            })->count(),


            'shopify_abandoned_checkout' =>
            $rows->filter(function ($row) {

                $source = strtolower(
                    trim(
                        (string) $row->order_source
                    )
                );

                return in_array(
                    $source,
                    [
                        'shopify_abandoned_checkout',
                        'abandoned',
                        'abanded'
                    ],
                    true
                );
            })->count(),

        ];
    }

    private function paymentStats($rows)
    {
        $normalize = function ($value) {
            $value = strtolower(trim((string) $value));
            $value = str_replace(['_', '-', '/'], ' ', $value);
            $value = preg_replace('/\s+/', ' ', $value);
            return trim($value);
        };

        $cod = $rows->filter(function ($row) use ($normalize) {
            $mode = $normalize($row->payment_mode ?? '');
            return in_array($mode, ['cod', 'vpp'], true);
        })->count();

        $pureCod = $rows->filter(function ($row) use ($normalize) {
            return $normalize($row->payment_mode ?? '') === 'cod';
        })->count();

        $vpp = $rows->filter(function ($row) use ($normalize) {
            return $normalize($row->payment_mode ?? '') === 'vpp';
        })->count();

        $prepaid = $rows->filter(function ($row) use ($normalize) {
            $mode = $normalize($row->payment_mode ?? '');
            return in_array($mode, [
                'prepaid',
                'pre paid',
                'online',
                'paid',
            ], true);
        })->count();

        $total = $cod + $prepaid;

        return [
            'total' => $total,
            'cod' => $cod,
            'pure_cod' => $pureCod,
            'vpp' => $vpp,
            'prepaid' => $prepaid,
            'cod_rate' => $total > 0 ? round(($cod / $total) * 100, 2) : 0,
            'prepaid_rate' => $total > 0 ? round(($prepaid / $total) * 100, 2) : 0,
        ];
    }


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

        $confirmationRate =
            $total > 0

            ? (
                $calling['verified']
                /
                $total
            ) * 100

            : 0;

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

        $cancelRate =
            $total > 0

            ? (
                $calling['cancel']
                /
                $total
            ) * 100

            : 0;

        $deliveryRate =
            $calling['verified'] > 0

            ? (
                $delivery['delivered']
                /
                $calling['verified']
            ) * 100

            : 0;

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

        $volumeScore =
            min(
                ($total / 100) * 5,
                5
            );

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

            'client_id' =>
            $rows->first()->client_id,

            'client_name' =>
            $rows->first()->client_name
                ?: 'Unknown Client',

            'staff_id' =>
            $rows->first()->staff_id,

            'staff_name' =>
            $rows->first()->staff_name
                ?: 'Unknown Staff',

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

    public function staffDetails(Request $request, $staffId)
    {

        $dateFrom = $request->date_from
            ?: now()->startOfMonth()->format('Y-m-d');

        $dateTo = $request->date_to
            ?: now()->format('Y-m-d');

        $staff = DB::table('calling_users')
            ->where('id', $staffId)
            ->first();

        if (!$staff) {
            abort(404, 'Staff not found.');
        }

        $clientId = $request->filled('client_id')
            ? $request->client_id
            : null;


        $clientName = null;

        if ($clientId) {

            $client = DB::table('clients')
                ->where('id', $clientId)
                ->first();

            $clientName = $client->client_name ?? null;
        }

        $latestOrders = DB::table('orders')
            ->select(
                DB::raw(
                    'TRIM(order_id) as normalized_order_id'
                ),
                DB::raw(
                    'MAX(id) as latest_id'
                )
            )
            ->whereNotNull('order_id')
            ->whereRaw(
                "TRIM(order_id) <> ''"
            )
            ->groupBy(
                DB::raw('TRIM(order_id)')
            );

        $query = DB::table('callingorder as c')

            ->leftJoinSub(
                $latestOrders,
                'lo',
                function ($join) {

                    $join->on(
                        'lo.normalized_order_id',
                        '=',
                        DB::raw(
                            'TRIM(c.order_id)'
                        )
                    );
                }
            )

            ->leftJoin(
                'orders as o',
                'o.id',
                '=',
                'lo.latest_id'
            )

            ->where(
                'c.assigned_to',
                $staffId
            )

            ->whereBetween(
                'c.updated_at',
                [
                    Carbon::parse($dateFrom)
                        ->startOfDay(),

                    Carbon::parse($dateTo)
                        ->endOfDay()
                ]
            );

        if ($clientId) {

            $query->where(
                'c.client_id',
                $clientId
            );
        }

        $rows = $query
            ->select([

                'c.id',

                'c.client_id',

                'c.order_id',

                'c.status as call_status',

                'c.updated_at as activity_date',

                'o.payment_mode',

                'o.delivery_status',

            ])
            ->orderBy('c.updated_at')
            ->get();

        $normalizePayment = function ($value) {

            $value = strtolower(
                trim((string) $value)
            );

            $value = str_replace(
                ['_', '-', '/'],
                ' ',
                $value
            );

            $value = preg_replace(
                '/\s+/',
                ' ',
                $value
            );

            return trim($value);
        };

        $verifiedRows = $rows->filter(
            function ($row) {

                $status = strtolower(
                    trim(
                        (string)
                        ($row->call_status ?? '')
                    )
                );

                return in_array(
                    $status,
                    [
                        'verified',
                        'confirm',
                        'confirmed'
                    ],
                    true
                );
            }
        );

        $totalCod = $verifiedRows
            ->filter(function ($row) use ($normalizePayment) {

                return $normalizePayment(
                    $row->payment_mode ?? ''
                ) === 'cod';
            })
            ->count();


        $totalVpp = $verifiedRows
            ->filter(function ($row) use ($normalizePayment) {

                return $normalizePayment(
                    $row->payment_mode ?? ''
                ) === 'vpp';
            })
            ->count();


        $totalPrepaid = $verifiedRows
            ->filter(function ($row) use ($normalizePayment) {

                $mode = $normalizePayment(
                    $row->payment_mode ?? ''
                );

                return in_array(
                    $mode,
                    [
                        'prepaid',
                        'pre paid',
                        'online',
                        'paid'
                    ],
                    true
                );
            })
            ->count();

        $totalDelivered = $rows
            ->filter(function ($row) {

                return strtolower(
                    trim(
                        (string)
                        ($row->delivery_status ?? '')
                    )
                ) === 'delivered';
            })
            ->count();

        $totalRto = $rows
            ->filter(function ($row) {

                return strtolower(
                    trim(
                        (string)
                        ($row->delivery_status ?? '')
                    )
                ) === 'rto-intrasit';
            })
            ->count();

        $totalCodPoints =
            $totalCod * 2;

        $totalVppPoints =
            $totalVpp * 2;

        $totalPrepaidPoints =
            $totalPrepaid * 4;

        $totalDeliveredPoints =
            $totalDelivered * 4;

        $totalRtoPoints =
            $totalRto * -1;


        $totalPoints =
            $totalCodPoints
            + $totalVppPoints
            + $totalPrepaidPoints
            + $totalDeliveredPoints
            + $totalRtoPoints;

        $paymentTotal =
            $totalCod
            + $totalVpp
            + $totalPrepaid;


        $codVppTotal =
            $totalCod
            + $totalVpp;


        $codRate =
            $paymentTotal > 0
            ? round(
                ($codVppTotal / $paymentTotal) * 100,
                2
            )
            : 0;


        $prepaidRate =
            $paymentTotal > 0
            ? round(
                ($totalPrepaid / $paymentTotal) * 100,
                2
            )
            : 0;

        $dailyPoints = collect();


        $start =
            Carbon::parse($dateFrom)
            ->startOfDay();

        $end =
            Carbon::parse($dateTo)
            ->startOfDay();


        while ($start->lte($end)) {

            $date =
                $start->format('Y-m-d');

            $dayRows =
                $rows->filter(
                    function ($row) use ($date) {

                        return Carbon::parse(
                            $row->activity_date
                        )->format('Y-m-d') === $date;
                    }
                );

            $verifiedDayRows =
                $dayRows->filter(
                    function ($row) {

                        $status =
                            strtolower(
                                trim(
                                    (string)
                                    ($row->call_status ?? '')
                                )
                            );

                        return in_array(
                            $status,
                            [
                                'verified',
                                'confirm',
                                'confirmed'
                            ],
                            true
                        );
                    }
                );

            $cod =
                $verifiedDayRows
                ->filter(
                    function ($row)
                    use ($normalizePayment) {

                        return $normalizePayment(
                            $row->payment_mode ?? ''
                        ) === 'cod';
                    }
                )
                ->count();

            $vpp =
                $verifiedDayRows
                ->filter(
                    function ($row)
                    use ($normalizePayment) {

                        return $normalizePayment(
                            $row->payment_mode ?? ''
                        ) === 'vpp';
                    }
                )
                ->count();

            $prepaid =
                $verifiedDayRows
                ->filter(
                    function ($row)
                    use ($normalizePayment) {

                        $mode =
                            $normalizePayment(
                                $row->payment_mode ?? ''
                            );

                        return in_array(
                            $mode,
                            [
                                'prepaid',
                                'pre paid',
                                'online',
                                'paid'
                            ],
                            true
                        );
                    }
                )
                ->count();

            $delivered =
                $dayRows
                ->filter(
                    function ($row) {

                        return strtolower(
                            trim(
                                (string)
                                ($row->delivery_status ?? '')
                            )
                        ) === 'delivered';
                    }
                )
                ->count();

            $rto =
                $dayRows
                ->filter(
                    function ($row) {

                        return strtolower(
                            trim(
                                (string)
                                ($row->delivery_status ?? '')
                            )
                        ) === 'rto-intrasit';
                    }
                )
                ->count();

            $codPoints =
                $cod * 2;

            $vppPoints =
                $vpp * 2;

            $prepaidPoints =
                $prepaid * 4;

            $deliveredPoints =
                $delivered * 4;

            $rtoPoints =
                $rto * -1;


            $points =
                $codPoints
                + $vppPoints
                + $prepaidPoints
                + $deliveredPoints
                + $rtoPoints;

            $dailyPoints->push([

                'date' =>
                $date,

                'display_date' =>
                Carbon::parse($date)
                    ->format('d M'),

                'day' =>
                Carbon::parse($date)
                    ->format('D'),

                'cod' =>
                $cod,

                'vpp' =>
                $vpp,

                'prepaid' =>
                $prepaid,

                'delivered' =>
                $delivered,

                'rto' =>
                $rto,

                'cod_points' =>
                $codPoints,

                'vpp_points' =>
                $vppPoints,

                'prepaid_points' =>
                $prepaidPoints,

                'delivered_points' =>
                $deliveredPoints,

                'rto_points' =>
                $rtoPoints,

                'points' =>
                $points,

            ]);


            $start->addDay();
        }

        $runningPoints = 0;


        $dailyPoints =
            $dailyPoints->map(
                function ($day) use (&$runningPoints) {

                    $runningPoints +=
                        $day['points'];

                    $day['cumulative_points'] =
                        $runningPoints;

                    return $day;
                }
            );

        $chartLabels =
            $dailyPoints
            ->pluck('display_date')
            ->values()
            ->toArray();


        $chartPoints =
            $dailyPoints
            ->pluck('points')
            ->values()
            ->toArray();


        $chartCumulative =
            $dailyPoints
            ->pluck('cumulative_points')
            ->values()
            ->toArray();

        return view(
            'reports.staff-performance-detail',
            compact(

                'staff',

                'clientId',

                'clientName',

                'dateFrom',

                'dateTo',

                'dailyPoints',

                'totalPoints',

                'totalCod',

                'totalVpp',

                'totalPrepaid',

                'totalDelivered',

                'totalRto',

                'totalCodPoints',

                'totalVppPoints',

                'totalPrepaidPoints',

                'totalDeliveredPoints',

                'totalRtoPoints',

                'paymentTotal',

                'codRate',

                'prepaidRate',

                'chartLabels',

                'chartPoints',

                'chartCumulative'
            )
        );
    }

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


    public function export(Request $request)
    {
        $dateFrom = $request->date_from
            ?: now()->format('Y-m-d');

        $dateTo = $request->date_to
            ?: now()->format('Y-m-d');


        $latestOrders = DB::table('orders')
            ->select(
                DB::raw('TRIM(order_id) as normalized_order_id'),
                DB::raw('MAX(id) as latest_id')
            )
            ->whereNotNull('order_id')
            ->whereRaw("TRIM(order_id) <> ''")
            ->groupBy(DB::raw('TRIM(order_id)'));


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
                        'lo.normalized_order_id',
                        '=',
                        DB::raw('TRIM(c.order_id)')
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

                'o.payment_mode',

                'c.created_at as calling_date',

                'o.updated_at as delivery_updated_at',

            ])

            ->whereBetween(
                'c.updated_at',
                [
                    Carbon::parse($dateFrom)->startOfDay(),
                    Carbon::parse($dateTo)->endOfDay()
                ]
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

                if ($request->order_source === 'deliveredreorder') {

                    $query->whereIn('c.order_source', [
                        'deliveredreorder',
                        'redelivered',
                        're delivered',
                        're-delivered',
                        're-delivred',
                    ]);
                } elseif ($request->order_source === 'shopify_abandoned_checkout') {

                    $query->whereIn('c.order_source', [
                        'shopify_abandoned_checkout',
                        'abandoned',
                        'abanded',
                    ]);
                } else {

                    $query->where(
                        'c.order_source',
                        $request->order_source
                    );
                }
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
                        'Payment Mode',
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

                            $row->payment_mode
                                ?: '-',

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
