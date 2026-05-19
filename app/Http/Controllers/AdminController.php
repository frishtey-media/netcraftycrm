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
use Illuminate\Support\Facades\DB;
use App\Models\Conversation;

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


        if ($request->from && $request->to) {
            $from = Carbon::parse($request->from)->startOfDay();
            $to   = Carbon::parse($request->to)->endOfDay();

            $query->whereBetween('updated_at', [$from, $to]);
        }


        if ($request->client_id) {
            $query->where('client_id', $request->client_id);
        }

        $orders = $query->latest()->get();

        return view('staff_verified', compact('orders', 'staffId'));
    }
    public function staffVerifiedExport(Request $request)
    {
        $staffId = $request->staff_id;

        $query = CallingOrder::with(['staff', 'client'])
            ->where('assigned_to', $staffId)
            ->where('status', 'verified')
            ->where('is_exported', 0);

        // Date Filter
        if ($request->from && $request->to) {

            $from = Carbon::parse($request->from)->startOfDay();
            $to   = Carbon::parse($request->to)->endOfDay();

            $query->whereBetween('updated_at', [$from, $to]);
        }

        // Client Filter
        if ($request->client_id) {
            $query->where('client_id', $request->client_id);
        }

        $orders = $query->get();

        // Mark Exported
        if ($orders->count()) {

            CallingOrder::whereIn('id', $orders->pluck('id'))
                ->update([
                    'is_exported' => 1
                ]);
        }

        return Excel::download(
            new VerifiedOrdersExport($orders),
            'staff_verified_' . now()->format('d_m_Y_H_i') . '.xlsx'
        );
    }


    public function shiftOrders(Request $request)
    {
        $request->validate([
            'from_staff' => 'required',
            'to_staff'   => 'required',
            'remark'     => 'required'
        ]);

        $orders = CallingOrder::where('assigned_to', $request->from_staff)
            ->where('status', 'pending')
            ->get();

        foreach ($orders as $order) {
            $order->assigned_to = $request->to_staff;
            $order->save();


            DB::table('order_shift_logs')->insert([
                'order_id'   => $order->id,
                'from_staff' => $request->from_staff,
                'to_staff'   => $request->to_staff,
                'remark'     => $request->remark,
                'created_at' => now()
            ]);
        }

        return back()->with('success', 'Orders shifted successfully');
    }
    public function ordersdashboard()
    {
        $from = Carbon::yesterday()->startOfDay();
        $to   = Carbon::now();

        // 🔥 CLIENTS WITH PENDING ORDERS (OPTIMIZED)
        $ordersData = Client::withCount([
            'orders as total_orders' => function ($q) use ($from, $to) {
                $q->whereNull('assigned_to')
                    ->where('status', 'pending')
                    ->whereBetween('order_date', [$from, $to]);
            }
        ])->get()->map(function ($client) {
            return [
                'client_name' => $client->client_name,
                'client_id'   => $client->id,
                'total_orders' => $client->total_orders
            ];
        });

        // 🔥 WHATSAPP CLIENT-WISE (DATE FILTER ADDED)
        $waClients = Conversation::select('client_id', DB::raw('COUNT(*) as total'))
            ->whereBetween('updated_at', [$from, $to])
            ->groupBy('client_id')
            ->with('client')
            ->get();

        // 🔥 STAFF-WISE WA PERFORMANCE
        $staff = Conversation::select('assigned_to', DB::raw('COUNT(*) as total'))
            ->whereBetween('updated_at', [$from, $to])
            ->groupBy('assigned_to')
            ->with('staff')
            ->get();

        return view('ordersdashboard', [
            'ordersData' => $ordersData,
            'waClients'  => $waClients,
            'staff'      => $staff,
            'allStaff'   => CallingUser::all()
        ]);
    }

    public function performance(Request $request)
    {
        $from = Carbon::parse($request->from ?? now())->startOfDay();
        $to   = Carbon::parse($request->to ?? now())->endOfDay();

        // ================= ORDERS =================
        $staffs = CallingUser::withCount([

            // TOTAL WEB ORDERS
            'orders as total_orders' => function ($q) use ($from, $to) {
                $q->whereBetween('updated_at', [$from, $to]);
            },

            // WEB VERIFIED
            'orders as web_verified_orders' => function ($q) use ($from, $to) {
                $q->where('status', 'verified')
                    ->whereNull('order_source')
                    ->whereBetween('updated_at', [$from, $to]);
            },

            // WHATSAPP VERIFIED FROM CALLING ORDER
            'orders as whatsapp_verified_orders' => function ($q) use ($from, $to) {
                $q->where('status', 'verified')
                    ->where('order_source', 'whatsapp')
                    ->whereBetween('updated_at', [$from, $to]);
            },

            // PENDING
            'orders as pending_orders' => function ($q) use ($from, $to) {
                $q->where('status', 'pending')
                    ->whereBetween('updated_at', [$from, $to]);
            },

            // NOT REACHABLE
            'orders as not_reachable_orders' => function ($q) use ($from, $to) {
                $q->where('status', 'not_reachable')
                    ->whereBetween('updated_at', [$from, $to]);
            },

        ])->get();

        // ================= WHATSAPP =================
        $waData = Conversation::select(
            'assigned_to',
            DB::raw('COUNT(*) as wa_total'),
            DB::raw("SUM(CASE WHEN status = 'verified' THEN 1 ELSE 0 END) as wa_verified"),
            DB::raw("SUM(CASE WHEN status = 'open' THEN 1 ELSE 0 END) as wa_pending")
        )
            ->whereBetween('updated_at', [$from, $to])
            ->groupBy('assigned_to')
            ->get()
            ->keyBy('assigned_to');

        // ================= MERGE =================
        foreach ($staffs as $staff) {
            $wa = $waData[$staff->id] ?? null;

            $staff->wa_total = $wa->wa_total ?? 0;
            $staff->wa_verified = $wa->wa_verified ?? 0;
            $staff->wa_pending = $wa->wa_pending ?? 0;
        }

        // ================= CLIENT WISE =================
        $clientData = CallingOrder::with('client')
            ->whereBetween('updated_at', [$from, $to])
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

        $allStaff = CallingUser::all();

        return view('performance', compact(
            'staffs',
            'from',
            'to',
            'clientWise',
            'allStaff'
        ));
    }
    public function assignOrders(Request $request)
    {
        $clientId = $request->client_id;
        $assignData = $request->assign;

        $orders = CallingOrder::where('client_id', $clientId)
            ->whereNull('assigned_to')
            ->where('status', 'pending')
            ->get()
            ->shuffle();

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
