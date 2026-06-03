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


    private function isClient()
    {
        return auth()->check() && auth()->user()->role === 'client';
    }

    private function clientId()
    {
        return auth()->user()?->client_id;
    }

    public function dashboard()
    {
        $user = auth()->user();

        if ($this->isClient()) {

            $totalOrders = Order::where(
                'client_id',
                $this->clientId()
            )->count();

            $totalclients = 1;

            $barcodes = Barcode::where('client_id', $this->clientId())
                ->orderBy('is_used')
                ->orderByDesc('created_at')
                ->get();
        } else {

            $totalOrders = Order::count();

            $totalclients = Client::count();

            $barcodes = Barcode::orderBy('is_used')
                ->orderByDesc('created_at')
                ->get();
        }

        return view('dashboard', compact(
            'totalOrders',
            'totalclients',
            'barcodes'
        ));
    }

    public function staffVerified(Request $request)
    {
        $staffId = $request->staff_id;

        $query = CallingOrder::query()
            ->where('assigned_to', $staffId)
            ->where('status', 'verified')
            ->where('is_exported', 0);

        // Client sirf apna data dekhega
        if ($this->isClient()) {
            $query->where('client_id', $this->clientId());
        }

        // Date Filter
        if ($request->filled('from') && $request->filled('to')) {

            $from = Carbon::parse($request->from)->startOfDay();
            $to   = Carbon::parse($request->to)->endOfDay();

            $query->whereBetween('updated_at', [$from, $to]);
        }

        // Sirf Super Admin client filter use kar sakta hai
        if (!$this->isClient() && $request->filled('client_id')) {
            $query->where('client_id', $request->client_id);
        }

        $orders = $query->latest()->get();

        return view('staff_verified', compact(
            'orders',
            'staffId'
        ));
    }
    public function staffVerifiedExport(Request $request)
    {
        $staffId = $request->staff_id;

        $query = CallingOrder::with(['staff', 'client'])
            ->where('assigned_to', $staffId)
            ->where('status', 'verified')
            ->where('is_exported', 0);

        // Client Login
        if ($this->isClient()) {

            $query->where(
                'client_id',
                $this->clientId()
            );
        } else {

            // Super Admin Client Filter
            if ($request->filled('client_id')) {
                $query->where(
                    'client_id',
                    $request->client_id
                );
            }
        }

        // Date Filter
        if ($request->filled('from') && $request->filled('to')) {

            $from = Carbon::parse($request->from)->startOfDay();
            $to   = Carbon::parse($request->to)->endOfDay();

            $query->whereBetween(
                'updated_at',
                [$from, $to]
            );
        }

        $orders = $query->get();

        // Mark Exported
        if ($orders->isNotEmpty()) {

            CallingOrder::whereIn(
                'id',
                $orders->pluck('id')
            )->update([
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
        // Client Block
        if ($this->isClient()) {
            abort(403, 'Unauthorized Access');
        }

        $request->validate([
            'from_staff' => 'required',
            'to_staff'   => 'required',
            'remark'     => 'required'
        ]);

        $orders = CallingOrder::where(
            'assigned_to',
            $request->from_staff
        )
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

        return back()->with(
            'success',
            'Orders shifted successfully'
        );
    }
    public function ordersdashboard()
    {
        $from = Carbon::yesterday()->startOfDay();
        $to   = Carbon::now();

        // ================= CLIENT LOGIN =================
        if ($this->isClient()) {

            $clientId = $this->clientId();

            $ordersData = Client::where('id', $clientId)
                ->withCount([
                    'orders as total_orders' => function ($q) use ($from, $to) {

                        $q->whereNull('assigned_to')
                            ->where('status', 'pending')
                            ->whereBetween('order_date', [$from, $to]);
                    }
                ])
                ->get()
                ->map(function ($client) {

                    return [
                        'client_name'  => $client->client_name,
                        'client_id'    => $client->id,
                        'total_orders' => $client->total_orders
                    ];
                });

            $waClients = Conversation::select(
                'client_id',
                DB::raw('COUNT(*) as total')
            )
                ->where('client_id', $clientId)
                ->whereBetween('updated_at', [$from, $to])
                ->groupBy('client_id')
                ->with('client')
                ->get();

            // client can assign orders
            $allStaff = CallingUser::all();

            return view('ordersdashboard', [
                'ordersData' => $ordersData,
                'waClients'  => $waClients,
                'staff'      => collect(),
                'allStaff'   => $allStaff
            ]);
        }

        // ================= SUPER ADMIN =================

        $ordersData = Client::withCount([
            'orders as total_orders' => function ($q) use ($from, $to) {

                $q->whereNull('assigned_to')
                    ->where('status', 'pending')
                    ->whereBetween('order_date', [$from, $to]);
            }
        ])
            ->get()
            ->map(function ($client) {

                return [
                    'client_name'  => $client->client_name,
                    'client_id'    => $client->id,
                    'total_orders' => $client->total_orders
                ];
            });

        $waClients = Conversation::select(
            'client_id',
            DB::raw('COUNT(*) as total')
        )
            ->whereBetween('updated_at', [$from, $to])
            ->groupBy('client_id')
            ->with('client')
            ->get();

        $staff = Conversation::select(
            'assigned_to',
            DB::raw('COUNT(*) as total')
        )
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

        // Client login => filter by client_id
        $clientId = $this->isClient()
            ? $this->clientId()
            : null;

        // ================= STAFF PERFORMANCE =================

        $staffs = CallingUser::withCount([

            // TOTAL ORDERS
            'orders as total_orders' => function ($q) use ($from, $to, $clientId) {

                if ($clientId) {
                    $q->where('client_id', $clientId);
                }

                $q->whereBetween('updated_at', [$from, $to]);
            },

            // WEB VERIFIED
            'orders as web_verified_orders' => function ($q) use ($from, $to, $clientId) {

                if ($clientId) {
                    $q->where('client_id', $clientId);
                }

                $q->where('status', 'verified')
                    ->whereNull('order_source')
                    ->whereBetween('updated_at', [$from, $to]);
            },

            // WHATSAPP VERIFIED
            'orders as whatsapp_verified_orders' => function ($q) use ($from, $to, $clientId) {

                if ($clientId) {
                    $q->where('client_id', $clientId);
                }

                $q->where('status', 'verified')
                    ->where('order_source', 'whatsapp')
                    ->whereBetween('updated_at', [$from, $to]);
            },

            // PENDING
            'orders as pending_orders' => function ($q) use ($from, $to, $clientId) {

                if ($clientId) {
                    $q->where('client_id', $clientId);
                }

                $q->where('status', 'pending')
                    ->whereBetween('updated_at', [$from, $to]);
            },

            // NOT REACHABLE
            'orders as not_reachable_orders' => function ($q) use ($from, $to, $clientId) {

                if ($clientId) {
                    $q->where('client_id', $clientId);
                }

                $q->where('status', 'not_reachable')
                    ->whereBetween('updated_at', [$from, $to]);
            },

        ])->get();

        // ================= WHATSAPP PERFORMANCE =================

        $waData = Conversation::select(
            'assigned_to',
            DB::raw('COUNT(*) as wa_total'),
            DB::raw("SUM(CASE WHEN status = 'verified' THEN 1 ELSE 0 END) as wa_verified"),
            DB::raw("SUM(CASE WHEN status = 'open' THEN 1 ELSE 0 END) as wa_pending")
        )

            ->when($clientId, function ($q) use ($clientId) {
                $q->where('client_id', $clientId);
            })

            ->whereBetween('updated_at', [$from, $to])
            ->groupBy('assigned_to')
            ->get()
            ->keyBy('assigned_to');

        foreach ($staffs as $staff) {

            $wa = $waData[$staff->id] ?? null;

            $staff->wa_total    = $wa->wa_total ?? 0;
            $staff->wa_verified = $wa->wa_verified ?? 0;
            $staff->wa_pending  = $wa->wa_pending ?? 0;
        }

        // ================= CLIENT WISE BREAKUP =================

        $clientData = CallingOrder::with('client')

            ->when($clientId, function ($q) use ($clientId) {
                $q->where('client_id', $clientId);
            })

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

        // ================= STAFF LIST =================

        $allStaff = $this->isClient()
            ? collect()
            : CallingUser::all();

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
        $request->validate([
            'client_id' => 'required|exists:clients,id',
            'assign'    => 'required|array'
        ]);

        $clientId = $request->client_id;

        // Client sirf apne orders assign kar sakta hai
        if ($this->isClient() && $clientId != $this->clientId()) {

            abort(403, 'Unauthorized Access');
        }

        $assignData = $request->assign;

        $orders = CallingOrder::where('client_id', $clientId)
            ->whereNull('assigned_to')
            ->where('status', 'pending')
            ->inRandomOrder()
            ->get();

        $totalRequested = array_sum($assignData);

        if ($totalRequested > $orders->count()) {

            return back()->with(
                'error',
                'Assigned quantity exceeds available orders'
            );
        }

        $orderIndex = 0;

        foreach ($assignData as $staffId => $qty) {

            if ($qty <= 0) {
                continue;
            }

            for ($i = 0; $i < $qty; $i++) {

                if (!isset($orders[$orderIndex])) {
                    break;
                }

                $orders[$orderIndex]->assigned_to = $staffId;
                $orders[$orderIndex]->save();

                $orderIndex++;
            }
        }

        return back()->with(
            'success',
            'Orders Assigned Successfully'
        );
    }
    public function assignPage()
    {
        if ($this->isClient()) {

            $clients = Client::where(
                'id',
                $this->clientId()
            )->get();
        } else {

            $clients = Client::all();
        }

        $staffs = User::where('role', 'staff')->get();

        return view(
            'assign-orders',
            compact('clients', 'staffs')
        );
    }


    public function labelsenders()
    {
        if ($this->isClient()) {

            $senders = LabelSender::where(
                'client_id',
                $this->clientId()
            )->latest()->get();

            $clients = Client::where(
                'id',
                $this->clientId()
            )->get();
        } else {

            $senders = LabelSender::latest()->get();

            $clients = Client::orderBy('client_name')->get();
        }

        return view(
            'labelsenders',
            compact(
                'senders',
                'clients'
            )
        );
    }
    public function storeLabelSenders(Request $request)
    {
        $request->validate([
            'customer_name'  => 'required|string|max:255',
            'customer_phone' => 'required|string|max:255',
        ]);

        $clientId = $this->isClient()
            ? $this->clientId()
            : $request->client_id;

        LabelSender::create([
            'client_id'      => $clientId,
            'customer_name'  => $request->customer_name,
            'customer_phone' => $request->customer_phone,
        ]);

        return redirect()
            ->route('labelsenders')
            ->with(
                'success',
                'Label sender saved successfully'
            );
    }
    public function labelgenrate()
    {
        if ($this->isClient()) {

            $senders = LabelSender::where(
                'client_id',
                $this->clientId()
            )
                ->latest()
                ->get();
        } else {

            $senders = LabelSender::latest()->get();
        }

        return view(
            'labelgenrate',
            compact('senders')
        );
    }
}
