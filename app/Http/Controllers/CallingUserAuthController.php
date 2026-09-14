<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\callingorder;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CallingUserAuthController extends Controller
{
    public function showLogin()
    {
        return view('calling.login');
    }

    public function login(Request $request)
    {
        $credentials = [
            'email'    => $request->email,
            'password' => $request->password,
            'status'   => 1
        ];

        if (Auth::guard('calling_user')->attempt($credentials)) {

            $user = Auth::guard('calling_user')->user();

            if ($user->status != 1) {
                Auth::guard('calling_user')->logout();

                return back()->with(
                    'error',
                    'Your account is inactive.'
                );
            }

            return redirect('/calling/dashboard');
        }

        return back()->with(
            'error',
            'Invalid Credentials or Account Disabled'
        );
    }

    public function dashboard(Request $request)
    {
        $userId = Auth::guard('calling_user')->id();

        /*
    |--------------------------------------------------------------------------
    | DATE FILTER
    |--------------------------------------------------------------------------
    */

        $fromDate = $request->filled('from')
            ? $request->from
            : now()->format('Y-m-d');

        $toDate = $request->filled('to')
            ? $request->to
            : now()->format('Y-m-d');

        $from = Carbon::parse($fromDate)->startOfDay();
        $to   = Carbon::parse($toDate)->endOfDay();


        /*
    |--------------------------------------------------------------------------
    | BASE QUERY
    | Same as Admin Report
    |--------------------------------------------------------------------------
    */

        $baseQuery = CallingOrder::query()
            ->where('assigned_to', $userId)
            ->whereBetween('updated_at', [$from, $to]);

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

        $countBySource = function ($sourceCondition, $status = null) use ($baseQuery) {

            $query = clone $baseQuery;

            if ($status !== null) {
                $query->where('status', $status);
            }

            $query->where($sourceCondition);

            return $query->count();
        };


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

        $pendingWeb = $countBySource($webCondition, 'pending');

        $pendingWhatsapp = $countBySource(
            $whatsappCondition,
            'pending'
        );

        $pendingRto = $countBySource(
            $rtoCondition,
            'pending'
        );

        $pendingDeliveredReorder = $countBySource(
            $deliverReorderCondition,
            'pending'
        );

        $pendingAbandoned = $countBySource(
            $abandonedCondition,
            'pending'
        );

        $verifiedWeb = $countBySource($webCondition, 'verified');

        $verifiedWhatsapp = $countBySource(
            $whatsappCondition,
            'verified'
        );

        $verifiedRto = $countBySource(
            $rtoCondition,
            'verified'
        );

        $verifiedDeliveredReorder = $countBySource(
            $deliverReorderCondition,
            'verified'
        );

        $verifiedAbandoned = $countBySource(
            $abandonedCondition,
            'verified'
        );

        $cancelledWeb = $countBySource($webCondition, 'cancel');

        $cancelledWhatsapp = $countBySource(
            $whatsappCondition,
            'cancel'
        );

        $cancelledRto = $countBySource(
            $rtoCondition,
            'cancel'
        );

        $cancelledDeliveredReorder = $countBySource(
            $deliverReorderCondition,
            'cancel'
        );

        $cancelledAbandoned = $countBySource(
            $abandonedCondition,
            'cancel'
        );

        $notConnectedWeb = $countBySource(
            $webCondition,
            'not_reachable'
        );

        $notConnectedWhatsapp = $countBySource(
            $whatsappCondition,
            'not_reachable'
        );

        $notConnectedRto = $countBySource(
            $rtoCondition,
            'not_reachable'
        );

        $notConnectedDeliveredReorder = $countBySource(
            $deliverReorderCondition,
            'not_reachable'
        );

        $notConnectedAbandoned = $countBySource(
            $abandonedCondition,
            'not_reachable'
        );


        $sameOrderWeb = $countBySource(
            $webCondition,
            'same_order'
        );

        $sameOrderWhatsapp = $countBySource(
            $whatsappCondition,
            'same_order'
        );

        $sameOrderRto = $countBySource(
            $rtoCondition,
            'same_order'
        );

        $sameOrderDeliveredReorder = $countBySource(
            $deliverReorderCondition,
            'same_order'
        );

        $sameOrderAbandoned = $countBySource(
            $abandonedCondition,
            'same_order'
        );

        $otherQuery = function ($sourceCondition) use (
            $baseQuery,
            $standardStatuses
        ) {

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

        $otherWeb = $otherQuery($webCondition);

        $otherWhatsapp = $otherQuery($whatsappCondition);

        $otherRto = $otherQuery($rtoCondition);

        $otherDeliveredReorder =
            $otherQuery($deliverReorderCondition);

        $otherAbandoned =
            $otherQuery($abandonedCondition);

        $successRate = $totalOrders > 0
            ? round(($verified / $totalOrders) * 100, 1)
            : 0;


        return view('calling.dashboard', compact(
            'fromDate',
            'toDate',
            'totalOrders',
            'webOrders',
            'whatsappOrders',
            'rtoOrders',
            'deliveredReorderOrders',
            'abandonedOrders',
            'pending',
            'verified',
            'cancelled',
            'notConnected',
            'sameOrder',
            'other',
            'pendingWeb',
            'pendingWhatsapp',
            'pendingRto',
            'pendingDeliveredReorder',
            'pendingAbandoned',
            'verifiedWeb',
            'verifiedWhatsapp',
            'verifiedRto',
            'verifiedDeliveredReorder',
            'verifiedAbandoned',
            'cancelledWeb',
            'cancelledWhatsapp',
            'cancelledRto',
            'cancelledDeliveredReorder',
            'cancelledAbandoned',
            'notConnectedWeb',
            'notConnectedWhatsapp',
            'notConnectedRto',
            'notConnectedDeliveredReorder',
            'notConnectedAbandoned',
            'sameOrderWeb',
            'sameOrderWhatsapp',
            'sameOrderRto',
            'sameOrderDeliveredReorder',
            'sameOrderAbandoned',
            'otherWeb',
            'otherWhatsapp',
            'otherRto',
            'otherDeliveredReorder',
            'otherAbandoned',
            'successRate'
        ));
    }

    public function trackCall(Request $request)
    {
        $request->validate([
            'callingorder_id' => 'required|integer',
            'order_id'        => 'required',
            'customer_phone'  => 'nullable|string',
            'call_status'     => 'nullable|in:called,not_answered,busy,wrong_number',
        ]);

        $userId = Auth::guard('calling_user')->id();

        DB::table('calling_logs')->insert([
            'callingorder_id' => $request->callingorder_id,
            'order_id'        => $request->order_id,
            'staff_id'        => $userId,
            'customer_phone'  => $request->customer_phone,
            'call_status'     => $request->call_status ?? 'called',
            'called_at'       => now(),
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Call tracked successfully.',
            'called_at' => now()->format('d-m-Y h:i A')
        ]);
    }
    public function rtoorders(Request $request)
    {
        $userId = Auth::guard('calling_user')->id();

        // Only RTO + Pending orders for client tabs/count
        $clients = CallingOrder::select(
            'client_id',
            DB::raw('COUNT(*) as total')
        )
            ->where('assigned_to', $userId)
            ->where('order_source', 'RTO')
            ->where('status', 'pending')
            ->groupBy('client_id')
            ->with('client')
            ->get();

        // Only RTO + Pending orders
        $query = CallingOrder::where('assigned_to', $userId)
            ->where('order_source', 'RTO')
            ->where('status', 'pending');

        // Client filter
        if ($request->client_id) {
            $query->where('client_id', $request->client_id);
        }

        $orders = $query->latest()->get();

        return view('calling.rtoorders', [
            'orders' => $orders,
            'clients' => $clients,
            'statusLabel' => 'RTO Pending Orders',
            'statusClass' => 'warning',
            'statusCount' => $orders->count()
        ]);
    }

    public function weborders(Request $request)
    {
        $userId = Auth::guard('calling_user')->id();

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
            ->get();


        // Only Pending orders where order_source is NULL or empty
        $query = CallingOrder::where('assigned_to', $userId)
            ->where(function ($q) {
                $q->whereNull('order_source')
                    ->orWhere('order_source', '');
            })
            ->where('status', 'pending');


        // Client filter
        if ($request->filled('client_id')) {
            $query->where('client_id', $request->client_id);
        }


        // Latest orders first
        $orders = $query->latest()->get();


        return view('calling.weborders', [
            'orders' => $orders,
            'clients' => $clients,
            'statusLabel' => 'Web Pending Orders',
            'statusClass' => 'warning',
            'statusCount' => $orders->count()
        ]);
    }

    public function prepaidorders(Request $request)
    {
        $userId = Auth::guard('calling_user')->id();

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
            ->get();


        // Only Pending orders where order_source is NULL or empty
        $query = CallingOrder::where('assigned_to', $userId)
            ->where(function ($q) {
                $q->whereNull('order_source')
                    ->orWhere('order_source', '');
            })
            ->where('payment_mode', 'paid')
            ->where('status', 'pending');



        // Client filter
        if ($request->filled('client_id')) {
            $query->where('client_id', $request->client_id);
        }


        // Latest orders first
        $orders = $query->latest()->get();


        return view('calling.prepaidorders', [
            'orders' => $orders,
            'clients' => $clients,
            'statusLabel' => 'Web Pending Orders',
            'statusClass' => 'warning',
            'statusCount' => $orders->count()
        ]);
    }
    public function WhatsApp(Request $request)
    {
        $userId = Auth::guard('calling_user')->id();

        $clients = CallingOrder::select(
            'client_id',
            DB::raw('COUNT(*) as total')
        )
            ->where('assigned_to', $userId)
            ->where(function ($q) {
                $q->whereNull('order_source')
                    ->orWhere('order_source', 'WhatsApp');
            })
            ->where('status', 'pending')
            ->groupBy('client_id')
            ->with('client')
            ->get();


        // Only Pending orders where order_source is NULL or empty
        $query = CallingOrder::where('assigned_to', $userId)
            ->where(function ($q) {
                $q->whereNull('order_source')
                    ->orWhere('order_source', 'WhatsApp');
            })
            ->where('status', 'pending');


        // Client filter
        if ($request->filled('client_id')) {
            $query->where('client_id', $request->client_id);
        }


        // Latest orders first
        $orders = $query->latest()->get();


        return view('calling.WhatsApp', [
            'orders' => $orders,
            'clients' => $clients,
            'statusLabel' => 'Whatsapp Pending Orders',
            'statusClass' => 'warning',
            'statusCount' => $orders->count()
        ]);
    }

    public function abandonedordersorders(Request $request)
    {
        $userId = Auth::guard('calling_user')->id();

        // Only RTO + Pending orders for client tabs/count
        $clients = CallingOrder::select(
            'client_id',
            DB::raw('COUNT(*) as total')
        )
            ->where('assigned_to', $userId)
            ->where('order_source', 'shopify_abandoned_checkout')
            ->where('status', 'pending')
            ->groupBy('client_id')
            ->with('client')
            ->get();

        // Only RTO + Pending orders
        $query = CallingOrder::where('assigned_to', $userId)
            ->where('order_source', 'shopify_abandoned_checkout')
            ->where('status', 'pending');

        // Client filter
        if ($request->client_id) {
            $query->where('client_id', $request->client_id);
        }

        $orders = $query->latest()->get();

        return view('calling.abandoned', [
            'orders' => $orders,
            'clients' => $clients,
            'statusLabel' => 'Abandoned Pending Orders',
            'statusClass' => 'warning',
            'statusCount' => $orders->count()
        ]);
    }
    public function deliverordersorders(Request $request)
    {
        $userId = Auth::guard('calling_user')->id();

        // Only RTO + Pending orders for client tabs/count
        $clients = CallingOrder::select(
            'client_id',
            DB::raw('COUNT(*) as total')
        )
            ->where('assigned_to', $userId)
            ->where('order_source', 'deliveredreorder')
            ->where('status', 'pending')
            ->groupBy('client_id')
            ->with('client')
            ->get();

        // Only RTO + Pending orders
        $query = CallingOrder::where('assigned_to', $userId)
            ->where('order_source', 'deliveredreorder')
            ->where('status', 'pending');

        // Client filter
        if ($request->client_id) {
            $query->where('client_id', $request->client_id);
        }

        $orders = $query->latest()->get();

        return view('calling.deliverorders', [
            'orders' => $orders,
            'clients' => $clients,
            'statusLabel' => 'Deliver Pending Orders',
            'statusClass' => 'warning',
            'statusCount' => $orders->count()
        ]);
    }

    public function orders(Request $request)
    {
        $userId = Auth::guard('calling_user')->id();

        $clients = CallingOrder::select('client_id', DB::raw('COUNT(*) as total'))
            ->where('assigned_to', $userId)
            ->where('status', 'pending')
            ->groupBy('client_id')
            ->with('client')
            ->get();

        $query = CallingOrder::where('assigned_to', $userId)
            ->where('status', 'pending');

        if ($request->client_id) {
            $query->where('client_id', $request->client_id);
        }

        $orders = $query->latest()->get();

        return view('calling.orders', [
            'orders' => $orders,
            'clients' => $clients,
            'statusLabel' => 'Pending',
            'statusClass' => 'danger',
            'statusCount' => $orders->count()
        ]);
    }
    private function getStaffDeliveryOrders(Request $request, $deliveryStatus)
    {
        $userId = Auth::guard('calling_user')->id();

        $query = DB::table('callingorder as co')
            ->join('orders as o', 'o.order_id', '=', 'co.order_id')

            // Latest call for this staff + order
            ->leftJoin(DB::raw('
            (
                SELECT cl1.*
                FROM calling_logs cl1
                INNER JOIN (
                    SELECT order_id, staff_id, MAX(id) as max_id
                    FROM calling_logs
                    GROUP BY order_id, staff_id
                ) cl2
                ON cl1.id = cl2.max_id
            ) as cl
        '), function ($join) {
                $join->on('cl.order_id', '=', 'co.order_id')
                    ->on('cl.staff_id', '=', 'co.assigned_to');
            })

            ->where('co.assigned_to', $userId)

            ->where('o.delivery_status', $deliveryStatus)

            ->select(
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

                // CALL TRACKING
                'cl.id as call_log_id',
                'cl.call_status',
                'cl.called_at'
            )

            ->orderByDesc('o.date');

        // CLIENT FILTER
        if ($request->filled('client_id')) {
            $query->where('co.client_id', $request->client_id);
        }

        return $query->get();
    }
    private function getStaffDeliveryCounts()
    {
        $userId = Auth::guard('calling_user')->id();

        $ofd = DB::table('callingorder as co')
            ->join('orders as o', 'o.order_id', '=', 'co.order_id')
            ->where('co.assigned_to', $userId)
            ->where('o.delivery_status', 'Out for Delivery')
            ->count();

        $onhold = DB::table('callingorder as co')
            ->join('orders as o', 'o.order_id', '=', 'co.order_id')
            ->where('co.assigned_to', $userId)
            ->where('o.delivery_status', 'On Hold')
            ->count();

        return [
            'ofd' => $ofd,
            'onhold' => $onhold,
        ];
    }
    public function orderCounts()
    {
        $counts = $this->getStaffDeliveryCounts();

        return response()->json($counts);
    }
    public function ofd(Request $request)
    {
        $orders = $this->getStaffDeliveryOrders(
            $request,
            'Out for Delivery'
        );

        return view('calling.ofd', compact('orders'));
    }


    public function onhold(Request $request)
    {
        $orders = $this->getStaffDeliveryOrders(
            $request,
            'On Hold'
        );

        return view('calling.onhold', compact('orders'));
    }


    public function verified(Request $request)
    {
        $userId = Auth::guard('calling_user')->id();

        if (!$userId) {
            abort(403);
        }

        $clients = CallingOrder::select(
            'client_id',
            DB::raw('COUNT(*) as total')
        )
            ->where('assigned_to', $userId)
            ->where('status', 'verified')
            ->groupBy('client_id')
            ->with('client')
            ->get();


        $query = CallingOrder::where('assigned_to', $userId)
            ->where('status', 'verified');


        // Client Filter
        if ($request->filled('client_id')) {

            $query->where(
                'client_id',
                $request->client_id
            );
        }


        $orders = $query
            ->latest('created_at')
            ->get();


        return view('calling.verified', [

            'orders' => $orders,

            'clients' => $clients,

            'statusLabel' => 'Verified',

            'statusClass' => 'success',

            'statusCount' => $orders->count()

        ]);
    }

    public function same_order(Request $request)
    {
        $userId = Auth::guard('calling_user')->id();

        $clients = CallingOrder::select('client_id', DB::raw('COUNT(*) as total'))
            ->where('assigned_to', $userId)
            ->where('status', 'same_order')
            ->groupBy('client_id')
            ->with('client')
            ->get();

        $query = CallingOrder::where('assigned_to', $userId)
            ->where('status', 'same_order');

        if ($request->client_id) {
            $query->where('client_id', $request->client_id);
        }

        $orders = $query->latest()->get();

        return view('calling.same_order', [
            'orders' => $orders,
            'clients' => $clients,
            'statusLabel' => 'same_order',
            'statusClass' => 'success',
            'statusCount' => $orders->count()
        ]);
    }

    public function cancel(Request $request)
    {
        $userId = Auth::guard('calling_user')->id();

        $clients = CallingOrder::select('client_id', DB::raw('COUNT(*) as total'))
            ->where('assigned_to', $userId)
            ->where('status', 'cancel')
            ->groupBy('client_id')
            ->with('client')
            ->get();

        $query = CallingOrder::where('assigned_to', $userId)
            ->where('status', 'cancel');

        if ($request->client_id) {
            $query->where('client_id', $request->client_id);
        }

        $orders = $query->latest()->get();

        return view('calling.cancel', [
            'orders' => $orders,
            'clients' => $clients,
            'statusLabel' => 'cancel',
            'statusClass' => 'success',
            'statusCount' => $orders->count()
        ]);
    }
    public function notReachable(Request $request)
    {
        $userId = Auth::guard('calling_user')->id();

        $clients = CallingOrder::select('client_id', DB::raw('COUNT(*) as total'))
            ->where('assigned_to', $userId)
            ->where('status', 'not_reachable')
            ->groupBy('client_id')
            ->with('client')
            ->get();

        $query = CallingOrder::where('assigned_to', $userId)
            ->where('status', 'not_reachable');

        if ($request->client_id) {
            $query->where('client_id', $request->client_id);
        }

        $orders = $query->latest()->get();

        return view('calling.not_reachable', [
            'orders' => $orders,
            'clients' => $clients,
            'statusLabel' => 'Not Reachable',
            'statusClass' => 'secondary',
            'statusCount' => $orders->count()
        ]);
    }
    public function update(Request $request, $id)
    {
        $request->validate([

            'customer_name' => [
                'required',
                'string',
                'max:255',
                'regex:/^[A-Za-z0-9\s.,\/\-()]+$/'
            ],
            'payment_mode' => [
                'required',
                'in:COD,Prepaid'
            ],
            'customer_phone' => [
                'required',
                'regex:/^[0-9]{10}$/'
            ],

            'product_name' => [
                'required',
                'string',
                'max:255'
            ],


            'father_name' => [
                'nullable',
                'string',
                'max:255',
                'regex:/^[A-Za-z0-9\s.,\/\-()]+$/'
            ],

            'quantity' => [
                'required',
                'integer',
                'min:1'
            ],

            'amount' => [
                'required',
                'numeric',
                'min:0.01'
            ],

            'age' => [
                'required',
                'integer',
                'min:1',
                'max:120'
            ],

            'city' => [
                'required',
                'string',
                'max:255',
                'regex:/^[A-Za-z\s.\-]+$/'
            ],

            'state' => [
                'required',
                'string',
                'max:255',
                'regex:/^[A-Za-z\s.\-]+$/'
            ],

            'pincode' => [
                'required',
                'regex:/^[0-9]{6}$/'
            ],

            'shipping_address' => [
                'required',
                'string',
                'max:1000',
                'regex:/^[A-Za-z0-9\s.,\/\-#()]+$/'
            ],

        ], [

            'customer_name.regex' =>
            'Customer name must be in English only.',

            'customer_phone.regex' =>
            'Phone number must contain exactly 10 digits.',

            'father_name.regex' =>
            'Father name must be in English only.',

            'city.regex' =>
            'City must be in English only.',

            'state.regex' =>
            'State must be in English only.',

            'pincode.regex' =>
            'Pincode must contain exactly 6 digits.',

            'shipping_address.regex' =>
            'Shipping address must be in English only.',

        ]);


        $order = CallingOrder::findOrFail($id);


        $order->update([

            'customer_name' =>
            $request->customer_name,

            'father_name' =>
            $request->father_name,

            'product_name' =>
            $request->product_name,

            'customer_phone' =>
            $request->customer_phone,

            'quantity' =>
            $request->quantity,
            'payment_mode' => $request->payment_mode,
            'amount' =>
            $request->amount,

            'age' =>
            $request->age,

            'city' =>
            $request->city,

            'state' =>
            $request->state,

            'pincode' =>
            $request->pincode,

            'shipping_address' =>
            $request->shipping_address,

            'status' =>
            $request->status ?? $order->status,
        ]);


        return back()->with(
            'success',
            'Order Updated Successfully'
        );
    }
    public function update1(Request $request, $id)
    {
        $request->validate([

            'quantity' => [
                'required',
                'integer',
                'min:1'
            ],

            'payment_mode' => [
                'required',
                'in:COD,Prepaid'
            ],

            'amount' => [
                'required',
                'numeric',
                'gt:0'
            ],

        ], [

            'quantity.required' =>
            'Quantity is required.',

            'quantity.integer' =>
            'Quantity must be a number.',

            'quantity.min' =>
            'Quantity must be at least 1.',

            'payment_mode.required' =>
            'Please select payment mode.',

            'payment_mode.in' =>
            'Please select COD or Prepaid.',

            'amount.required' =>
            'Price is required.',

            'amount.numeric' =>
            'Price must be a valid number.',

            'amount.gt' =>
            'Price must be greater than 0.',

        ]);


        $order = CallingOrder::findOrFail($id);


        $order->update([

            'quantity' =>
            $request->quantity,

            'payment_mode' =>
            $request->payment_mode,

            'amount' =>
            $request->amount,

        ]);


        return back()->with(
            'success',
            'Order Updated Successfully'
        );
    }
    public function statusupdate(Request $request, $id)
    {
        $order = CallingOrder::findOrFail($id);

        $order->update([

            'status'          => $request->status ?? $order->status, // fallback safe
        ]);

        return back()->with('success', 'Order Updated');
    }
    public function logout()
    {
        Auth::guard('calling_user')->logout();
        return redirect('/calling/login');
    }
}
