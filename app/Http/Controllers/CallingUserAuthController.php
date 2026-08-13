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

    public function dashboard()
    {
        $userId = Auth::guard('calling_user')->id();
        $year = now()->year;

        // 🔥 ALL ORDERS (single query)
        $allOrders = CallingOrder::where('assigned_to', $userId)->get();

        // 🔥 Pending Orders
        $orders = $allOrders->where('status', 'pending')->sortByDesc('created_at');

        // 🔥 COUNTS
        $total = $allOrders->where('order_source', '!=', 'whatsapp')->count();
        $verified = $allOrders->where('status', 'verified')->count();
        $pending = $allOrders->where('status', 'pending')->count();
        $notReachable = $allOrders->where('status', 'not_reachable')->count();
        $whatsappOrders = $allOrders->where('order_source', 'whatsapp')->count();

        // 🎯 SUCCESS RATE
        $successRate = $total > 0 ? round(($verified / $total) * 100, 1) : 0;

        // 🔥 MONTH NAMES
        $months = collect(range(1, 12))->map(fn($m) => date('M', mktime(0, 0, 0, $m, 1)));

        // 🔥 GROUPED DATA (FAST QUERY)
        $monthly = CallingOrder::select(
            DB::raw('MONTH(created_at) as month'),
            DB::raw("SUM(CASE WHEN order_source != 'whatsapp' THEN 1 ELSE 0 END) as web"),
            DB::raw("SUM(CASE WHEN order_source = 'whatsapp' THEN 1 ELSE 0 END) as whatsapp")
        )
            ->where('assigned_to', $userId)
            ->whereYear('created_at', $year)
            ->groupBy('month')
            ->pluck('web', 'month');

        $monthlyWA = CallingOrder::select(
            DB::raw('MONTH(created_at) as month'),
            DB::raw("COUNT(*) as total")
        )
            ->where('assigned_to', $userId)
            ->whereYear('created_at', $year)
            ->where('order_source', 'whatsapp')
            ->groupBy('month')
            ->pluck('total', 'month');

        $monthlyVerified = CallingOrder::select(
            DB::raw('MONTH(updated_at) as month'),
            DB::raw("COUNT(*) as total")
        )
            ->where('assigned_to', $userId)
            ->whereYear('updated_at', $year)
            ->where('status', 'verified')
            ->groupBy('month')
            ->pluck('total', 'month');

        $monthlyNR = CallingOrder::select(
            DB::raw('MONTH(updated_at) as month'),
            DB::raw("COUNT(*) as total")
        )
            ->where('assigned_to', $userId)
            ->whereYear('updated_at', $year)
            ->where('status', 'not_reachable')
            ->groupBy('month')
            ->pluck('total', 'month');

        // 🔥 FINAL ARRAY (12 months fix)
        $webData = [];
        $waData = [];
        $verifiedData = [];
        $nrData = [];

        for ($i = 1; $i <= 12; $i++) {
            $webData[] = $monthly[$i] ?? 0;
            $waData[] = $monthlyWA[$i] ?? 0;
            $verifiedData[] = $monthlyVerified[$i] ?? 0;
            $nrData[] = $monthlyNR[$i] ?? 0;
        }

        return view('calling.dashboard', compact(
            'orders',
            'total',
            'verified',
            'pending',
            'notReachable',
            'whatsappOrders',
            'successRate',
            'months',
            'webData',
            'waData',
            'verifiedData',
            'nrData'
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
