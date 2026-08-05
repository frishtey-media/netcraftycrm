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
use App\Models\callingorder;
use App\Models\User;
use App\Models\CallingUser;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\VerifiedOrdersExport;
use Illuminate\Support\Facades\DB;
use App\Models\conversation;
use App\Models\Payment;
use App\Exports\StaffPerformanceExport;
use App\Exports\StaffPerformanceOrdersExport;
use App\Exports\SelectedStaffExport;

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
        $dashboardQuery = Order::query();

        // Client Login
        if ($this->isClient()) {

            $dashboardQuery->where('client_id', $this->clientId());

            $totalclients = 1;

            $barcodes = Barcode::where('client_id', $this->clientId())
                ->orderBy('is_used')
                ->orderByDesc('created_at')
                ->get();
        } else {

            $totalclients = Client::count();

            $barcodes = Barcode::orderBy('is_used')
                ->orderByDesc('created_at')
                ->get();
        }
        $dashboardQuery1 = Order::query();

        // Last 30 Days Dashboard
        $dashboardQuery->whereDate(
            'created_at',
            '>=',
            Carbon::now()->subDays(30)
        );

        $totalOrders = (clone $dashboardQuery1)->count();

        $ourSidePending = (clone $dashboardQuery)
            ->where(function ($q) {
                $q->whereNull('delivery_status')
                    ->orWhere('delivery_status', '');
            })
            ->count();

        $transit7 = (clone $dashboardQuery)
            ->whereIn('delivery_status', [
                'In Transit',
                'Out For Delivery'
            ])
            ->whereDate('intransitdate', '<=', now()->subDays(7))
            ->count();

        $rto5 = (clone $dashboardQuery)
            ->where('delivery_status', 'RTO')
            ->where('rtorecivedsts', 0)
            ->whereDate('rtodate', '<=', now()->subDays(5))
            ->count();
        // $paymentCount = Payment::count();

        // $paymentAmount = Payment::sum('cod_value');

        $paymentQuery = Payment::whereDate(
            'bill_date',
            '>=',
            now()->subDays(30)
        );

        $paymentCount = (clone $paymentQuery)->count();

        $paymentAmount = (clone $paymentQuery)->sum('cod_value');
        return view('dashboard', compact(
            'barcodes',
            'totalOrders',
            'totalclients',
            'ourSidePending',
            'transit7',
            'paymentCount',
            'paymentAmount',
            'rto5'
        ));
    }
    public function dashboardOrders(Request $request)
    {
        $query = Order::query();

        // Client Login
        if ($this->isClient()) {
            $query->where('client_id', $this->clientId());
        }

        // Last 30 Days
        $query->whereDate(
            'created_at',
            '>=',
            now()->subDays(30)
        );

        switch ($request->type) {

            case 'not_booked':

                $query->where(function ($q) {

                    $q->whereNull('delivery_status')
                        ->orWhere('delivery_status', '');
                });

                break;

            case 'transit7':

                $query->whereIn('delivery_status', [
                    'In Transit',
                    'Out For Delivery'
                ])
                    ->whereDate(
                        'created_at',
                        '<=',
                        now()->subDays(7)
                    );

                break;

            case 'rto5':

                $query->where('delivery_status', 'RTO')
                    ->where('rtorecivedsts', 0)
                    ->whereDate(
                        'created_at',
                        '<=',
                        now()->subDays(5)
                    );

                break;
        }

        return response()->json(

            $query->select(
                'order_id',
                'barcode',
                'customer_name',
                'customer_phone',
                'delivery_status',
                'amount',
                'created_at'
            )
                ->latest()
                ->get()

        );
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
            $query->where('client_id', $this->clientId());
        } else {
            if ($request->filled('client_id')) {
                $query->where('client_id', $request->client_id);
            }
        }

        // Date Filter
        if ($request->filled('from') && $request->filled('to')) {
            $from = Carbon::parse($request->from)->startOfDay();
            $to   = Carbon::parse($request->to)->endOfDay();

            $query->whereBetween('updated_at', [$from, $to]);
        }

        // Product Wise Sort
        $orders = $query
            ->orderBy('product_name', 'ASC')
            ->orderBy('id', 'ASC')
            ->get();

        // Mark Exported
        if ($orders->isNotEmpty()) {
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
    public function exportSelected1(Request $request)
    {
        $staffIds = is_array($request->staff_ids)
            ? $request->staff_ids
            : explode(',', $request->staff_ids);

        $query = CallingOrder::whereIn('assigned_to', $staffIds)

            ->where('status', 'verified')

            ->where('is_exported', 0)

            ->whereBetween('updated_at', [
                Carbon::parse($request->from)->startOfDay(),
                Carbon::parse($request->to)->endOfDay()
            ]);

        if ($request->filled('client_id')) {
            $query->where('client_id', $request->client_id);
        }

        $orders = $query->get();

        if ($orders->isEmpty()) {

            return back()->with(
                'error',
                'No new verified orders available for export.'
            );
        }

        // Mark exported
        CallingOrder::whereIn('id', $orders->pluck('id'))
            ->update([
                'is_exported' => 1,

            ]);

        return Excel::download(
            new VerifiedOrdersExport($orders),
            'Verified_Orders_' . now()->format('Ymd_His') . '.xlsx'
        );
    }

    public function verifySelected(Request $request)
    {
        $from = \Carbon\Carbon::parse($request->from)->startOfDay();
        $to   = \Carbon\Carbon::parse($request->to)->endOfDay();

        $updated = CallingOrder::whereIn('assigned_to', $request->staff_ids)
            ->where('status', 'pending')
            ->whereBetween('updated_at', [$from, $to])
            ->update([
                'status' => 'verified'
            ]);

        return response()->json([
            'updated' => $updated,
            'message' => "$updated Orders Verified Successfully"
        ]);
    }
    public function orderDetails(Request $request)
    {
        /*
    |--------------------------------------------------------------------------
    | Staff
    |--------------------------------------------------------------------------
    */

        $request->validate([
            'staff_id' => 'required|exists:calling_users,id',
        ]);

        $staff = CallingUser::findOrFail($request->staff_id);


        /*
    |--------------------------------------------------------------------------
    | Date Range
    |--------------------------------------------------------------------------
    */

        $from = $request->filled('from')
            ? Carbon::parse($request->from)->startOfDay()
            : Carbon::today()->startOfDay();

        $to = $request->filled('to')
            ? Carbon::parse($request->to)->endOfDay()
            : Carbon::today()->endOfDay();


        /*
    |--------------------------------------------------------------------------
    | Base Query
    |--------------------------------------------------------------------------
    */

        $query = CallingOrder::query()
            ->where('assigned_to', $request->staff_id)
            ->whereBetween('updated_at', [$from, $to]);


        // ======================================================
        // ORDER SOURCE FILTER
        // ======================================================

        if ($request->filled('order_source')) {

            if ($request->order_source === 'web') {

                // Existing Web records can have NULL / empty / web
                $query->where(function ($q) {

                    $q->whereNull('order_source')
                        ->orWhere('order_source', '')
                        ->orWhereRaw('LOWER(TRIM(order_source)) = ?', ['web']);
                });
            } elseif ($request->order_source === 'whatsapp') {

                $query->whereRaw(
                    'LOWER(TRIM(order_source)) = ?',
                    ['whatsapp']
                );
            }
        }





        // ======================================================
        // STATUS FILTER
        // ======================================================

        if ($request->filled('status')) {

            if ($request->status === 'other') {

                $query->where(function ($q) {

                    $q->whereNotIn('status', [
                        'pending',
                        'verified',
                        'cancel',
                        'not_reachable',
                        'same_order'
                    ])
                        ->orWhereNull('status');
                });
            } else {

                $query->where(
                    'status',
                    $request->status
                );
            }
        }



        /*
    |--------------------------------------------------------------------------
    | Search
    |--------------------------------------------------------------------------
    */

        if ($request->filled('search')) {

            $search = trim($request->search);

            $query->where(function ($q) use ($search) {

                $q->where('order_id', 'LIKE', "%{$search}%")
                    ->orWhere('customer_name', 'LIKE', "%{$search}%")
                    ->orWhere('customer_phone', 'LIKE', "%{$search}%")
                    ->orWhere('product_name', 'LIKE', "%{$search}%")
                    ->orWhere('remarks', 'LIKE', "%{$search}%");
            });
        }




        $summaryQuery = clone $query;




        // ======================================================
        // SUMMARY COUNTS
        // ======================================================

        $totalOrders = (clone $query)->count();

        $webOrders = (clone $query)
            ->where(function ($q) {
                $q->whereNull('order_source')
                    ->orWhere('order_source', 'web');
            })
            ->count();

        $whatsappOrders = (clone $query)
            ->where('order_source', 'whatsapp')
            ->count();


        // ======================================================
        // PENDING
        // ======================================================

        $pendingOrders = (clone $query)
            ->where('status', 'pending')
            ->count();

        $pendingWeb = (clone $query)
            ->where('status', 'pending')
            ->where(function ($q) {
                $q->whereNull('order_source')
                    ->orWhere('order_source', 'web');
            })
            ->count();

        $pendingWhatsapp = (clone $query)
            ->where('status', 'pending')
            ->where('order_source', 'whatsapp')
            ->count();


        // ======================================================
        // VERIFIED
        // ======================================================

        $verifiedOrders = (clone $query)
            ->where('status', 'verified')
            ->count();

        $verifiedWeb = (clone $query)
            ->where('status', 'verified')
            ->where(function ($q) {
                $q->whereNull('order_source')
                    ->orWhere('order_source', 'web');
            })
            ->count();

        $verifiedWhatsapp = (clone $query)
            ->where('status', 'verified')
            ->where('order_source', 'whatsapp')
            ->count();


        // ======================================================
        // CANCEL
        // ======================================================

        $cancelOrders = (clone $query)
            ->where('status', 'cancel')
            ->count();

        $cancelWeb = (clone $query)
            ->where('status', 'cancel')
            ->where(function ($q) {
                $q->whereNull('order_source')
                    ->orWhere('order_source', 'web');
            })
            ->count();

        $cancelWhatsapp = (clone $query)
            ->where('status', 'cancel')
            ->where('order_source', 'whatsapp')
            ->count();


        // ======================================================
        // NOT REACHABLE
        // ======================================================

        $notReachableOrders = (clone $query)
            ->where('status', 'not_reachable')
            ->count();

        $notReachableWeb = (clone $query)
            ->where('status', 'not_reachable')
            ->where(function ($q) {
                $q->whereNull('order_source')
                    ->orWhere('order_source', 'web');
            })
            ->count();

        $notReachableWhatsapp = (clone $query)
            ->where('status', 'not_reachable')
            ->where('order_source', 'whatsapp')
            ->count();


        // ======================================================
        // SAME ORDER
        // ======================================================

        $sameOrderOrders = (clone $query)
            ->where('status', 'same_order')
            ->count();

        $sameOrderWeb = (clone $query)
            ->where('status', 'same_order')
            ->where(function ($q) {
                $q->whereNull('order_source')
                    ->orWhere('order_source', 'web');
            })
            ->count();

        $sameOrderWhatsapp = (clone $query)
            ->where('status', 'same_order')
            ->where('order_source', 'whatsapp')
            ->count();


        // ======================================================
        // OTHER
        // Any status outside our standard statuses
        // ======================================================

        $standardStatuses = [
            'pending',
            'verified',
            'cancel',
            'not_reachable',
            'same_order'
        ];

        $otherOrders = (clone $query)
            ->where(function ($q) use ($standardStatuses) {
                $q->whereNotIn('status', $standardStatuses)
                    ->orWhereNull('status');
            })
            ->count();

        $otherWeb = (clone $query)
            ->where(function ($q) use ($standardStatuses) {
                $q->whereNotIn('status', $standardStatuses)
                    ->orWhereNull('status');
            })
            ->where(function ($q) {
                $q->whereNull('order_source')
                    ->orWhere('order_source', 'web');
            })
            ->count();

        $otherWhatsapp = (clone $query)
            ->where(function ($q) use ($standardStatuses) {
                $q->whereNotIn('status', $standardStatuses)
                    ->orWhereNull('status');
            })
            ->where('order_source', 'whatsapp')
            ->count();


        // ======================================================
        // PAYMENT
        // ======================================================

        $codOrders = (clone $query)
            ->whereIn('payment_mode', ['COD', 'cod'])
            ->count();

        $prepaidOrders = (clone $query)
            ->whereIn('payment_mode', ['Prepaid', 'prepaid', 'PREPAID'])
            ->count();



        //  $clients = Client::orderBy('client_name')->get();

        $orders = (clone $query)
            ->with('client')
            ->orderByDesc('updated_at')
            ->paginate(100)
            ->withQueryString();
        return view('performance-orders', compact(

            'orders',
            'staff',
            'from',
            'to',

            'totalOrders',
            'webOrders',
            'whatsappOrders',

            'pendingOrders',
            'pendingWeb',
            'pendingWhatsapp',

            'verifiedOrders',
            'verifiedWeb',
            'verifiedWhatsapp',

            'cancelOrders',
            'cancelWeb',
            'cancelWhatsapp',

            'notReachableOrders',
            'notReachableWeb',
            'notReachableWhatsapp',

            'sameOrderOrders',
            'sameOrderWeb',
            'sameOrderWhatsapp',

            'otherOrders',
            'otherWeb',
            'otherWhatsapp',

            'codOrders',
            'prepaidOrders'
        ));
    }
    public function exportOrderDetails(Request $request)
    {
        $request->validate([
            'staff_id' => 'required|exists:calling_users,id',
        ]);

        $staff = CallingUser::findOrFail(
            $request->staff_id
        );


        $fileName =
            'Performance-'
            . str_replace(
                ' ',
                '-',
                $staff->name
            )
            . '-'
            . now()->format('d-m-Y-H-i-s')
            . '.xlsx';


        return Excel::download(
            new StaffPerformanceOrdersExport($request),
            $fileName
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
            $allStaff = CallingUser::where('status', '1')->get();
            //   dd($allStaff->pluck('name', 'status'));
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
        $from = $request->filled('from')
            ? Carbon::parse($request->from)->startOfDay()
            : now()->startOfDay();

        $to = $request->filled('to')
            ? Carbon::parse($request->to)->endOfDay()
            : now()->endOfDay();

        /*
|--------------------------------------------------------------------------
| CLIENT FILTER
|--------------------------------------------------------------------------

|
*/

        if ($this->isClient()) {

            // Client user can only see own data
            $clientId = $this->clientId();
        } else {

            // Super Admin selected client
            $clientId = $request->filled('client_id')
                ? (int) $request->client_id
                : null;
        }




        if ($this->isClient()) {

            $clients = Client::where('id', $this->clientId())
                ->orderBy('client_name')
                ->get();
        } else {

            $clients = Client::orderBy('client_name')
                ->get();
        }

        // =========================
        // STAFF PERFORMANCE
        // =========================

        $staffs = CallingUser::withCount([

            'orders as total_orders' => function ($q) use ($from, $to, $clientId) {

                if ($clientId) {
                    $q->where('client_id', $clientId);
                }

                $q->whereBetween('updated_at', [$from, $to]);
            },

            'orders as web_verified_orders' => function ($q) use ($from, $to, $clientId) {

                if ($clientId) {
                    $q->where('client_id', $clientId);
                }

                $q->where('status', 'verified')
                    ->whereNull('order_source')
                    ->whereBetween('updated_at', [$from, $to]);
            },

            'orders as whatsapp_verified_orders' => function ($q) use ($from, $to, $clientId) {

                if ($clientId) {
                    $q->where('client_id', $clientId);
                }

                $q->where('status', 'verified')
                    ->where('order_source', 'whatsapp')
                    ->whereBetween('updated_at', [$from, $to]);
            },

            'orders as pending_orders' => function ($q) use ($from, $to, $clientId) {

                if ($clientId) {
                    $q->where('client_id', $clientId);
                }

                $q->where('status', 'pending')
                    ->whereBetween('updated_at', [$from, $to]);
            },

            'orders as not_reachable_orders' => function ($q) use ($from, $to, $clientId) {

                if ($clientId) {
                    $q->where('client_id', $clientId);
                }

                $q->where('status', 'not_reachable')
                    ->whereBetween('updated_at', [$from, $to]);
            },

            'orders as same_order' => function ($q) use ($from, $to, $clientId) {

                if ($clientId) {
                    $q->where('client_id', $clientId);
                }

                $q->where('status', 'same_order')
                    ->whereBetween('updated_at', [$from, $to]);
            },

            'orders as cancel' => function ($q) use ($from, $to, $clientId) {

                if ($clientId) {
                    $q->where('client_id', $clientId);
                }

                $q->where('status', 'cancel')
                    ->whereBetween('updated_at', [$from, $to]);
            },

        ])->get();

        // =========================
        // WHATSAPP PERFORMANCE
        // =========================

        $waData = Conversation::select(
            'assigned_to',
            DB::raw('COUNT(*) as wa_total'),
            DB::raw("SUM(CASE WHEN status='verified' THEN 1 ELSE 0 END) as wa_verified"),
            DB::raw("SUM(CASE WHEN status='open' THEN 1 ELSE 0 END) as wa_pending")
        )

            ->when($clientId, function ($q) use ($clientId) {
                $q->where('client_id', $clientId);
            })

            ->whereBetween('created_at', [$from, $to])

            ->groupBy('assigned_to')
            ->get()
            ->keyBy('assigned_to');
        $isClientUser = $this->isClient();
        foreach ($staffs as $staff) {

            $wa = $waData[$staff->id] ?? null;

            $staff->wa_total = $wa->wa_total ?? 0;
            $staff->wa_verified = $wa->wa_verified ?? 0;
            $staff->wa_pending = $wa->wa_pending ?? 0;
        }

        // =========================
        // CLIENT WISE BREAKUP
        // =========================

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

        // =========================
        // DASHBOARD TOTALS
        // =========================

        $totalOrders = CallingOrder::when($clientId, function ($q) use ($clientId) {
            $q->where('client_id', $clientId);
        })
            ->whereBetween('updated_at', [$from, $to])
            ->count();

        $totalWebVerified = CallingOrder::when($clientId, function ($q) use ($clientId) {
            $q->where('client_id', $clientId);
        })
            ->where('status', 'verified')
            ->whereNull('order_source')
            ->whereBetween('updated_at', [$from, $to])
            ->count();

        $totalWhatsappVerified = CallingOrder::when($clientId, function ($q) use ($clientId) {
            $q->where('client_id', $clientId);
        })
            ->where('status', 'verified')
            ->where('order_source', 'whatsapp')
            ->whereBetween('updated_at', [$from, $to])
            ->count();

        $totalPending = CallingOrder::when($clientId, function ($q) use ($clientId) {
            $q->where('client_id', $clientId);
        })
            ->where('status', 'pending')
            ->whereBetween('updated_at', [$from, $to])
            ->count();

        $totalWA = Conversation::when($clientId, function ($q) use ($clientId) {
            $q->where('client_id', $clientId);
        })
            ->whereBetween('created_at', [$from, $to])
            ->count();

        $verifiedWA = Conversation::when($clientId, function ($q) use ($clientId) {
            $q->where('client_id', $clientId);
        })
            ->where('status', 'verified')
            ->whereBetween('created_at', [$from, $to])
            ->count();

        // =========================
        // STAFF LIST
        // =========================

        $allStaff = $this->isClient()
            ? collect()
            : CallingUser::all();

        return view('performance', compact(
            'staffs',
            'from',
            'to',
            'clients',
            'clientId',
            'isClientUser',
            'clientWise',
            'allStaff',
            'totalOrders',
            'totalWebVerified',
            'totalWhatsappVerified',
            'totalPending',
            'totalWA',
            'verifiedWA'
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
