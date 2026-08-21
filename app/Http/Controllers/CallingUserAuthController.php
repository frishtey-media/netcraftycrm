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


        /*
    |--------------------------------------------------------------------------
    | SOURCE CONDITIONS
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
    | HELPER - COUNT BY STATUS + SOURCE
    |--------------------------------------------------------------------------
    */

        $countBySource = function ($sourceCondition, $status = null) use ($baseQuery) {

            $query = clone $baseQuery;

            if ($status !== null) {
                $query->where('status', $status);
            }

            $query->where($sourceCondition);

            return $query->count();
        };


        /*
    |--------------------------------------------------------------------------
    | TOTAL ORDERS
    |--------------------------------------------------------------------------
    */

        $totalOrders = (clone $baseQuery)->count();


        /*
    |--------------------------------------------------------------------------
    | TOTAL SOURCE COUNTS
    |--------------------------------------------------------------------------
    */

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
    | CALLING STATUS TOTALS
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
    | OTHER
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
    | PENDING WORK - SOURCE WISE
    |--------------------------------------------------------------------------
    */

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


        /*
    |--------------------------------------------------------------------------
    | VERIFIED - SOURCE WISE
    |--------------------------------------------------------------------------
    */

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


        /*
    |--------------------------------------------------------------------------
    | CANCELLED - SOURCE WISE
    |--------------------------------------------------------------------------
    */

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


        /*
    |--------------------------------------------------------------------------
    | NOT CONNECTED - SOURCE WISE
    |--------------------------------------------------------------------------
    */

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


        /*
    |--------------------------------------------------------------------------
    | SAME ORDER - SOURCE WISE
    |--------------------------------------------------------------------------
    */

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


        /*
    |--------------------------------------------------------------------------
    | OTHER - SOURCE WISE
    |--------------------------------------------------------------------------
    */

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


        /*
    |--------------------------------------------------------------------------
    | CONVERSION
    |--------------------------------------------------------------------------
    */

        $successRate = $totalOrders > 0
            ? round(($verified / $totalOrders) * 100, 1)
            : 0;


        /*
    |--------------------------------------------------------------------------
    | VIEW
    |--------------------------------------------------------------------------
    */

        return view('calling.dashboard', compact(

            // Dates
            'fromDate',
            'toDate',

            // Total
            'totalOrders',

            // Sources
            'webOrders',
            'whatsappOrders',
            'rtoOrders',
            'deliveredReorderOrders',
            'abandonedOrders',

            // Status totals
            'pending',
            'verified',
            'cancelled',
            'notConnected',
            'sameOrder',
            'other',

            // Pending source wise
            'pendingWeb',
            'pendingWhatsapp',
            'pendingRto',
            'pendingDeliveredReorder',
            'pendingAbandoned',

            // Verified source wise
            'verifiedWeb',
            'verifiedWhatsapp',
            'verifiedRto',
            'verifiedDeliveredReorder',
            'verifiedAbandoned',

            // Cancelled source wise
            'cancelledWeb',
            'cancelledWhatsapp',
            'cancelledRto',
            'cancelledDeliveredReorder',
            'cancelledAbandoned',

            // Not connected source wise
            'notConnectedWeb',
            'notConnectedWhatsapp',
            'notConnectedRto',
            'notConnectedDeliveredReorder',
            'notConnectedAbandoned',

            // Same order source wise
            'sameOrderWeb',
            'sameOrderWhatsapp',
            'sameOrderRto',
            'sameOrderDeliveredReorder',
            'sameOrderAbandoned',

            // Other source wise
            'otherWeb',
            'otherWhatsapp',
            'otherRto',
            'otherDeliveredReorder',
            'otherAbandoned',

            // Conversion
            'successRate'
        ));
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

            // OPTIONAL
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
