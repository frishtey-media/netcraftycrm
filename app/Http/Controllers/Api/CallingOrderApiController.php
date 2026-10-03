<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\callingorder;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use App\Models\KnowlarityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CallingOrderApiController extends Controller
{


    public function verifiedOrders(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated',
            ], 401);
        }

        $userId = $user->id;

        // DEBUG
        $totalVerified = CallingOrder::whereRaw(
            "LOWER(TRIM(status)) = ?",
            ['verified']
        )->count();

        $myVerified = CallingOrder::where('assigned_to', $userId)
            ->whereRaw(
                "LOWER(TRIM(status)) = ?",
                ['verified']
            )
            ->count();

        $orders = CallingOrder::where('assigned_to', $userId)
            ->whereRaw(
                "LOWER(TRIM(status)) = ?",
                ['verified']
            )
            ->latest('created_at')
            ->paginate(20);

        return response()->json([
            'success' => true,

            'debug' => [
                'logged_in_user_id' => $userId,
                'logged_in_user_name' => $user->name,
                'total_verified_all_staff' => $totalVerified,
                'my_verified_orders' => $myVerified,
            ],

            'data' => [
                'orders' => $orders,
                'clients' => [],
                'status_label' => 'Verified',
                'status_count' => $orders->total(),
            ],
        ]);
    }
    private function getStaffDeliveryOrders(
        Request $request,
        string $deliveryStatus
    ) {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $userId = $user->id;


        $query = DB::table('callingorder as co')

            ->join(
                'orders as o',
                'o.order_id',
                '=',
                'co.order_id'
            )

            ->leftJoin('calling_logs as cl', function ($join) {

                $join->on(
                    'cl.order_id',
                    '=',
                    'co.order_id'
                );

                $join->on(
                    'cl.staff_id',
                    '=',
                    'co.assigned_to'
                );

                $join->whereRaw('
                    cl.id = (
                        SELECT MAX(cl2.id)
                        FROM calling_logs AS cl2
                        WHERE cl2.order_id = co.order_id
                        AND cl2.staff_id = co.assigned_to
                    )
                ');
            })

            ->where(
                'co.assigned_to',
                $userId
            )

            ->where(
                'o.delivery_status',
                $deliveryStatus
            );


        /*
        |--------------------------------------------------------------------------
        | Client Filter
        |--------------------------------------------------------------------------
        */

        if ($request->filled('client_id')) {

            $query->where(
                'co.client_id',
                $request->client_id
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Search Filter
        |--------------------------------------------------------------------------
        */

        if ($request->filled('search')) {

            $search = trim(
                $request->search
            );

            $query->where(function ($q) use ($search) {

                $q->where(
                    'o.customer_name',
                    'like',
                    "%{$search}%"
                )

                    ->orWhere(
                        'o.customer_phone',
                        'like',
                        "%{$search}%"
                    )

                    ->orWhere(
                        'o.barcode',
                        'like',
                        "%{$search}%"
                    )

                    ->orWhere(
                        'co.order_id',
                        'like',
                        "%{$search}%"
                    )

                    ->orWhere(
                        'o.product',
                        'like',
                        "%{$search}%"
                    )

                    ->orWhere(
                        'o.city',
                        'like',
                        "%{$search}%"
                    )

                    ->orWhere(
                        'o.pincode',
                        'like',
                        "%{$search}%"
                    );
            });
        }


        /*
        |--------------------------------------------------------------------------
        | Select
        |--------------------------------------------------------------------------
        */

        $query->select(
            'co.id',
            'co.order_id',
            'co.client_id',
            'co.assigned_to',

            'o.customer_name',
            'o.customer_phone',
            'o.shipping_address',
            'o.city',
            'o.barcode',
            'o.state',
            'o.pincode',
            'o.delivery_remark',

            'o.product as product_name',
            'o.quantity',
            'o.amount',
            'o.payment_mode',
            'o.date as order_date',
            'o.delivery_status',

            'cl.id as call_log_id',
            'cl.call_status',
            'cl.called_at'
        );


        /*
        |--------------------------------------------------------------------------
        | Latest Orders
        |--------------------------------------------------------------------------
        */

        $query->orderByDesc(
            'o.date'
        );


        /*
        |--------------------------------------------------------------------------
        | Pagination
        |--------------------------------------------------------------------------
        */

        return $query
            ->paginate(20)
            ->withQueryString();
    }

    public function sidebarCounts(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $userId = $user->id;

        $outForDelivery = DB::table('callingorder as co')
            ->join(
                'orders as o',
                'o.order_id',
                '=',
                'co.order_id'
            )
            ->where('co.assigned_to', $userId)
            ->where('o.delivery_status', 'Out for Delivery')
            ->count();

        $onHold = DB::table('callingorder as co')
            ->join(
                'orders as o',
                'o.order_id',
                '=',
                'co.order_id'
            )
            ->where('co.assigned_to', $userId)
            ->where('o.delivery_status', 'On Hold')
            ->count();

        return response()->json([
            'success' => true,

            'data' => [
                'out_for_delivery' => $outForDelivery,
                'on_hold' => $onHold,
            ],
        ]);
    }


    public function onHold(Request $request)
    {
        $orders = $this->getStaffDeliveryOrders(
            $request,
            'On Hold'
        );

        return response()->json([
            'success' => true,
            'message' => 'On Hold orders fetched successfully.',
            'data' => $orders,
        ]);
    }
    public function pendingOrders(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $userId = (int) $user->id;

        // Client-wise pending count for the logged-in staff only.
        $clients = DB::table('callingorder as co')
            ->leftJoin('clients as c', 'c.id', '=', 'co.client_id')
            ->where('co.assigned_to', $userId)
            ->where('co.status', 'pending')
            ->select(
                'co.client_id',
                DB::raw("COALESCE(c.client_name, 'Client') as client_name"),
                DB::raw('COUNT(co.id) as total')
            )
            ->groupBy('co.client_id', 'c.client_name')
            ->orderBy('client_name')
            ->get()
            ->map(function ($row) {
                return [
                    'client_id' => $row->client_id !== null ? (int) $row->client_id : null,
                    'client_name' => (string) $row->client_name,
                    'total' => (int) $row->total,
                ];
            })
            ->values();

        $query = CallingOrder::query()
            ->where('assigned_to', $userId)
            ->where('status', 'pending');

        if ($request->filled('client_id')) {
            $query->where('client_id', (int) $request->client_id);
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->search);

            $query->where(function ($q) use ($search) {
                $like = "%{$search}%";

                $q->where('order_id', 'like', $like)
                    ->orWhere('customer_name', 'like', $like)
                    ->orWhere('customer_phone', 'like', $like)
                    ->orWhere('product_name', 'like', $like)
                    ->orWhere('city', 'like', $like)
                    ->orWhere('state', 'like', $like)
                    ->orWhere('shipping_address', 'like', $like)
                    ->orWhere('pincode', 'like', $like);
            });
        }

        $orders = $query
            ->latest()
            ->paginate(50)
            ->withQueryString();

        return response()->json([
            'success' => true,
            'message' => 'Pending orders fetched successfully.',
            'data' => [
                'orders' => $orders,
                'clients' => $clients,
                'status_label' => 'Pending',
                'status_count' => $orders->total(),
            ],
        ]);
    }

    public function updatePendingOrderStatus(Request $request, int $id)
    {
        $request->validate([
            'status' => [
                'required',
                'string',
                'in:verified,same_order,not_reachable,cancel',
            ],
        ]);

        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $order = CallingOrder::query()
            ->where('id', $id)
            ->where('assigned_to', $user->id)
            ->where('status', 'pending')
            ->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Pending order not found or not assigned to this staff member.',
            ], 404);
        }

        $order->status = $request->status;
        $order->save();

        return response()->json([
            'success' => true,
            'message' => 'Order status updated successfully.',
            'data' => [
                'order' => $order->fresh(),
            ],
        ]);
    }

    public function updatePendingOrder(Request $request, int $id)
    {
        $request->validate([
            'product_name' => 'nullable|string|max:1000',
            'quantity' => 'nullable|integer|min:1',
            'customer_name' => 'nullable|string|max:255',
            'father_name' => 'nullable|string|max:255',
            'customer_phone' => 'nullable|string|max:30',
            'shipping_address' => 'nullable|string|max:2000',
            'age' => 'nullable|integer|min:0|max:150',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'pincode' => 'nullable|string|max:20',
            'payment_mode' => 'nullable|string|max:100',
            'amount' => 'nullable|numeric|min:0',
            'remarks' => 'nullable|string|max:2000',
        ]);

        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $order = CallingOrder::query()
            ->where('id', $id)
            ->where('assigned_to', $user->id)
            ->where('status', 'pending')
            ->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Pending order not found or not assigned to this staff member.',
            ], 404);
        }

        $order->update([
            'product_name' => $request->product_name,
            'quantity' => $request->quantity,
            'customer_name' => $request->customer_name,
            'father_name' => $request->father_name,
            'customer_phone' => $request->customer_phone,
            'shipping_address' => $request->shipping_address,
            'age' => $request->age,
            'city' => $request->city,
            'state' => $request->state,
            'pincode' => $request->pincode,
            'payment_mode' => $request->payment_mode,
            'amount' => $request->amount,
            'remarks' => $request->remarks,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Order updated successfully.',
            'data' => [
                'order' => $order->fresh(),
            ],
        ]);
    }
    public function trackCall(Request $request)
    {
        $request->validate([
            'callingorder_id' => [
                'required',
                'integer',
            ],

            'order_id' => [
                'required',
                'string',
            ],

            'customer_phone' => [
                'required',
                'string',
            ],

            'call_status' => [
                'nullable',
                'string',
            ],
        ]);


        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }


        /*
    |--------------------------------------------------------------------------
    | Verify order belongs to logged-in staff
    |--------------------------------------------------------------------------
    */

        $callingOrder = DB::table('callingorder')
            ->where('id', $request->callingorder_id)
            ->where(
                'assigned_to',
                $user->id
            )
            ->where(
                'order_id',
                $request->order_id
            )
            ->first();


        if (!$callingOrder) {

            return response()->json([
                'success' => false,
                'message' => 'Order is not assigned to this staff member.',
            ], 403);
        }


        /*
    |--------------------------------------------------------------------------
    | Create Call Log
    |--------------------------------------------------------------------------
    */

        $calledAt = now();


        $logId = DB::table('calling_logs')
            ->insertGetId([
                'order_id' => $request->order_id,
                'staff_id' => $user->id,
                'customer_phone' =>
                $request->customer_phone,
                'call_status' =>
                $request->call_status ?? 'called',
                'called_at' => $calledAt,
                'created_at' => now(),
                'updated_at' => now(),
            ]);


        return response()->json([
            'success' => true,
            'message' => 'Call tracked successfully.',
            'data' => [
                'id' => $logId,
                'called_at' => $calledAt->toDateTimeString(),
                'call_status' =>
                $request->call_status ?? 'called',
            ],
        ]);
    }
    public function me(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'data' => [
                'staff' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'status' => $user->status,
                    'client_id' => $user->client_id,
                ],
            ],
        ]);
    }

    public function dashboard(Request $request)
    {
        /*
    |--------------------------------------------------------------------------
    | Logged-in Sanctum Staff
    |--------------------------------------------------------------------------
    */

        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.'
            ], 401);
        }

        $userId = $user->id;


        /*
    |--------------------------------------------------------------------------
    | Date Filter
    |--------------------------------------------------------------------------
    */

        $fromDate = $request->filled('from')
            ? $request->from
            : now()->format('Y-m-d');

        $toDate = $request->filled('to')
            ? $request->to
            : now()->format('Y-m-d');

        try {
            $from = Carbon::parse($fromDate)->startOfDay();
            $to = Carbon::parse($toDate)->endOfDay();
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid date format. Use YYYY-MM-DD.'
            ], 422);
        }

        if ($from->gt($to)) {
            return response()->json([
                'success' => false,
                'message' => 'From date cannot be greater than To date.'
            ], 422);
        }


        /*
    |--------------------------------------------------------------------------
    | Base Query
    |--------------------------------------------------------------------------
    */

        $baseQuery = CallingOrder::query()
            ->where('assigned_to', $userId)
            ->whereBetween('updated_at', [$from, $to]);


        /*
    |--------------------------------------------------------------------------
    | Source Conditions
    |--------------------------------------------------------------------------
    */

        $webCondition = function ($q) {
            $q->whereNull('order_source')
                ->orWhere('order_source', '')
                ->orWhereRaw(
                    'LOWER(TRIM(order_source)) = ?',
                    ['web']
                );
        };

        $whatsappCondition = function ($q) {
            $q->whereRaw(
                'LOWER(TRIM(order_source)) = ?',
                ['whatsapp']
            );
        };

        $rtoCondition = function ($q) {
            $q->whereRaw(
                'UPPER(TRIM(order_source)) = ?',
                ['RTO']
            );
        };

        $deliverReorderCondition = function ($q) {
            $q->whereRaw(
                'LOWER(TRIM(order_source)) = ?',
                ['deliveredreorder']
            );
        };

        $abandonedCondition = function ($q) {
            $q->whereRaw(
                'LOWER(TRIM(order_source)) = ?',
                ['shopify_abandoned_checkout']
            );
        };


        /*
    |--------------------------------------------------------------------------
    | Helper
    |--------------------------------------------------------------------------
    */

        $countBySource = function (
            $sourceCondition,
            $status = null
        ) use ($baseQuery) {

            $query = clone $baseQuery;

            if ($status !== null) {
                $query->where('status', $status);
            }

            $query->where($sourceCondition);

            return $query->count();
        };


        /*
    |--------------------------------------------------------------------------
    | Total Source Counts
    |--------------------------------------------------------------------------
    */

        $totalOrders = (clone $baseQuery)->count();

        $webOrders = (clone $baseQuery)
            ->where($webCondition)
            ->count();

        $whatsappOrders = (clone $baseQuery)
            ->where($whatsappCondition)
            ->count();

        $rtoOrders = (clone $baseQuery)
            ->where($rtoCondition)
            ->count();

        $deliveredReorderOrders = (clone $baseQuery)
            ->where($deliverReorderCondition)
            ->count();

        $abandonedOrders = (clone $baseQuery)
            ->where($abandonedCondition)
            ->count();


        /*
    |--------------------------------------------------------------------------
    | Status Counts
    |--------------------------------------------------------------------------
    */

        $pending = (clone $baseQuery)
            ->where('status', 'pending')
            ->count();

        $verified = (clone $baseQuery)
            ->where('status', 'verified')
            ->count();

        $cancelled = (clone $baseQuery)
            ->where('status', 'cancel')
            ->count();

        $notConnected = (clone $baseQuery)
            ->where('status', 'not_reachable')
            ->count();

        $sameOrder = (clone $baseQuery)
            ->where('status', 'same_order')
            ->count();


        /*
    |--------------------------------------------------------------------------
    | Other Status
    |--------------------------------------------------------------------------
    */

        $standardStatuses = [
            'pending',
            'verified',
            'cancel',
            'not_reachable',
            'same_order'
        ];

        $other = (clone $baseQuery)
            ->where(function ($q) use ($standardStatuses) {
                $q->whereNotIn('status', $standardStatuses)
                    ->orWhereNull('status');
            })
            ->count();


        /*
    |--------------------------------------------------------------------------
    | Pending Breakdown
    |--------------------------------------------------------------------------
    */

        $pendingWeb =
            $countBySource($webCondition, 'pending');

        $pendingWhatsapp =
            $countBySource($whatsappCondition, 'pending');

        $pendingRto =
            $countBySource($rtoCondition, 'pending');

        $pendingDeliveredReorder =
            $countBySource($deliverReorderCondition, 'pending');

        $pendingAbandoned =
            $countBySource($abandonedCondition, 'pending');


        /*
    |--------------------------------------------------------------------------
    | Verified Breakdown
    |--------------------------------------------------------------------------
    */

        $verifiedWeb =
            $countBySource($webCondition, 'verified');

        $verifiedWhatsapp =
            $countBySource($whatsappCondition, 'verified');

        $verifiedRto =
            $countBySource($rtoCondition, 'verified');

        $verifiedDeliveredReorder =
            $countBySource($deliverReorderCondition, 'verified');

        $verifiedAbandoned =
            $countBySource($abandonedCondition, 'verified');


        /*
    |--------------------------------------------------------------------------
    | Cancelled Breakdown
    |--------------------------------------------------------------------------
    */

        $cancelledWeb =
            $countBySource($webCondition, 'cancel');

        $cancelledWhatsapp =
            $countBySource($whatsappCondition, 'cancel');

        $cancelledRto =
            $countBySource($rtoCondition, 'cancel');

        $cancelledDeliveredReorder =
            $countBySource($deliverReorderCondition, 'cancel');

        $cancelledAbandoned =
            $countBySource($abandonedCondition, 'cancel');


        /*
    |--------------------------------------------------------------------------
    | Not Connected Breakdown
    |--------------------------------------------------------------------------
    */

        $notConnectedWeb =
            $countBySource($webCondition, 'not_reachable');

        $notConnectedWhatsapp =
            $countBySource($whatsappCondition, 'not_reachable');

        $notConnectedRto =
            $countBySource($rtoCondition, 'not_reachable');

        $notConnectedDeliveredReorder =
            $countBySource(
                $deliverReorderCondition,
                'not_reachable'
            );

        $notConnectedAbandoned =
            $countBySource(
                $abandonedCondition,
                'not_reachable'
            );


        /*
    |--------------------------------------------------------------------------
    | Same Order Breakdown
    |--------------------------------------------------------------------------
    */

        $sameOrderWeb =
            $countBySource($webCondition, 'same_order');

        $sameOrderWhatsapp =
            $countBySource($whatsappCondition, 'same_order');

        $sameOrderRto =
            $countBySource($rtoCondition, 'same_order');

        $sameOrderDeliveredReorder =
            $countBySource(
                $deliverReorderCondition,
                'same_order'
            );

        $sameOrderAbandoned =
            $countBySource(
                $abandonedCondition,
                'same_order'
            );


        /*
    |--------------------------------------------------------------------------
    | Other Breakdown
    |--------------------------------------------------------------------------
    */

        $otherQuery = function (
            $sourceCondition
        ) use ($baseQuery, $standardStatuses) {

            return (clone $baseQuery)
                ->where(function ($q) use ($standardStatuses) {

                    $q->whereNotIn(
                        'status',
                        $standardStatuses
                    )->orWhereNull('status');
                })
                ->where($sourceCondition)
                ->count();
        };

        $otherWeb =
            $otherQuery($webCondition);

        $otherWhatsapp =
            $otherQuery($whatsappCondition);

        $otherRto =
            $otherQuery($rtoCondition);

        $otherDeliveredReorder =
            $otherQuery($deliverReorderCondition);

        $otherAbandoned =
            $otherQuery($abandonedCondition);


        /*
    |--------------------------------------------------------------------------
    | Conversion Rate
    |--------------------------------------------------------------------------
    */

        $successRate = $totalOrders > 0
            ? round(($verified / $totalOrders) * 100, 1)
            : 0;


        /*
    |--------------------------------------------------------------------------
    | API Response
    |--------------------------------------------------------------------------
    */

        return response()->json([
            'success' => true,

            'data' => [

                'staff' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],

                'filters' => [
                    'from' => $fromDate,
                    'to' => $toDate,
                ],

                'pending_work' => [
                    'web' => $pendingWeb,
                    'whatsapp' => $pendingWhatsapp,
                    'rto' => $pendingRto,
                    'delivered_reorder' => $pendingDeliveredReorder,
                    'abandoned' => $pendingAbandoned,
                ],

                'calling_status' => [

                    'total' => [
                        'count' => $totalOrders,
                        'web' => $webOrders,
                        'whatsapp' => $whatsappOrders,
                        'rto' => $rtoOrders,
                        'delivered_reorder' => $deliveredReorderOrders,
                        'abandoned' => $abandonedOrders,
                    ],

                    'pending' => [
                        'count' => $pending,
                        'web' => $pendingWeb,
                        'whatsapp' => $pendingWhatsapp,
                        'rto' => $pendingRto,
                        'delivered_reorder' => $pendingDeliveredReorder,
                        'abandoned' => $pendingAbandoned,
                    ],

                    'verified' => [
                        'count' => $verified,
                        'web' => $verifiedWeb,
                        'whatsapp' => $verifiedWhatsapp,
                        'rto' => $verifiedRto,
                        'delivered_reorder' => $verifiedDeliveredReorder,
                        'abandoned' => $verifiedAbandoned,
                    ],

                    'cancelled' => [
                        'count' => $cancelled,
                        'web' => $cancelledWeb,
                        'whatsapp' => $cancelledWhatsapp,
                        'rto' => $cancelledRto,
                        'delivered_reorder' => $cancelledDeliveredReorder,
                        'abandoned' => $cancelledAbandoned,
                    ],

                    'not_connected' => [
                        'count' => $notConnected,
                        'web' => $notConnectedWeb,
                        'whatsapp' => $notConnectedWhatsapp,
                        'rto' => $notConnectedRto,
                        'delivered_reorder' => $notConnectedDeliveredReorder,
                        'abandoned' => $notConnectedAbandoned,
                    ],

                    'same_order' => [
                        'count' => $sameOrder,
                        'web' => $sameOrderWeb,
                        'whatsapp' => $sameOrderWhatsapp,
                        'rto' => $sameOrderRto,
                        'delivered_reorder' => $sameOrderDeliveredReorder,
                        'abandoned' => $sameOrderAbandoned,
                    ],

                    'other' => [
                        'count' => $other,
                        'web' => $otherWeb,
                        'whatsapp' => $otherWhatsapp,
                        'rto' => $otherRto,
                        'delivered_reorder' => $otherDeliveredReorder,
                        'abandoned' => $otherAbandoned,
                    ],
                ],

                'conversion' => [
                    'rate' => $successRate,
                    'verified' => $verified,
                    'total' => $totalOrders,
                ],
            ],
        ]);
    }

    public function outForDelivery(Request $request)
    {
        $orders = $this->getStaffDeliveryOrders(
            $request,
            'Out for Delivery'
        );

        return response()->json([
            'success' => true,
            'message' => 'Out for Delivery orders fetched successfully.',
            'data' => $orders,
        ]);
    }


    public function logout(Request $request)
    {
        $request->user()
            ->currentAccessToken()
            ->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logout successful'
        ]);
    }
    public function store(Request $request)
    {
        try {

            $data = $request->json()->all();

            if (empty($data)) {
                $data = $request->all();
            }

            Log::info('Knowlarity Log Push Received', [
                'data' => $data
            ]);

            /*
        |--------------------------------------------------------------------------
        | Required Field
        |--------------------------------------------------------------------------
        */

            if (empty($data['call_uuid'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'call_uuid is required'
                ], 422);
            }

            /*
        |--------------------------------------------------------------------------
        | Check Duplicate Call UUID
        |--------------------------------------------------------------------------
        */

            $existing = DB::table('knowlarity_log')
                ->where('call_uuid', $data['call_uuid'])
                ->first();

            if ($existing) {
                return response()->json([
                    'success' => true,
                    'message' => 'Call log already exists',
                    'call_uuid' => $existing->call_uuid,
                    'id' => $existing->id
                ], 200);
            }

            /*
        |--------------------------------------------------------------------------
        | Insert Call Log
        |--------------------------------------------------------------------------
        */

            $id = DB::table('knowlarity_log')->insertGetId([
                'call_date' => $data['call_date'] ?? null,
                'call_time' => $data['call_time'] ?? null,
                'caller_number' => $data['caller_number'] ?? null,
                'call_direction' => $data['call_direction'] ?? null,
                'called_number' => $data['called_number'] ?? null,
                'call_status' => $data['call_status'] ?? null,
                'agent_number' => $data['agent_number'] ?? null,
                'call_transfer_status' => $data['call_transfer_status'] ?? null,
                'caller_duration' => $data['caller_duration'] ?? null,
                'recording_url' => $data['recording_url'] ?? null,
                'call_uuid' => $data['call_uuid'],
                'hangup_cause' => $data['hangup_cause'] ?? null,
                'menu_extension' => $data['menu_extension'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            /*
        |--------------------------------------------------------------------------
        | Success Response
        |--------------------------------------------------------------------------
        */

            return response()->json([
                'success' => true,
                'message' => 'Call log received successfully',
                'call_uuid' => $data['call_uuid'],
                'id' => $id
            ], 200);
        } catch (\Throwable $e) {

            Log::error('Knowlarity Log Push Error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'request' => $request->all()
            ]);

            /*
        |--------------------------------------------------------------------------
        | Local Testing Response
        |--------------------------------------------------------------------------
        */

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
    }



    public function prepaidOrders(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $userId = $user->id;

        $clients = CallingOrder::select(
            'client_id',
            DB::raw('COUNT(*) as total')
        )
            ->where('assigned_to', $userId)
            ->where(function ($q) {
                $q->whereNull('order_source')
                    ->orWhere('order_source', '');
            })
            ->where('status', 'pending')
            ->where('payment_mode', 'paid')
            ->groupBy('client_id')
            ->with('client')
            ->get()
            ->map(function ($row) {
                return [
                    'client_id' => $row->client_id,
                    'client_name' => optional($row->client)->client_name ?? 'Client',
                    'total' => (int) $row->total,
                ];
            })
            ->values();

        $query = CallingOrder::query()
            ->where('assigned_to', $userId)
            ->where(function ($q) {
                $q->whereNull('order_source')
                    ->orWhere('order_source', '');
            })
            ->where('payment_mode', 'paid')
            ->where('status', 'pending');

        if ($request->filled('client_id')) {
            $query->where('client_id', (int) $request->client_id);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);

            $query->where(function ($q) use ($search) {
                $q->where('order_id', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhere('customer_phone', 'like', "%{$search}%")
                    ->orWhere('product_name', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%")
                    ->orWhere('state', 'like', "%{$search}%")
                    ->orWhere('pincode', 'like', "%{$search}%")
                    ->orWhere('shipping_address', 'like', "%{$search}%");
            });
        }

        $orders = $query
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return response()->json([
            'success' => true,
            'message' => 'Prepaid orders fetched successfully.',
            'data' => [
                'orders' => $orders,
                'clients' => $clients,
                'status_label' => 'Web Pending Orders',
                'status_count' => $orders->total(),
            ],
        ]);
    }

    public function prepaidOrderStatus(Request $request, $id)
    {
        $request->validate([
            'status' => ['required', 'in:verified,same_order,not_reachable,cancel'],
        ]);

        $user = $request->user();

        $order = CallingOrder::where('id', $id)
            ->where('assigned_to', $user->id)
            ->where(function ($q) {
                $q->whereNull('order_source')
                    ->orWhere('order_source', '');
            })
            ->where('payment_mode', 'paid')
            ->where('status', 'pending')
            ->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Prepaid order not found or not assigned to you.',
            ], 404);
        }

        $order->status = $request->status;
        $order->save();

        return response()->json([
            'success' => true,
            'message' => 'Order status updated successfully.',
            'data' => $order->fresh(),
        ]);
    }

    public function prepaidOrderUpdate(Request $request, $id)
    {
        $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_phone' => ['required', 'digits:10'],
            'product_name' => ['required', 'string', 'max:1000'],
            'father_name' => ['nullable', 'string', 'max:255'],
            'quantity' => ['required', 'integer', 'min:1'],
            'payment_mode' => ['required', 'in:paid,COD,Prepaid'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'age' => ['nullable', 'integer', 'min:1', 'max:120'],
            'city' => ['required', 'string', 'max:255'],
            'state' => ['required', 'string', 'max:255'],
            'pincode' => ['required', 'digits:6'],
            'shipping_address' => ['required', 'string', 'max:2000'],
            'remarks' => ['nullable', 'string', 'max:2000'],
        ]);

        $user = $request->user();

        $order = CallingOrder::where('id', $id)
            ->where('assigned_to', $user->id)
            ->where(function ($q) {
                $q->whereNull('order_source')
                    ->orWhere('order_source', '');
            })
            ->where('status', 'pending')
            ->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Prepaid order not found or not assigned to you.',
            ], 404);
        }

        $order->update([
            'customer_name' => $request->customer_name,
            'customer_phone' => $request->customer_phone,
            'product_name' => $request->product_name,
            'father_name' => $request->father_name,
            'quantity' => $request->quantity,
            'payment_mode' => $request->payment_mode,
            'amount' => $request->amount,
            'age' => $request->age,
            'city' => $request->city,
            'state' => $request->state,
            'pincode' => $request->pincode,
            'shipping_address' => $request->shipping_address,
            'remarks' => $request->remarks,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Prepaid order updated successfully.',
            'data' => $order->fresh(),
        ]);
    }

    public function webOrders(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $userId = $user->id;

        $clients = CallingOrder::select(
            'client_id',
            DB::raw('COUNT(*) as total')
        )
            ->where('assigned_to', $userId)
            ->where(function ($q) {
                $q->whereNull('order_source')
                    ->orWhere('order_source', '');
            })
            ->where('status', 'pending')
            ->groupBy('client_id')
            ->with('client')
            ->get()
            ->map(function ($row) {
                return [
                    'client_id' => $row->client_id,
                    'client_name' => optional($row->client)->client_name ?? 'Client',
                    'total' => (int) $row->total,
                ];
            })
            ->values();

        $query = CallingOrder::query()
            ->where('assigned_to', $userId)
            ->where(function ($q) {
                $q->whereNull('order_source')
                    ->orWhere('order_source', '');
            })
            ->where('status', 'pending');

        if ($request->filled('client_id')) {
            $query->where('client_id', (int) $request->client_id);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);

            $query->where(function ($q) use ($search) {
                $q->where('order_id', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhere('customer_phone', 'like', "%{$search}%")
                    ->orWhere('product_name', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%")
                    ->orWhere('state', 'like', "%{$search}%")
                    ->orWhere('pincode', 'like', "%{$search}%")
                    ->orWhere('shipping_address', 'like', "%{$search}%");
            });
        }

        $orders = $query->latest('id')
            ->paginate(20)
            ->withQueryString();

        return response()->json([
            'success' => true,
            'message' => 'Web orders fetched successfully.',
            'data' => [
                'orders' => $orders,
                'clients' => $clients,
                'status_label' => 'Web Pending Orders',
                'status_count' => $orders->total(),
            ],
        ]);
    }

    public function webOrderStatus(Request $request, $id)
    {
        $request->validate([
            'status' => ['required', 'in:verified,same_order,not_reachable,cancel'],
        ]);

        $user = $request->user();

        $order = CallingOrder::where('id', $id)
            ->where('assigned_to', $user->id)
            ->where(function ($q) {
                $q->whereNull('order_source')
                    ->orWhere('order_source', '');
            })
            ->where('status', 'pending')
            ->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Web order not found or not assigned to you.',
            ], 404);
        }

        $order->status = $request->status;
        $order->save();

        return response()->json([
            'success' => true,
            'message' => 'Order status updated successfully.',
            'data' => $order->fresh(),
        ]);
    }

    public function webOrderUpdate(Request $request, $id)
    {
        $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_phone' => ['required', 'digits:10'],
            'product_name' => ['required', 'string', 'max:1000'],
            'father_name' => ['nullable', 'string', 'max:255'],
            'quantity' => ['required', 'integer', 'min:1'],
            'payment_mode' => ['required', 'in:COD,Prepaid'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'age' => ['nullable', 'integer', 'min:1', 'max:120'],
            'city' => ['required', 'string', 'max:255'],
            'state' => ['required', 'string', 'max:255'],
            'pincode' => ['required', 'digits:6'],
            'shipping_address' => ['required', 'string', 'max:2000'],
            'remarks' => ['nullable', 'string', 'max:2000'],
        ]);

        $user = $request->user();

        $order = CallingOrder::where('id', $id)
            ->where('assigned_to', $user->id)
            ->where(function ($q) {
                $q->whereNull('order_source')
                    ->orWhere('order_source', '');
            })
            ->where('status', 'pending')
            ->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Web order not found or not assigned to you.',
            ], 404);
        }

        $order->update([
            'customer_name' => $request->customer_name,
            'customer_phone' => $request->customer_phone,
            'product_name' => $request->product_name,
            'father_name' => $request->father_name,
            'quantity' => $request->quantity,
            'payment_mode' => $request->payment_mode,
            'amount' => $request->amount,
            'age' => $request->age,
            'city' => $request->city,
            'state' => $request->state,
            'pincode' => $request->pincode,
            'shipping_address' => $request->shipping_address,
            'remarks' => $request->remarks,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Web order updated successfully.',
            'data' => $order->fresh(),
        ]);
    }

    public function rtoOrders(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $userId = $user->id;

        $clients = CallingOrder::select(
            'client_id',
            DB::raw('COUNT(*) as total')
        )
            ->where('assigned_to', $userId)
            ->where('order_source', 'RTO')
            ->where('status', 'pending')
            ->groupBy('client_id')
            ->with('client')
            ->get()
            ->map(function ($row) {
                return [
                    'client_id' => $row->client_id,
                    'client_name' => optional($row->client)->client_name ?? 'Client',
                    'total' => (int) $row->total,
                ];
            })
            ->values();

        $query = CallingOrder::query()
            ->where('assigned_to', $userId)
            ->where('order_source', 'RTO')
            ->where('status', 'pending');

        if ($request->filled('client_id')) {
            $query->where(
                'client_id',
                (int) $request->client_id
            );
        }

        if ($request->filled('search')) {
            $search = trim($request->search);

            $query->where(function ($q) use ($search) {
                $q->where('order_id', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhere('customer_phone', 'like', "%{$search}%")
                    ->orWhere('product_name', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%")
                    ->orWhere('state', 'like', "%{$search}%")
                    ->orWhere('pincode', 'like', "%{$search}%")
                    ->orWhere('shipping_address', 'like', "%{$search}%");
            });
        }

        $orders = $query
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return response()->json([
            'success' => true,
            'message' => 'RTO orders fetched successfully.',
            'data' => [
                'orders' => $orders,
                'clients' => $clients,
                'status_label' => 'RTO Pending Orders',
                'status_count' => $orders->total(),
            ],
        ]);
    }

    public function rtoOrderStatus(Request $request, $id)
    {
        $request->validate([
            'status' => [
                'required',
                'in:verified,same_order,not_reachable,cancel',
            ],
        ]);

        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $order = CallingOrder::where('id', $id)
            ->where('assigned_to', $user->id)
            ->where('order_source', 'RTO')
            ->where('status', 'pending')
            ->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' =>
                'RTO order not found or not assigned to you.',
            ], 404);
        }

        $order->status = $request->status;
        $order->save();

        return response()->json([
            'success' => true,
            'message' => 'RTO order status updated successfully.',
            'data' => $order->fresh(),
        ]);
    }

    public function rtoOrderUpdate(Request $request, $id)
    {
        $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_phone' => ['required', 'digits:10'],
            'product_name' => ['required', 'string', 'max:1000'],
            'father_name' => ['nullable', 'string', 'max:255'],
            'quantity' => ['required', 'integer', 'min:1'],
            'payment_mode' => ['required', 'in:COD,Prepaid,paid'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'age' => ['nullable', 'integer', 'min:1', 'max:120'],
            'city' => ['required', 'string', 'max:255'],
            'state' => ['required', 'string', 'max:255'],
            'pincode' => ['required', 'digits:6'],
            'shipping_address' => ['required', 'string', 'max:2000'],
            'remarks' => ['nullable', 'string', 'max:2000'],
        ]);

        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $order = CallingOrder::where('id', $id)
            ->where('assigned_to', $user->id)
            ->where('order_source', 'RTO')
            ->where('status', 'pending')
            ->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' =>
                'RTO order not found or not assigned to you.',
            ], 404);
        }

        $order->update([
            'customer_name' => $request->customer_name,
            'customer_phone' => $request->customer_phone,
            'product_name' => $request->product_name,
            'father_name' => $request->father_name,
            'quantity' => $request->quantity,
            'payment_mode' => $request->payment_mode,
            'amount' => $request->amount,
            'age' => $request->age,
            'city' => $request->city,
            'state' => $request->state,
            'pincode' => $request->pincode,
            'shipping_address' => $request->shipping_address,
            'remarks' => $request->remarks,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'RTO order updated successfully.',
            'data' => $order->fresh(),
        ]);
    }

    public function deliverOrders(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $userId = $user->id;

        // Same client grouping logic as the supplied Blade method.
        $clients = CallingOrder::select(
            'client_id',
            DB::raw('COUNT(*) as total')
        )
            ->where('assigned_to', $userId)
            ->where('order_source', 'deliveredreorder')
            ->where('status', 'pending')
            ->groupBy('client_id')
            ->with('client')
            ->get()
            ->map(function ($row) {
                return [
                    'client_id' => $row->client_id,
                    'client_name' => optional($row->client)->client_name ?? 'Client',
                    'total' => (int) $row->total,
                ];
            })
            ->values();

        $query = CallingOrder::query()
            ->where('assigned_to', $userId)
            ->where('order_source', 'deliveredreorder')
            ->where('status', 'pending');

        if ($request->filled('client_id')) {
            $query->where('client_id', (int) $request->client_id);
        }

        // API-only enhancement: server-side search.
        if ($request->filled('search')) {
            $search = trim($request->search);

            $query->where(function ($q) use ($search) {
                $q->where('order_id', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhere('customer_phone', 'like', "%{$search}%")
                    ->orWhere('product_name', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%")
                    ->orWhere('state', 'like', "%{$search}%")
                    ->orWhere('pincode', 'like', "%{$search}%")
                    ->orWhere('shipping_address', 'like', "%{$search}%");
            });
        }

        $orders = $query
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return response()->json([
            'success' => true,
            'message' => 'Delivered reorder orders fetched successfully.',
            'data' => [
                'orders' => $orders,
                'clients' => $clients,
                'status_label' => 'Deliver Pending Orders',
                'status_count' => $orders->total(),
            ],
        ]);
    }

    public function deliverOrderStatus(Request $request, $id)
    {
        $request->validate([
            'status' => [
                'required',
                'in:verified,same_order,not_reachable,cancel',
            ],
        ]);

        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $order = CallingOrder::where('id', $id)
            ->where('assigned_to', $user->id)
            ->where('order_source', 'deliveredreorder')
            ->where('status', 'pending')
            ->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' =>
                'Deliver order not found or not assigned to you.',
            ], 404);
        }

        $order->status = $request->status;
        $order->save();

        return response()->json([
            'success' => true,
            'message' => 'Deliver order status updated successfully.',
            'data' => $order->fresh(),
        ]);
    }

    public function deliverOrderUpdate(Request $request, $id)
    {
        $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_phone' => ['required', 'digits:10'],
            'product_name' => ['required', 'string', 'max:1000'],
            'father_name' => ['nullable', 'string', 'max:255'],
            'quantity' => ['required', 'integer', 'min:1'],
            'payment_mode' => ['required', 'in:COD,Prepaid,paid'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'age' => ['nullable', 'integer', 'min:1', 'max:120'],
            'city' => ['required', 'string', 'max:255'],
            'state' => ['required', 'string', 'max:255'],
            'pincode' => ['required', 'digits:6'],
            'shipping_address' => ['required', 'string', 'max:2000'],
            'remarks' => ['nullable', 'string', 'max:2000'],
        ]);

        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $order = CallingOrder::where('id', $id)
            ->where('assigned_to', $user->id)
            ->where('order_source', 'deliveredreorder')
            ->where('status', 'pending')
            ->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' =>
                'Deliver order not found or not assigned to you.',
            ], 404);
        }

        $order->update([
            'customer_name' => $request->customer_name,
            'customer_phone' => $request->customer_phone,
            'product_name' => $request->product_name,
            'father_name' => $request->father_name,
            'quantity' => $request->quantity,
            'payment_mode' => $request->payment_mode,
            'amount' => $request->amount,
            'age' => $request->age,
            'city' => $request->city,
            'state' => $request->state,
            'pincode' => $request->pincode,
            'shipping_address' => $request->shipping_address,
            'remarks' => $request->remarks,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Deliver order updated successfully.',
            'data' => $order->fresh(),
        ]);
    }

    public function abandonedOrders(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $userId = $user->id;

        // Same client grouping logic as the supplied Blade method.
        $clients = CallingOrder::select(
            'client_id',
            DB::raw('COUNT(*) as total')
        )
            ->where('assigned_to', $userId)
            ->where('order_source', 'shopify_abandoned_checkout')
            ->where('status', 'pending')
            ->groupBy('client_id')
            ->with('client')
            ->get()
            ->map(function ($row) {
                return [
                    'client_id' => $row->client_id,
                    'client_name' => optional($row->client)->client_name ?? 'Client',
                    'total' => (int) $row->total,
                ];
            })
            ->values();

        $query = CallingOrder::query()
            ->where('assigned_to', $userId)
            ->where('order_source', 'shopify_abandoned_checkout')
            ->where('status', 'pending');

        if ($request->filled('client_id')) {
            $query->where('client_id', (int) $request->client_id);
        }

        // API-only enhancement: server-side search.
        if ($request->filled('search')) {
            $search = trim($request->search);

            $query->where(function ($q) use ($search) {
                $q->where('order_id', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhere('customer_phone', 'like', "%{$search}%")
                    ->orWhere('product_name', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%")
                    ->orWhere('state', 'like', "%{$search}%")
                    ->orWhere('pincode', 'like', "%{$search}%")
                    ->orWhere('shipping_address', 'like', "%{$search}%");
            });
        }

        $orders = $query
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return response()->json([
            'success' => true,
            'message' => 'Abandoned checkout orders fetched successfully.',
            'data' => [
                'orders' => $orders,
                'clients' => $clients,
                'status_label' => 'Abandoned Pending Orders',
                'status_count' => $orders->total(),
            ],
        ]);
    }

    public function abandonedOrderStatus(Request $request, $id)
    {
        $request->validate([
            'status' => [
                'required',
                'in:verified,same_order,not_reachable,cancel',
            ],
        ]);

        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $order = CallingOrder::where('id', $id)
            ->where('assigned_to', $user->id)
            ->where('order_source', 'shopify_abandoned_checkout')
            ->where('status', 'pending')
            ->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' =>
                'Abandoned order not found or not assigned to you.',
            ], 404);
        }

        $order->status = $request->status;
        $order->save();

        return response()->json([
            'success' => true,
            'message' => 'Abandoned order status updated successfully.',
            'data' => $order->fresh(),
        ]);
    }

    public function abandonedOrderUpdate(Request $request, $id)
    {
        $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_phone' => ['required', 'digits:10'],
            'product_name' => ['required', 'string', 'max:1000'],
            'father_name' => ['nullable', 'string', 'max:255'],
            'quantity' => ['required', 'integer', 'min:1'],
            'payment_mode' => ['required', 'in:COD,Prepaid,paid'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'age' => ['nullable', 'integer', 'min:1', 'max:120'],
            'city' => ['required', 'string', 'max:255'],
            'state' => ['required', 'string', 'max:255'],
            'pincode' => ['required', 'digits:6'],
            'shipping_address' => ['required', 'string', 'max:2000'],
            'remarks' => ['nullable', 'string', 'max:2000'],
        ]);

        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $order = CallingOrder::where('id', $id)
            ->where('assigned_to', $user->id)
            ->where('order_source', 'shopify_abandoned_checkout')
            ->where('status', 'pending')
            ->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' =>
                'Abandoned order not found or not assigned to you.',
            ], 404);
        }

        $order->update([
            'customer_name' => $request->customer_name,
            'customer_phone' => $request->customer_phone,
            'product_name' => $request->product_name,
            'father_name' => $request->father_name,
            'quantity' => $request->quantity,
            'payment_mode' => $request->payment_mode,
            'amount' => $request->amount,
            'age' => $request->age,
            'city' => $request->city,
            'state' => $request->state,
            'pincode' => $request->pincode,
            'shipping_address' => $request->shipping_address,
            'remarks' => $request->remarks,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Abandoned order updated successfully.',
            'data' => $order->fresh(),
        ]);
    }
}
