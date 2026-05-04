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

        $clients = CallingOrder::select('client_id', DB::raw('COUNT(*) as total'))
            ->where('assigned_to', $userId)
            ->where('status', 'verified')
            ->groupBy('client_id')
            ->with('client')
            ->get();

        $query = CallingOrder::where('assigned_to', $userId)
            ->where('status', 'verified');

        if ($request->client_id) {
            $query->where('client_id', $request->client_id);
        }

        $orders = $query->latest()->get();

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
