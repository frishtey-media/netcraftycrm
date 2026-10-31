<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\callingorder;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

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


        $fromDate = $request->filled('from')
            ? $request->from
            : now()->format('Y-m-d');

        $toDate = $request->filled('to')
            ? $request->to
            : now()->format('Y-m-d');

        $from = Carbon::parse($fromDate)->startOfDay();
        $to   = Carbon::parse($toDate)->endOfDay();

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
    public function staffWhatsappLeads(Request $request)
    {
        $staffId = Auth::guard('calling_user')->id();

        if (!$staffId) {
            abort(403, 'Please login as calling staff.');
        }

        $statuses = [
            'pending',
            'not_reachable',
            'not reachable',
            'not-reachable',
        ];

        // Last 3 calendar days: today and previous 2 days
        $from = now()->startOfDay()->subDays(2);
        $to = now()->startOfDay()->addDay();

        /*
    |--------------------------------------------------------------------------
    | Base Query: Normal Assigned + Shared WhatsApp Leads
    |--------------------------------------------------------------------------
    */
        $baseQuery = DB::table('callingorder as c')
            ->whereRaw(
                "LOWER(TRIM(COALESCE(c.order_source, ''))) = ?",
                ['whatsapp']
            )
            ->whereRaw(
                "LOWER(TRIM(COALESCE(c.status, ''))) = ?",
                ['pending']
            )
            ->where(function ($q) {
                $q->whereNull('c.remarks')
                    ->orWhereRaw("TRIM(c.remarks) = ''");
            })
            ->where('c.order_date', '>=', $from)
            ->where('c.order_date', '<', $to)
            ->where(function ($q) use ($staffId) {
                $q->where(function ($normal) use ($staffId) {
                    // Normal assigned leads, excluding leads shared with anyone
                    $normal->where('c.assigned_to', $staffId)
                        ->whereNotExists(function ($sub) {
                            $sub->selectRaw('1')
                                ->from('whatsapp_fresh_lead_shares as s')
                                ->whereColumn('s.callingorder_id', 'c.id');
                        });
                })
                    ->orWhereExists(function ($sub) use ($staffId) {
                        // Leads shared with logged-in staff only
                        $sub->selectRaw('1')
                            ->from('whatsapp_fresh_lead_shares as s')
                            ->whereColumn('s.callingorder_id', 'c.id')
                            ->where('s.shared_with_staff_id', $staffId);
                    });
            });

        $clients = DB::table('clients as cl')
            ->join('callingorder as c', 'c.client_id', '=', 'cl.id')
            ->whereRaw(
                "LOWER(TRIM(COALESCE(c.order_source, ''))) = ?",
                ['whatsapp']
            )
            ->whereIn(
                DB::raw('LOWER(TRIM(c.status))'),
                $statuses
            )
            ->where('c.order_date', '>=', $from)
            ->where('c.order_date', '<', $to)
            ->where(function ($q) use ($staffId) {
                $q->where('c.assigned_to', $staffId)
                    ->orWhereExists(function ($sub) use ($staffId) {
                        $sub->selectRaw('1')
                            ->from('whatsapp_fresh_lead_shares as s')
                            ->whereColumn(
                                's.callingorder_id',
                                'c.id'
                            )
                            ->where(
                                's.shared_with_staff_id',
                                $staffId
                            );
                    });
            })
            ->select(
                'cl.id as client_id',
                'cl.client_name',
                DB::raw('COUNT(DISTINCT c.id) as total')
            )
            ->groupBy('cl.id', 'cl.client_name')
            ->orderBy('cl.client_name')
            ->get();

        $query = clone $baseQuery;

        if ($request->filled('client_id')) {
            $query->where('c.client_id', $request->client_id);
        }

        $orders = $query
            ->select([
                'c.id',
                'c.id as callingorder_id',
                'c.order_id',
                'c.customer_name',
                'c.customer_phone',
                'c.product_name',
                'c.shipping_address',
                'c.city',
                'c.state',
                'c.pincode',
                'c.order_date',
                'c.status',
                'c.remarks',
                'c.client_id',
                'c.payment_mode',
                'c.quantity',
                'c.amount',
                'c.age',
                'c.father_name',
            ])
            ->orderByDesc('c.order_date')
            ->orderByDesc('c.id')
            ->get();

        /*
    |--------------------------------------------------------------------------
    | Show Shared Order ID for Shared Leads
    |--------------------------------------------------------------------------
    */
        $sharedOrderIds = DB::table('whatsapp_fresh_lead_shares')
            ->where('shared_with_staff_id', $staffId)
            ->whereIn(
                'callingorder_id',
                $orders->pluck('callingorder_id')->unique()->values()
            )
            ->select('callingorder_id', 'shared_order_id')
            ->get()
            ->keyBy('callingorder_id');

        $orders->transform(function ($order) use ($sharedOrderIds) {
            $share = $sharedOrderIds->get($order->callingorder_id);

            if ($share && !empty($share->shared_order_id)) {
                $order->order_id = $share->shared_order_id;
                $order->lead_type = 'Shared';
            } else {
                $order->lead_type = 'Normal Assigned';
            }

            return $order;
        });

        return view('calling.whatsapp-leads', [
            'orders'       => $orders,
            'clients'      => $clients,
            'statusLabel'  => 'WhatsApp Leads',
            'statusClass'  => 'warning',
            'statusCount'  => $orders->count(),
        ]);
    }
    public function updateWhatsappLeadStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:verified,same_order,not_reachable,cancel',
        ]);

        $staffId = Auth::guard('calling_user')->id();

        abort_unless($staffId, 403, 'Please login as calling staff.');

        $lead = DB::table('callingorder as c')
            ->where('c.id', $id)
            ->whereRaw(
                "LOWER(TRIM(COALESCE(c.order_source, ''))) = ?",
                ['whatsapp']
            )
            ->where(function ($q) use ($staffId) {
                $q->where('c.assigned_to', $staffId)
                    ->orWhereExists(function ($sub) use ($staffId) {
                        $sub->selectRaw('1')
                            ->from('whatsapp_fresh_lead_shares as s')
                            ->whereColumn('s.callingorder_id', 'c.id')
                            ->where('s.shared_with_staff_id', $staffId);
                    });
            })
            ->select('c.id', 'c.status')
            ->first();

        abort_unless($lead, 404, 'WhatsApp lead not found or access denied.');

        DB::table('callingorder')
            ->where('id', $lead->id)
            ->update([
                'status' => $request->status,
                'updated_at' => now(),
            ]);

        return redirect()->back()->with(
            'success',
            'WhatsApp lead status updated successfully. The update applies to both staff members.'
        );
    }

    public function updateWhatsappLead(Request $request, $id)
    {
        $staffId = Auth::guard('calling_user')->id();

        abort_unless($staffId, 403, 'Please login as calling staff.');

        $data = $request->validate([
            'customer_name'    => 'nullable|string|max:255',
            'customer_phone'   => 'nullable|string|max:30',
            'product_name'     => 'nullable|string|max:1000',
            'father_name'      => 'nullable|string|max:255',
            'quantity'         => 'nullable|integer|min:1',
            'payment_mode'     => 'nullable|in:COD,Prepaid',
            'amount'           => 'nullable|numeric|min:0',
            'age'              => 'nullable|integer|min:0|max:150',
            'city'             => 'nullable|string|max:255',
            'state'            => 'nullable|string|max:255',
            'pincode'          => 'nullable|string|max:20',
            'shipping_address' => 'nullable|string|max:2000',
            'remarks'           => 'nullable|string|max:2000',
        ]);

        $lead = DB::table('callingorder as c')
            ->where('c.id', $id)
            ->whereRaw(
                "LOWER(TRIM(COALESCE(c.order_source, ''))) = ?",
                ['whatsapp']
            )
            ->where(function ($q) use ($staffId) {
                $q->where('c.assigned_to', $staffId)
                    ->orWhereExists(function ($sub) use ($staffId) {
                        $sub->selectRaw('1')
                            ->from('whatsapp_fresh_lead_shares as s')
                            ->whereColumn('s.callingorder_id', 'c.id')
                            ->where('s.shared_with_staff_id', $staffId);
                    });
            })
            ->select('c.id')
            ->first();

        abort_unless($lead, 404, 'WhatsApp lead not found or access denied.');

        DB::table('callingorder')
            ->where('id', $lead->id)
            ->update(array_merge($data, [
                'updated_at' => now(),
            ]));

        return redirect()->back()->with(
            'success',
            'WhatsApp lead updated successfully for both staff members.'
        );
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


        $query = CallingOrder::where('assigned_to', $userId)
            ->where('order_source', 'RTO')
            ->where('status', 'pending');


        if ($request->client_id) {
            $query->where('client_id', $request->client_id);
        }

        $orders = $query
            ->latest()
            ->paginate(50)
            ->withQueryString();

        return view('calling.rtoorders', [
            'orders' => $orders,
            'clients' => $clients,
            'statusLabel' => 'RTO Pending Orders',
            'statusClass' => 'warning',
            'statusCount' => $orders->total()
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



        $query = CallingOrder::where('assigned_to', $userId)
            ->where(function ($q) {
                $q->whereNull('order_source')
                    ->orWhere('order_source', '');
            })
            ->where('status', 'pending');



        if ($request->filled('client_id')) {
            $query->where('client_id', $request->client_id);
        }


        $orders = $query
            ->orderByDesc('order_date')
            ->orderByDesc('id')
            ->paginate(50)
            ->withQueryString();

        return view('calling.weborders', [
            'orders' => $orders,
            'clients' => $clients,
            'statusLabel' => 'Web Pending Orders',
            'statusClass' => 'warning',
            'statusCount' => $orders->total()
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


        $query = CallingOrder::where('assigned_to', $userId)
            ->where(function ($q) {
                $q->whereNull('order_source')
                    ->orWhere('order_source', '');
            })
            ->where('payment_mode', 'paid')
            ->where('status', 'pending');




        if ($request->filled('client_id')) {
            $query->where('client_id', $request->client_id);
        }



        $orders = $query
            ->latest()
            ->paginate(50)
            ->withQueryString();


        return view('calling.prepaidorders', [
            'orders' => $orders,
            'clients' => $clients,
            'statusLabel' => 'Web Pending Orders',
            'statusClass' => 'warning',
            'statusCount' => $orders->total()
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


        $query = CallingOrder::where('assigned_to', $userId)
            ->where(function ($q) {
                $q->whereNull('order_source')
                    ->orWhere('order_source', 'WhatsApp');
            })
            ->where('status', 'pending');


        if ($request->filled('client_id')) {
            $query->where('client_id', $request->client_id);
        }


        $orders = $query
            ->latest()
            ->paginate(50)
            ->withQueryString();

        return view('calling.WhatsApp', [
            'orders' => $orders,
            'clients' => $clients,
            'statusLabel' => 'Whatsapp Pending Orders',
            'statusClass' => 'warning',
            'statusCount' => $orders->total()
        ]);
    }

    public function abandonedordersorders(Request $request)
    {
        $userId = Auth::guard('calling_user')->id();


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


        $query = CallingOrder::where('assigned_to', $userId)
            ->where('order_source', 'shopify_abandoned_checkout')
            ->where('status', 'pending');


        if ($request->client_id) {
            $query->where('client_id', $request->client_id);
        }

        $orders = $query
            ->latest()
            ->paginate(50)
            ->withQueryString();

        return view('calling.abandoned', [
            'orders' => $orders,
            'clients' => $clients,
            'statusLabel' => 'Abandoned Pending Orders',
            'statusClass' => 'warning',
            'statusCount' => $orders->total()
        ]);
    }
    public function deliverordersorders(Request $request)
    {
        $userId = Auth::guard('calling_user')->id();


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


        $query = CallingOrder::where('assigned_to', $userId)
            ->where('order_source', 'deliveredreorder')
            ->where('status', 'pending');


        if ($request->client_id) {
            $query->where('client_id', $request->client_id);
        }

        $orders = $query
            ->latest()
            ->paginate(50)
            ->withQueryString();

        return view('calling.deliverorders', [
            'orders' => $orders,
            'clients' => $clients,
            'statusLabel' => 'Deliver Pending Orders',
            'statusClass' => 'warning',
            'statusCount' => $orders->total()
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
        $orders = $query
            ->latest()
            ->paginate(50)
            ->withQueryString();

        return view('calling.orders', [
            'orders' => $orders,
            'clients' => $clients,
            'statusLabel' => 'Pending',
            'statusClass' => 'danger',
            'statusCount' => $orders->total()
        ]);
    }
    private function getStaffDeliveryOrders(Request $request, $deliveryStatus)
    {
        $userId = Auth::guard('calling_user')->id();

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


        if ($request->filled('client_id')) {
            $query->where(
                'co.client_id',
                $request->client_id
            );
        }

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

        $query->orderByDesc('o.date');

        return $query
            ->paginate(50)
            ->withQueryString();
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



        if ($request->filled('client_id')) {

            $query->where(
                'client_id',
                $request->client_id
            );
        }


        $orders = $query
            ->latest('created_at')
            ->paginate(50)
            ->withQueryString();


        return view('calling.verified', [

            'orders' => $orders,

            'clients' => $clients,

            'statusLabel' => 'Verified',

            'statusClass' => 'success',

            'statusCount' => $orders->total()

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

        $orders = $query
            ->latest()
            ->paginate(50)
            ->withQueryString();

        return view('calling.same_order', [
            'orders' => $orders,
            'clients' => $clients,
            'statusLabel' => 'same_order',
            'statusClass' => 'success',
            'statusCount' => $orders->total()
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

        $orders = $query
            ->latest()
            ->paginate(50)
            ->withQueryString();

        return view('calling.cancel', [
            'orders' => $orders,
            'clients' => $clients,
            'statusLabel' => 'cancel',
            'statusClass' => 'success',
            'statusCount' => $orders->total()
        ]);
    }
    public function notReachable(Request $request)
    {
        $userId = Auth::guard('calling_user')->id();

        $clients = CallingOrder::select(
            'client_id',
            DB::raw('COUNT(*) as total')
        )
            ->where('assigned_to', $userId)
            ->where('status', 'not_reachable')
            ->groupBy('client_id')
            ->with('client')
            ->get();

        $query = CallingOrder::where('assigned_to', $userId)
            ->where('status', 'not_reachable');

        if ($request->filled('client_id')) {
            $query->where('client_id', $request->client_id);
        }


        $orders = $query
            ->latest()
            ->paginate(50)
            ->withQueryString();

        return view('calling.not_reachable', [
            'orders' => $orders,
            'clients' => $clients,
            'statusLabel' => 'Not Reachable',
            'statusClass' => 'secondary',
            'statusCount' => $orders->total()
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
            'remarks' => [
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
            'remarks' =>
            $request->remarks,

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
    private function normalizePhone($phone)
    {
        $phone = preg_replace('/[^0-9]/', '', (string) $phone);

        if (strlen($phone) === 12 && substr($phone, 0, 2) === '91') {
            $phone = substr($phone, 2);
        }

        if (strlen($phone) === 11 && substr($phone, 0, 1) === '0') {
            $phone = substr($phone, 1);
        }

        return $phone;
    }


    public function statusupdate(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:verified,same_order,not_reachable,cancel',
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput()
                ->with('error', 'Please select a valid order status.');
        }

        try {
            $order = CallingOrder::findOrFail($id);

            if ($request->status === 'verified') {

                $phone = $this->normalizePhone($order->customer_phone);

                if (empty($phone)) {
                    return back()->withInput()->with(
                        'error',
                        'Customer phone number is invalid.'
                    );
                }

                /*
            |--------------------------------------------------------------------------
            | 1. FIND CUSTOMER SHIPPING ORDERS
            |--------------------------------------------------------------------------
            */

                $customerOrders = DB::table('orders')
                    ->where('client_id', $order->client_id)
                    ->whereNotNull('customer_phone')
                    ->get([
                        'id',
                        'order_id',
                        'customer_phone',
                        'delivery_status',
                        'payment_mode',
                    ])
                    ->filter(function ($item) use ($phone) {
                        $existingPhone = $this->normalizePhone(
                            $item->customer_phone
                        );

                        return !empty($existingPhone)
                            && $existingPhone === $phone;
                    });

                /*
            |--------------------------------------------------------------------------
            | 2. FIND CURRENT SHIPPING ORDER
            |--------------------------------------------------------------------------
            */

                $shippingOrder = $customerOrders->first(
                    function ($item) use ($order) {
                        return (string) $item->order_id ===
                            (string) $order->order_id;
                    }
                );

                /*
            |--------------------------------------------------------------------------
            | 3. BLOCK CURRENT ORDER IN TRANSIT
            |--------------------------------------------------------------------------
            */

                if ($shippingOrder) {
                    $shippingStatus = strtolower(
                        preg_replace(
                            '/[^a-z]/',
                            '',
                            trim((string) $shippingOrder->delivery_status)
                        )
                    );

                    if (in_array($shippingStatus, [
                        'rtointrasit',
                        'customerintrasit',
                    ], true)) {
                        return back()->withInput()->with(
                            'error',
                            'Verification blocked! Current delivery status is "' .
                                $shippingOrder->delivery_status .
                                '". This order cannot be verified.'
                        );
                    }
                }

                /*
            |--------------------------------------------------------------------------
            | 4. COUNT PREVIOUS RTO RECORDS
            |--------------------------------------------------------------------------
            */

                $rtoCount = $customerOrders->filter(
                    function ($item) use ($order) {
                        // Exclude current shipping order
                        if (
                            (string) $item->order_id ===
                            (string) $order->order_id
                        ) {
                            return false;
                        }

                        $status = strtolower(
                            preg_replace(
                                '/[^a-z]/',
                                '',
                                trim((string) $item->delivery_status)
                            )
                        );

                        return $status === 'rtointrasit';
                    }
                )->count();

                /*
            |--------------------------------------------------------------------------
            | 5. TWO OR MORE RTOS = PREPAID ONLY
            |--------------------------------------------------------------------------
            */

                if ($rtoCount >= 2) {
                    if (!$shippingOrder) {
                        return back()->withInput()->with(
                            'error',
                            'Verification blocked! Current shipping order was not found.'
                        );
                    }

                    $paymentMode = strtolower(
                        trim((string) $shippingOrder->payment_mode)
                    );

                    if ($paymentMode !== 'prepaid') {
                        return back()->withInput()->with(
                            'error',
                            'Verification blocked! Customer has ' .
                                $rtoCount .
                                ' previous RTO records. Only Prepaid orders are allowed.'
                        );
                    }
                }

                /*
            |--------------------------------------------------------------------------
            | 6. SAME DATE + SAME PHONE + SAME PRODUCT DUPLICATE CHECK
            |--------------------------------------------------------------------------
            */

                if (empty($order->order_date)) {
                    return back()->withInput()->with(
                        'error',
                        'Order date is missing. Verification cannot proceed.'
                    );
                }

                $orderDate = Carbon::parse($order->order_date)
                    ->toDateString();

                $verifiedOrders = CallingOrder::where(
                    'client_id',
                    $order->client_id
                )
                    ->whereDate('order_date', $orderDate)
                    ->where('status', 'verified')
                    ->where('id', '!=', $order->id)
                    ->get([
                        'id',
                        'customer_phone',
                        'order_id',
                        'product_name',
                    ]);

                foreach ($verifiedOrders as $existing) {
                    $existingPhone = $this->normalizePhone(
                        $existing->customer_phone
                    );

                    $sameProduct = strtolower(trim(
                        (string) $existing->product_name
                    )) === strtolower(trim(
                        (string) $order->product_name
                    ));

                    if (
                        !empty($existingPhone)
                        && $existingPhone === $phone
                        && $sameProduct
                    ) {
                        return back()->withInput()->with(
                            'error',
                            'Duplicate verification blocked! This customer has already verified this product on ' .
                                Carbon::parse($order->order_date)->format('d-m-Y') .
                                '. Existing Order ID: ' .
                                ($existing->order_id ?? $existing->id)
                        );
                    }
                }
            }

            /*
        |--------------------------------------------------------------------------
        | 7. UPDATE STATUS
        |--------------------------------------------------------------------------
        */

            $order->status = $request->status;
            $order->save();

            return back()->with(
                'success',
                'Order #' . $order->order_id .
                    ' status updated successfully to ' .
                    str_replace('_', ' ', ucfirst($request->status)) . '.'
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::warning('Calling order not found during status update', [
                'order_id' => $id,
            ]);

            return back()->with(
                'error',
                'Order not found. It may have been deleted or is no longer available.'
            );
        } catch (\Throwable $e) {
            Log::error('Calling order status update failed', [
                'order_id' => $id,
                'requested_status' => $request->status,
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->withInput()->with(
                'error',
                config('app.debug')
                    ? 'Technical error: ' . $e->getMessage()
                    : 'Unable to update order status. Please try again or contact support.'
            );
        }
    }


    public function logout()
    {
        Auth::guard('calling_user')->logout();
        return redirect('/calling/login');
    }
}
