<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CallingOrder;
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
        $credentials = $request->only('email', 'password');

        if (Auth::guard('calling_user')->attempt($credentials)) {
            return redirect('/calling/dashboard');
        }

        return back()->with('error', 'Invalid Credentials');
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
    public function orders(Request $request)
    {
        $userId = Auth::guard('calling_user')->id();

        // 🔥 ONLY THOSE CLIENTS JINKE ORDERS ASSIGNED HAIN
        $clients = CallingOrder::select('client_id', DB::raw('COUNT(*) as total'))
            ->where('assigned_to', $userId)
            ->groupBy('client_id')
            ->with('client') // relation required
            ->get();

        // 🔥 FILTER
        $query = CallingOrder::where('assigned_to', $userId);

        if ($request->client_id) {
            $query->where('client_id', $request->client_id);
        }

        $orders = $query->latest()->get();

        return view('calling.orders', compact('orders', 'clients'));
    }




    public function verified()
    {
        $userId = Auth::guard('calling_user')->id();

        $orders = CallingOrder::where('assigned_to', $userId)
            ->where('status', 'verified')
            ->latest()
            ->get();

        return view('calling.verified', compact('orders'));
    }
    public function notReachable()
    {
        $userId = Auth::guard('calling_user')->id();

        $orders = CallingOrder::where('assigned_to', $userId)
            ->where('status', 'not_reachable')
            ->latest()
            ->get();

        return view('calling.not_reachable', compact('orders'));
    }
    public function update(Request $request, $id)
    {
        $order = CallingOrder::findOrFail($id);

        $order->update([
            'customer_name'   => $request->customer_name,
            'customer_phone'  => $request->customer_phone,
            'city'            => $request->city,
            'state'           => $request->state,
            'pincode'         => $request->pincode,
            'shipping_address' => $request->shipping_address,
            'status'          => $request->status ?? $order->status, // fallback safe
        ]);

        return back()->with('success', 'Order Updated');
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
