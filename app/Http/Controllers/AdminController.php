<?php

namespace App\Http\Controllers;

use App\Models\Barcode;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\LabelSender;
use App\Models\Client;
//use Illuminate\Support\Facades\Http;
//use GuzzleHttp\Client as GuzzleClient;
use Carbon\Carbon;
use App\Models\CallingOrder;
use App\Models\User;
use App\Models\CallingUser;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\VerifiedOrdersExport;

class AdminController extends Controller
{

    public function dashboard()
    {
        $totalOrders = Order::count();
        $totalclients =  Client::count();

        return view('dashboard', compact('totalOrders', 'totalclients'), [
            'barcodes' => Barcode::orderBy('is_used', 'asc')
                ->orderBy('created_at', 'desc')
                ->get(),
        ]);
    }

    public function staffVerified(Request $request)
    {
        $staffId = $request->staff_id;

        $query = CallingOrder::where('assigned_to', $staffId)
            ->where('status', 'verified')
            ->where('is_exported', 0);

        // ✅ DATE FILTER
        if ($request->from && $request->to) {
            $from = Carbon::parse($request->from)->startOfDay();
            $to   = Carbon::parse($request->to)->endOfDay();

            $query->whereBetween('updated_at', [$from, $to]);
        }

        // ✅ CLIENT FILTER (OUTSIDE DATE)
        if ($request->client_id) {
            $query->where('client_id', $request->client_id);
        }

        $orders = $query->latest()->get();

        return view('staff_verified', compact('orders', 'staffId'));
    }
    public function staffVerifiedExport(Request $request)
    {
        $staffId = $request->staff_id;

        $query = CallingOrder::where('assigned_to', $staffId)
            ->where('status', 'verified')
            ->where('is_exported', 0);

        // ✅ DATE FILTER
        if ($request->from && $request->to) {
            $from = Carbon::parse($request->from)->startOfDay();
            $to   = Carbon::parse($request->to)->endOfDay();

            $query->whereBetween('updated_at', [$from, $to]);
        }

        // ✅ CLIENT FILTER (FIXED POSITION)
        if ($request->client_id) {
            $query->where('client_id', $request->client_id);
        }

        $orders = $query->get();

        // 🔥 UPDATE EXPORT STATUS
        if ($orders->count()) {
            CallingOrder::whereIn('id', $orders->pluck('id'))
                ->update(['is_exported' => 1]);
        }

        return Excel::download(
            new VerifiedOrdersExport($orders),
            'staff_verified_' . now()->format('d_m_Y_H_i') . '.xlsx'
        );
    }
    public function ordersdashboard()
    {
        $clients = Client::all();
        $ordersData = [];

        foreach ($clients as $client) {

            $totalOrders = CallingOrder::where('client_id', $client->id)
                ->whereNull('assigned_to')
                ->where('status', 'pending')
                ->whereBetween('order_date', [
                    Carbon::yesterday()->startOfDay(),
                    Carbon::now()
                ])
                ->count();

            $ordersData[] = [
                'client_name' => $client->client_name,
                'client_id' => $client->id,
                'total_orders' => $totalOrders,
            ];
        }


        $staffs = CallingUser::where('status', 1)->get();

        return view('ordersdashboard', compact('ordersData', 'staffs'));
    }

    public function performance(Request $request)
    {
        $hasFilter = $request->from && $request->to;

        $from = $hasFilter
            ? Carbon::parse($request->from)->startOfDay()
            : null;

        $to = $hasFilter
            ? Carbon::parse($request->to)->endOfDay()
            : null;

        $staffs = CallingUser::withCount([

            // TOTAL
            'orders as total_orders' => function ($q) use ($from, $to, $hasFilter) {
                if ($hasFilter) {
                    $q->whereBetween('updated_at', [$from, $to]);
                }
            },

            // ✅ VERIFIED (FIXED)
            'orders as verified_orders' => function ($q) use ($from, $to, $hasFilter) {
                $q->where('status', 'verified');
                //->where('is_exported', 0); // 🔥 IMPORTANT FIX

                if ($hasFilter) {
                    $q->whereBetween('updated_at', [$from, $to]);
                }
            },

            // NOT REACHABLE
            'orders as not_reachable_orders' => function ($q) use ($from, $to, $hasFilter) {
                $q->where('status', 'not_reachable');

                if ($hasFilter) {
                    $q->whereBetween('updated_at', [$from, $to]);
                }
            },

            // PENDING
            'orders as pending_orders' => function ($q) use ($from, $to, $hasFilter) {
                $q->where('status', 'pending');

                if ($hasFilter) {
                    $q->whereBetween('updated_at', [$from, $to]);
                }
            }

        ])->get();


        // ✅ CLIENT DATA FIX
        $clientQuery = CallingOrder::with('client')
            ->whereNotNull('assigned_to');

        if ($hasFilter) {
            $clientQuery->whereBetween('updated_at', [$from, $to]);
        }

        $clientData = $clientQuery
            ->selectRaw('assigned_to, client_id, COUNT(*) as total')
            ->groupBy('assigned_to', 'client_id')
            ->get();

        $clientWise = [];

        foreach ($clientData as $row) {
            $clientWise[$row->assigned_to][] = [
                'client' => $row->client->client_name ?? 'N/A',
                'total' => $row->total
            ];
        }

        return view('performance', compact(
            'staffs',
            'from',
            'to',
            'clientWise'
        ));
    }
    public function assignOrders(Request $request)
    {
        $clientId = $request->client_id;
        $assignData = $request->assign;

        $orders = CallingOrder::where('client_id', $clientId)
            ->whereNull('assigned_to')
            ->where('status', 'pending')
            ->get();

        // 🔥 Validation
        $totalRequested = array_sum($assignData);

        if ($totalRequested > $orders->count()) {
            return back()->with('error', 'Assigned quantity exceeds available orders');
        }

        $orderIndex = 0;

        foreach ($assignData as $staffId => $qty) {

            if ($qty <= 0) continue;

            for ($i = 0; $i < $qty; $i++) {

                if (!isset($orders[$orderIndex])) break;

                $orders[$orderIndex]->assigned_to = $staffId;
                $orders[$orderIndex]->save();

                $orderIndex++;
            }
        }

        return back()->with('success', 'Orders Assigned Successfully');
    }
    public function assignPage()
    {
        $clients = Client::all();
        $staffs = User::where('role', 'staff')->get();

        return view('assign-orders', compact('clients', 'staffs'));
    }


    public function labelsenders()
    {
        $senders = LabelSender::latest()->get();
        return view('labelsenders', compact('senders'));
    }
    public function storeLabelSenders(Request $request)
    {
        $request->validate([
            'customer_name' => 'required|string|max:255|unique:label_senders,customer_name',
            'customer_phone' => 'required|string|max:255',
        ]);

        LabelSender::create([
            'customer_name' => $request->customer_name,
            'customer_phone' => $request->customer_phone,
        ]);

        return redirect()
            ->route('labelsenders')
            ->with('success', 'Label sender saved successfully');
    }
    public function labelgenrate()
    {
        $senders = LabelSender::latest()->get();
        return view('labelgenrate', compact('senders'));
    }
}
