<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Carbon\Carbon;

class OrdersReportController extends Controller
{

    private function isClient()
    {
        return auth()->check() && auth()->user()->role === 'client';
    }

    private function clientId()
    {
        return auth()->user()->client_id;
    }


    public function index1(Request $request)
    {

        $dateFrom = $request->input('date_from', $request->input('from'))
            ?: now('Asia/Kolkata')->toDateString();

        $dateTo = $request->input('date_to', $request->input('to'))
            ?: now('Asia/Kolkata')->toDateString();

        // Keep date inputs as calendar dates (YYYY-MM-DD).
        $dateFrom = Carbon::parse($dateFrom, 'Asia/Kolkata')->toDateString();
        $dateTo = Carbon::parse($dateTo, 'Asia/Kolkata')->toDateString();

        $user = auth()->user();

        if ($this->isClient()) {

            // Client login: only own client

            $clients = DB::table('clients')

                ->select('id', 'client_name')

                ->where('id', $this->clientId())

                ->orderBy('client_name')

                ->get();

            // Client ke liye client_id automatically set

            $clientId = $this->clientId();
        } else {

            // Super Admin / other admin: all clients

            $clients = DB::table('clients')

                ->select('id', 'client_name')

                ->orderBy('client_name')

                ->get();

            $clientId = $request->input('client_id');
        }

        $staffIds = $request->input(
            'staff_ids',
            $request->filled('staff_id') ? [$request->input('staff_id')] : []
        );

        if (!is_array($staffIds)) {

            $staffIds = [$staffIds];
        }

        $staffIds = array_values(

            array_filter($staffIds, function ($id) {

                return $id !== null && $id !== '';
            })

        );

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

        if ($this->isClient()) {

            // Logged-in client ke staff only

            $staffQuery->where(

                'c.client_id',

                $this->clientId()

            );
        } elseif ($request->filled('client_id')) {

            // Super Admin selected client ke staff

            $staffQuery->where(

                'c.client_id',

                $request->client_id

            );
        }

        $allStaff = $staffQuery

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

        // Resolve the latest order within the same client. Order IDs may repeat

        // across clients, so matching only by order_id can attach another client's status.

        $latestOrders = DB::table('orders')

            ->select(

                'client_id',

                DB::raw('TRIM(order_id) as normalized_order_id'),

                DB::raw('MAX(id) as latest_id')

            )

            ->whereNotNull('order_id')

            ->whereRaw("TRIM(order_id) <> ''")

            ->groupBy('client_id', DB::raw('TRIM(order_id)'));

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

                    )->on('lo.client_id', '=', 'c.client_id');
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

                'o.delivery_date',

                'o.payment_mode',

                'o.created_at as order_date',

                'o.updated_at as delivery_updated_at',

            ]);

        if ($this->isClient()) {

            // Client can ONLY see own client's report

            $query->where(

                'c.client_id',

                $this->clientId()

            );
        } elseif (!empty($clientId)) {

            // Super Admin/Admin selected client

            $query->where(

                'c.client_id',

                $clientId

            );
        }

        if ($request->filled('order_source')) {

            $source = strtolower(trim($request->order_source));

            if ($source === 'web') {

                // NULL / empty source = Web

                $query->where(function ($q) {

                    $q->whereNull('c.order_source')

                        ->orWhere('c.order_source', '');
                });
            } elseif ($source === 'deliveredreorder') {

                $query->whereIn('c.order_source', [

                    'deliveredreorder',

                    'redelivered',

                    're delivered',

                    're-delivered',

                    're-delivred',

                ]);
            } elseif ($source === 'shopify_abandoned_checkout') {

                $query->whereIn('c.order_source', [

                    'shopify_abandoned_checkout',

                    'abandoned',

                    'abanded',

                ]);
            } else {

                $query->whereRaw(

                    'LOWER(TRIM(c.order_source)) = ?',

                    [$source]

                );
            }
        }

        if ($request->filled('call_status')) {

            $query->where(

                'c.status',

                $request->call_status

            );
        }

        // Keep a separate query for the main Delivered/RTO cards.

        // This snapshot excludes the delivery_status dropdown filter.

        $deliveryBaseQuery = clone $query;

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

        /*
        |--------------------------------------------------------------------------
        | DELIVERY DATA - ORDERS DELIVERY DATE
        |--------------------------------------------------------------------------
        | Calling metrics continue to use callingorder.updated_at below.
        | Delivery metrics use orders.delivery_date and match staff/source by
        | client_id + order_id. Latest callingorder ID prevents duplicate joins.
        */

        $latestCalling = DB::table('callingorder')
            ->select(
                'client_id',
                DB::raw('TRIM(order_id) AS normalized_order_id'),
                DB::raw('MAX(id) AS latest_call_id')
            )
            ->whereNotNull('order_id')
            ->whereRaw("TRIM(order_id) <> ''")
            ->groupBy('client_id', DB::raw('TRIM(order_id)'));

        $deliveryQuery = DB::table('orders as o')
            ->leftJoinSub($latestCalling, 'lc', function ($join) {
                $join->on('lc.client_id', '=', 'o.client_id')
                    ->on(
                        'lc.normalized_order_id',
                        '=',
                        DB::raw('TRIM(o.order_id)')
                    );
            })
            ->leftJoin('callingorder as c', 'c.id', '=', 'lc.latest_call_id')
            ->leftJoin('calling_users as cu', 'cu.id', '=', 'c.assigned_to')
            ->leftJoin('clients as cl', 'cl.id', '=', 'o.client_id')
            ->whereBetween('o.delivery_date', [
                $dateFrom,
                $dateTo,
            ]);

        // Client filter
        if ($this->isClient()) {
            $deliveryQuery->where('o.client_id', $this->clientId());
        } elseif (!empty($clientId)) {
            $deliveryQuery->where('o.client_id', $clientId);
        }

        // Staff filter
        if (!empty($staffIds)) {
            $deliveryQuery->whereIn('c.assigned_to', $staffIds);
        }

        // Order source filter (same aliases as the calling report)
        if ($request->filled('order_source')) {
            $source = strtolower(trim((string) $request->order_source));

            if ($source === 'web') {
                $deliveryQuery->where(function ($q) {
                    $q->whereNull('c.order_source')
                        ->orWhereRaw("TRIM(c.order_source) = ''");
                });
            } elseif ($source === 'deliveredreorder') {
                $deliveryQuery->whereRaw(
                    "LOWER(TRIM(c.order_source)) IN (?, ?, ?, ?, ?)",
                    [
                        'deliveredreorder',
                        'redelivered',
                        're delivered',
                        're-delivered',
                        're-delivred',
                    ]
                );
            } elseif ($source === 'shopify_abandoned_checkout') {
                $deliveryQuery->whereRaw(
                    "LOWER(TRIM(c.order_source)) IN (?, ?, ?)",
                    [
                        'shopify_abandoned_checkout',
                        'abandoned',
                        'abanded',
                    ]
                );
            } else {
                $deliveryQuery->whereRaw(
                    'LOWER(TRIM(c.order_source)) = ?',
                    [$source]
                );
            }
        }

        // Call status filter, matched to the same client/order
        if ($request->filled('call_status')) {
            $deliveryQuery->whereRaw(
                'LOWER(TRIM(c.status)) = ?',
                [strtolower(trim((string) $request->call_status))]
            );
        }

        // Delivery status filter
        if ($request->filled('delivery_status')) {
            if ($request->delivery_status === 'No Status') {
                $deliveryQuery->where(function ($q) {
                    $q->whereNull('o.delivery_status')
                        ->orWhereRaw("TRIM(o.delivery_status) = ''");
                });
            } else {
                $deliveryQuery->whereRaw(
                    'LOWER(TRIM(o.delivery_status)) = ?',
                    [strtolower(trim((string) $request->delivery_status))]
                );
            }
        }

        // Product filter
        if ($request->filled('product')) {
            $deliveryQuery->where('o.product', $request->product);
        }

        // Payment mode filter
        if ($request->filled('payment_mode')) {
            $deliveryQuery->whereRaw(
                'LOWER(TRIM(o.payment_mode)) = ?',
                [strtolower(trim((string) $request->payment_mode))]
            );
        }

        // Search by order ID or barcode
        $search = trim((string) $request->input('search', ''));
        if ($search !== '') {
            $deliveryQuery->where(function ($q) use ($search) {
                $q->where('o.order_id', 'LIKE', "%{$search}%")
                    ->orWhere('o.barcode', 'LIKE', "%{$search}%");
            });
        }

        $deliveryRows = $deliveryQuery
            ->select([
                'o.id',
                'o.client_id',
                'cl.client_name',
                'o.order_id',
                'o.delivery_status',
                'o.delivery_date',
                'o.payment_mode',
                'o.amount',
                'o.product',
                'c.assigned_to as staff_id',
                'cu.name as staff_name',
                'c.order_source',
            ])
            ->orderByDesc('o.delivery_date')
            ->get();

        /*
         * Normalize all delivery statuses in one place so the top cards and
         * staff-wise counts use the exact same delivery-date-based logic.
         */
        $calculateDeliveryStats = function ($rows) {
            $counts = [
                'delivered' => 0,
                'rto_intransit' => 0,
                'rto_received' => 0,
                'customer_intransit' => 0,
                'ofd' => 0,
                'on_hold' => 0,
                'no_status' => 0,
            ];

            $uniqueRows = collect($rows)->unique(function ($row) {
                return ($row->client_id ?? '') . '_' .
                    trim((string) ($row->order_id ?? ''));
            });

            foreach ($uniqueRows as $row) {
                $status = strtolower(trim((string) ($row->delivery_status ?? '')));
                $status = preg_replace('/\s+/', ' ', $status);
                $normalized = str_replace(['_', '-'], ' ', $status);
                $normalized = preg_replace('/\s+/', ' ', trim($normalized));

                if ($normalized === '' || $normalized === 'no status') {
                    $counts['no_status']++;
                } elseif (in_array($normalized, ['delivered', 'delivery'], true)) {
                    $counts['delivered']++;
                } elseif (
                    in_array($normalized, [
                        'rto intrasit',
                        'rto in transit',
                        'rto transit',
                        'rto intrast',
                    ], true)
                ) {
                    $counts['rto_intransit']++;
                } elseif (
                    in_array($normalized, [
                        'rto received',
                        'rto receive',
                        'rto delivered',
                        'rto return received',
                    ], true)
                ) {
                    $counts['rto_received']++;
                } elseif (
                    in_array($normalized, [
                        'customer intrasit',
                        'customer in transit',
                        'customer transit',
                        'in transit',
                    ], true)
                ) {
                    $counts['customer_intransit']++;
                } elseif (
                    in_array($normalized, [
                        'out for delivery',
                        'ofd',
                    ], true)
                ) {
                    $counts['ofd']++;
                } elseif (
                    in_array($normalized, [
                        'on hold',
                        'hold',
                    ], true)
                ) {
                    $counts['on_hold']++;
                } else {
                    // Unknown/blank statuses should not be silently counted as Delivered.
                    $counts['no_status']++;
                }
            }

            $rtoTotal = $counts['rto_intransit'] + $counts['rto_received'];
            $total = array_sum($counts);

            return array_merge($counts, [
                'rto_total' => $rtoTotal,
                'total' => $total,
                'rto_rate' => $total > 0
                    ? round(($rtoTotal / $total) * 100, 2)
                    : 0,
            ]);
        };

        $delivery = $calculateDeliveryStats($deliveryRows);

        // Group delivery rows by client + staff for staff performance.
        $deliveryByStaff = $deliveryRows
            ->filter(function ($row) {
                return !empty($row->staff_id);
            })
            ->groupBy(function ($row) {
                return $row->client_id . '_' . $row->staff_id;
            });

        // All existing report rows and other metrics still use callingorder.updated_at.

        // Convert India-local selected dates to UTC boundaries for timestamp columns.
        $callStartUtc = Carbon::parse($dateFrom, 'Asia/Kolkata')
            ->startOfDay()
            ->utc();

        $callEndUtc = Carbon::parse($dateTo, 'Asia/Kolkata')
            ->endOfDay()
            ->utc();

        $query->whereBetween('c.updated_at', [
            $callStartUtc,
            $callEndUtc,
        ]);

        $rows = $query

            ->orderBy('c.client_id')

            ->orderBy('cu.name')

            ->orderByDesc('c.updated_at')

            ->get();

        $overall = $this->callingStats(

            $rows

        );

        $sourceStats = $this->sourceStats(

            $rows

        );

        $staffGroups = $rows
            ->groupBy(function ($row) {
                return $row->client_id . '_' . $row->staff_id;
            });

        foreach ($deliveryByStaff as $staffKey => $staffDeliveryRows) {
            if (!$staffGroups->has($staffKey)) {
                $deliveryRow = $staffDeliveryRows->first();

                $staffGroups->put($staffKey, collect([
                    (object) [
                        'client_id' => $deliveryRow->client_id,
                        'client_name' => $deliveryRow->client_name ?? 'Unknown Client',
                        'staff_id' => $deliveryRow->staff_id,
                        'staff_name' => $deliveryRow->staff_name ?? 'Unknown Staff',
                        'order_id' => $deliveryRow->order_id,
                        'order_source' => $deliveryRow->order_source,
                        'delivery_status' => $deliveryRow->delivery_status,
                        'payment_mode' => $deliveryRow->payment_mode ?? null,
                        'amount' => $deliveryRow->amount ?? 0,
                        'product' => $deliveryRow->product ?? null,
                        'call_status' => '__delivery_only__',
                        'is_delivery_only' => true,
                    ],
                ]));
            }
        }

        $staffReport = $staffGroups
            ->map(function ($staffRows) use ($deliveryByStaff, $calculateDeliveryStats) {
                $firstStaffRow = $staffRows->first();
                $staffKey = $firstStaffRow->client_id . '_' . $firstStaffRow->staff_id;
                $staffDeliveryRows = $deliveryByStaff->get($staffKey, collect());
                $staffDeliveryStats = $calculateDeliveryStats($staffDeliveryRows);
                $isDeliveryOnly = !empty($firstStaffRow->is_delivery_only);

                if ($isDeliveryOnly) {
                    $performance = [
                        'client_id' => $firstStaffRow->client_id,
                        'client_name' => $firstStaffRow->client_name ?? 'Unknown Client',
                        'staff_id' => $firstStaffRow->staff_id,
                        'staff_name' => $firstStaffRow->staff_name ?? 'Unknown Staff',
                        'total' => 0,
                        'pending' => 0,
                        'verified' => 0,
                        'cancel' => 0,
                        'not_reachable' => 0,
                        'same_order' => 0,
                        'other' => 0,
                        'whatsapp' => 0,
                        'web' => 0,
                        'rto_source' => 0,
                        'deliveredreorder' => 0,
                        'shopify_abandoned_checkout' => 0,
                        'confirmation_rate' => 0,
                        'reachability_rate' => 0,
                        'cancel_rate' => 0,
                        'delivery_rate' => 0,
                        'rto_rate' => 0,
                        'volume_score' => 0,
                        'score' => 20,
                        'rating' => 'Needs Improvement',
                    ];
                } else {
                    $performance = $this->staffPerformance($staffRows);
                }
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

                $deliveredCount = $staffDeliveryRows
                    ->filter(function ($row) {
                        return strtolower(trim((string) ($row->delivery_status ?? ''))) === 'delivered';
                    })
                    ->unique(function ($row) {
                        return $row->client_id . '_' . trim((string) ($row->order_id ?? ''));
                    })
                    ->count();

                $deliveredPoints =

                    $deliveredCount * 4;

                $rtoPointCount = $staffDeliveryRows
                    ->filter(function ($row) {
                        return strtolower(trim((string) ($row->delivery_status ?? ''))) === 'rto-intrasit';
                    })
                    ->unique(function ($row) {
                        return $row->client_id . '_' . trim((string) ($row->order_id ?? ''));
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

                // Delivery columns are based on orders.delivery_date, not callingorder.updated_at.
                $performance['delivered'] = $staffDeliveryStats['delivered'] ?? 0;
                $performance['rto_intransit'] = $staffDeliveryStats['rto_intransit'] ?? 0;
                $performance['rto_received'] = $staffDeliveryStats['rto_received'] ?? 0;
                $performance['rto_total'] =
                    ($performance['rto_intransit'] ?? 0) +
                    ($performance['rto_received'] ?? 0);
                $performance['customer_intransit'] = $staffDeliveryStats['customer_intransit'] ?? 0;
                $performance['ofd'] = $staffDeliveryStats['ofd'] ?? 0;
                $performance['on_hold'] = $staffDeliveryStats['on_hold'] ?? 0;
                $performance['no_status'] = $staffDeliveryStats['no_status'] ?? 0;

                $verifiedCount = (int) ($performance['verified'] ?? 0);
                $performance['delivery_rate'] = $verifiedCount > 0
                    ? round((($performance['delivered'] ?? 0) / $verifiedCount) * 100, 2)
                    : 0;
                $performance['rto_rate'] = $verifiedCount > 0
                    ? round((($performance['rto_total'] ?? 0) / $verifiedCount) * 100, 2)
                    : 0;

                // Recalculate overall rating score using corrected delivery rates.
                $updatedScore =
                    ((float) ($performance['confirmation_rate'] ?? 0) * 0.30) +
                    ((float) ($performance['delivery_rate'] ?? 0) * 0.30) +
                    ((float) ($performance['reachability_rate'] ?? 0) * 0.15) +
                    ((100 - (float) ($performance['cancel_rate'] ?? 0)) * 0.10) +
                    ((100 - (float) ($performance['rto_rate'] ?? 0)) * 0.10) +
                    (float) ($performance['volume_score'] ?? 0);

                $performance['score'] = min(round($updatedScore, 2), 100);

                if ($performance['score'] >= 80) {
                    $performance['rating'] = 'Excellent';
                } elseif ($performance['score'] >= 70) {
                    $performance['rating'] = 'Very Good';
                } elseif ($performance['score'] >= 60) {
                    $performance['rating'] = 'Good';
                } elseif ($performance['score'] >= 50) {
                    $performance['rating'] = 'Average';
                } else {
                    $performance['rating'] = 'Needs Improvement';
                }

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

            'reports.orders-performance1',

            compact(

                'clients',

                'sources',

                'callStatuses',

                'deliveryStatuses',

                'rows',

                'allStaff',

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







    public function index(Request $request)
    {
        $dateFrom = $request->date_from
            ?: now()->format('Y-m-d');

        $dateTo = $request->date_to
            ?: now()->format('Y-m-d');

        $user = auth()->user();

        if ($this->isClient()) {

            // Client login: only own client
            $clients = DB::table('clients')
                ->select('id', 'client_name')
                ->where('id', $this->clientId())
                ->orderBy('client_name')
                ->get();

            // Client ke liye client_id automatically set
            $clientId = $this->clientId();
        } else {

            // Super Admin / other admin: all clients
            $clients = DB::table('clients')
                ->select('id', 'client_name')
                ->orderBy('client_name')
                ->get();

            $clientId = $request->input('client_id');
        }
        $staffIds = $request->input('staff_ids', []);

        if (!is_array($staffIds)) {
            $staffIds = [$staffIds];
        }

        $staffIds = array_values(
            array_filter($staffIds, function ($id) {
                return $id !== null && $id !== '';
            })
        );

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

        if ($this->isClient()) {

            // Logged-in client ke staff only
            $staffQuery->where(
                'c.client_id',
                $this->clientId()
            );
        } elseif ($request->filled('client_id')) {

            // Super Admin selected client ke staff
            $staffQuery->where(
                'c.client_id',
                $request->client_id
            );
        }

        $allStaff = $staffQuery
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

        if ($this->isClient()) {

            // Client can ONLY see own client's report
            $query->where(
                'c.client_id',
                $this->clientId()
            );
        } elseif (!empty($clientId)) {

            // Super Admin/Admin selected client
            $query->where(
                'c.client_id',
                $clientId
            );
        }

        if (!empty($staffIds)) {

            $query->whereIn(
                'c.assigned_to',
                $staffIds
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

                'sources',
                'callStatuses',
                'deliveryStatuses',

                'rows',
                'allStaff',
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
        $normalizeStatus = function ($status) {
            return strtolower(trim((string) ($status ?? '')));
        };

        $countStatus = function ($status) use ($rows, $normalizeStatus) {
            $target = $normalizeStatus($status);

            return $rows->filter(function ($row) use ($normalizeStatus, $target) {
                return $normalizeStatus($row->delivery_status ?? '') === $target;
            })->count();
        };

        return [
            'delivered' => $countStatus('Delivered'),

            'rto_intransit' => $countStatus('RTO-intrasit'),

            'rto_received' => $countStatus('RTO Received'),

            'customer_intransit' => $countStatus('Customer - Intrasit'),

            'ofd' => $countStatus('Out for Delivery'),

            'on_hold' => $countStatus('On Hold'),

            'no_status' => $rows->filter(function ($row) {
                return trim((string) ($row->delivery_status ?? '')) === '';
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

        $staffIds = $request->input('staff_ids', []);

        if (!is_array($staffIds)) {
            $staffIds = [$staffIds];
        }

        $staffIds = array_values(
            array_filter($staffIds, function ($id) {
                return $id !== null && $id !== '';
            })
        );

        if (!empty($staffIds)) {
            $query->whereIn(
                'c.assigned_to',
                $staffIds
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
