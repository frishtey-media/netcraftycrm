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
use Illuminate\Support\Facades\Log;

use App\Models\RtoReport;

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
    | STAFF
    |--------------------------------------------------------------------------
    */

        $request->validate([
            'staff_id' => 'required|exists:calling_users,id',
        ]);

        $staff = CallingUser::findOrFail(
            $request->staff_id
        );


        /*
    |--------------------------------------------------------------------------
    | DATE RANGE
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
    | STANDARD STATUSES
    |--------------------------------------------------------------------------
    | Keep this BEFORE calculating OTHER counts.
    */

        $standardStatuses = [
            'pending',
            'verified',
            'cancel',
            'not_reachable',
            'same_order'
        ];


        /*
    |--------------------------------------------------------------------------
    | BASE QUERY
    |--------------------------------------------------------------------------
    */

        $query = CallingOrder::query()
            ->where(
                'assigned_to',
                $request->staff_id
            )
            ->whereBetween(
                'updated_at',
                [$from, $to]
            );


        /*
    |--------------------------------------------------------------------------
    | ORDER SOURCE FILTER
    |--------------------------------------------------------------------------
    */

        if ($request->filled('order_source')) {

            if ($request->order_source === 'web') {

                /*
            | Web orders:
            | NULL, empty or web
            */

                $query->where(function ($q) {

                    $q->whereNull('order_source')
                        ->orWhere('order_source', '')
                        ->orWhereRaw(
                            'LOWER(TRIM(order_source)) = ?',
                            ['web']
                        );
                });
            } elseif ($request->order_source === 'whatsapp') {

                $query->whereRaw(
                    'LOWER(TRIM(order_source)) = ?',
                    ['whatsapp']
                );
            } elseif ($request->order_source === 'RTO') {

                $query->whereRaw(
                    'UPPER(TRIM(order_source)) = ?',
                    ['RTO']
                );
            } elseif ($request->order_source === 'deliveredreorder') {

                $query->whereRaw(
                    'LOWER(TRIM(order_source)) = ?',
                    ['deliveredreorder']
                );
            } elseif ($request->order_source === 'shopify_abandoned_checkout') {

                $query->whereRaw(
                    'LOWER(TRIM(order_source)) = ?',
                    ['shopify_abandoned_checkout']
                );
            }
        }
        /*
    |--------------------------------------------------------------------------
    | STATUS FILTER
    |--------------------------------------------------------------------------
    */

        if ($request->filled('status')) {

            if ($request->status === 'other') {

                $query->where(function ($q) use ($standardStatuses) {

                    $q->whereNotIn(
                        'status',
                        $standardStatuses
                    )
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
    | SEARCH
    |--------------------------------------------------------------------------
    */

        if ($request->filled('search')) {

            $search = trim(
                $request->search
            );

            $query->where(function ($q) use ($search) {

                $q->where(
                    'order_id',
                    'LIKE',
                    "%{$search}%"
                )
                    ->orWhere(
                        'customer_name',
                        'LIKE',
                        "%{$search}%"
                    )
                    ->orWhere(
                        'customer_phone',
                        'LIKE',
                        "%{$search}%"
                    )
                    ->orWhere(
                        'product_name',
                        'LIKE',
                        "%{$search}%"
                    )
                    ->orWhere(
                        'remarks',
                        'LIKE',
                        "%{$search}%"
                    );
            });
        }


        /*
    |--------------------------------------------------------------------------
    | TOTAL ORDERS
    |--------------------------------------------------------------------------
    */

        $totalOrders = (clone $query)
            ->count();


        /*
    |--------------------------------------------------------------------------
    | TOTAL BY SOURCE
    |--------------------------------------------------------------------------
    */

        // WEB
        $webOrders = (clone $query)
            ->where(function ($q) {

                $q->whereNull('order_source')
                    ->orWhere('order_source', '')
                    ->orWhereRaw(
                        'LOWER(TRIM(order_source)) = ?',
                        ['web']
                    );
            })
            ->count();


        // WHATSAPP
        $whatsappOrders = (clone $query)
            ->whereRaw(
                'LOWER(TRIM(order_source)) = ?',
                ['whatsapp']
            )
            ->count();


        // RTO
        $rtoOrders = (clone $query)
            ->whereRaw(
                'UPPER(TRIM(order_source)) = ?',
                ['RTO']
            )
            ->count();
        $deliveredReorderOrders = (clone $query)
            ->whereRaw(
                'LOWER(TRIM(order_source)) = ?',
                ['deliveredreorder']
            )
            ->count();
        $abandonedOrders = (clone $query)
            ->whereRaw(
                'LOWER(TRIM(order_source)) = ?',
                ['shopify_abandoned_checkout']
            )
            ->count();

        $pendingDeliveredReorder = (clone $query)
            ->where('status', 'pending')
            ->whereRaw(
                'LOWER(TRIM(order_source)) = ?',
                ['deliveredreorder']
            )
            ->count();

        $pendingAbandoned = (clone $query)
            ->where('status', 'pending')
            ->whereRaw(
                'LOWER(TRIM(order_source)) = ?',
                ['shopify_abandoned_checkout']
            )
            ->count();
        $verifiedDeliveredReorder = (clone $query)
            ->where('status', 'verified')
            ->whereRaw(
                'LOWER(TRIM(order_source)) = ?',
                ['deliveredreorder']
            )
            ->count();
        $verifiedAbandoned = (clone $query)
            ->where('status', 'verified')
            ->whereRaw(
                'LOWER(TRIM(order_source)) = ?',
                ['shopify_abandoned_checkout']
            )
            ->count();

        $cancelDeliveredReorder = (clone $query)
            ->where('status', 'cancel')
            ->whereRaw(
                'LOWER(TRIM(order_source)) = ?',
                ['deliveredreorder']
            )
            ->count();

        $cancelAbandoned = (clone $query)
            ->where('status', 'cancel')
            ->whereRaw(
                'LOWER(TRIM(order_source)) = ?',
                ['shopify_abandoned_checkout']
            )
            ->count();

        $notReachableDeliveredReorder = (clone $query)
            ->where('status', 'not_reachable')
            ->whereRaw(
                'LOWER(TRIM(order_source)) = ?',
                ['deliveredreorder']
            )
            ->count();

        $notReachableAbandoned = (clone $query)
            ->where('status', 'not_reachable')
            ->whereRaw(
                'LOWER(TRIM(order_source)) = ?',
                ['shopify_abandoned_checkout']
            )
            ->count();

        $sameOrderDeliveredReorder = (clone $query)
            ->where('status', 'same_order')
            ->whereRaw(
                'LOWER(TRIM(order_source)) = ?',
                ['deliveredreorder']
            )
            ->count();

        $sameOrderAbandoned = (clone $query)
            ->where('status', 'same_order')
            ->whereRaw(
                'LOWER(TRIM(order_source)) = ?',
                ['shopify_abandoned_checkout']
            )
            ->count();

        $otherDeliveredReorder = (clone $query)
            ->where(function ($q) use ($standardStatuses) {

                $q->whereNotIn(
                    'status',
                    $standardStatuses
                )
                    ->orWhereNull('status');
            })
            ->whereRaw(
                'LOWER(TRIM(order_source)) = ?',
                ['deliveredreorder']
            )
            ->count();

        $otherAbandoned = (clone $query)
            ->where(function ($q) use ($standardStatuses) {

                $q->whereNotIn(
                    'status',
                    $standardStatuses
                )
                    ->orWhereNull('status');
            })
            ->whereRaw(
                'LOWER(TRIM(order_source)) = ?',
                ['shopify_abandoned_checkout']
            )
            ->count();

        /*
    |--------------------------------------------------------------------------
    | PENDING
    |--------------------------------------------------------------------------
    */

        $pendingOrders = (clone $query)
            ->where(
                'status',
                'pending'
            )
            ->count();


        // Pending Web
        $pendingWeb = (clone $query)
            ->where(
                'status',
                'pending'
            )
            ->where(function ($q) {

                $q->whereNull('order_source')
                    ->orWhere('order_source', '')
                    ->orWhereRaw(
                        'LOWER(TRIM(order_source)) = ?',
                        ['web']
                    );
            })
            ->count();


        // Pending WhatsApp
        $pendingWhatsapp = (clone $query)
            ->where(
                'status',
                'pending'
            )
            ->whereRaw(
                'LOWER(TRIM(order_source)) = ?',
                ['whatsapp']
            )
            ->count();


        // Pending RTO
        $rtoPending = (clone $query)
            ->where(
                'status',
                'pending'
            )
            ->whereRaw(
                'UPPER(TRIM(order_source)) = ?',
                ['RTO']
            )
            ->count();


        /*
    |--------------------------------------------------------------------------
    | VERIFIED
    |--------------------------------------------------------------------------
    */

        $verifiedOrders = (clone $query)
            ->where(
                'status',
                'verified'
            )
            ->count();


        // Verified Web
        $verifiedWeb = (clone $query)
            ->where(
                'status',
                'verified'
            )
            ->where(function ($q) {

                $q->whereNull('order_source')
                    ->orWhere('order_source', '')
                    ->orWhereRaw(
                        'LOWER(TRIM(order_source)) = ?',
                        ['web']
                    );
            })
            ->count();


        // Verified WhatsApp
        $verifiedWhatsapp = (clone $query)
            ->where(
                'status',
                'verified'
            )
            ->whereRaw(
                'LOWER(TRIM(order_source)) = ?',
                ['whatsapp']
            )
            ->count();


        // Verified RTO
        $rtoVerified = (clone $query)
            ->where(
                'status',
                'verified'
            )
            ->whereRaw(
                'UPPER(TRIM(order_source)) = ?',
                ['RTO']
            )
            ->count();


        /*
    |--------------------------------------------------------------------------
    | CANCEL
    |--------------------------------------------------------------------------
    */

        $cancelOrders = (clone $query)
            ->where(
                'status',
                'cancel'
            )
            ->count();


        // Cancel Web
        $cancelWeb = (clone $query)
            ->where(
                'status',
                'cancel'
            )
            ->where(function ($q) {

                $q->whereNull('order_source')
                    ->orWhere('order_source', '')
                    ->orWhereRaw(
                        'LOWER(TRIM(order_source)) = ?',
                        ['web']
                    );
            })
            ->count();


        // Cancel WhatsApp
        $cancelWhatsapp = (clone $query)
            ->where(
                'status',
                'cancel'
            )
            ->whereRaw(
                'LOWER(TRIM(order_source)) = ?',
                ['whatsapp']
            )
            ->count();


        // Cancel RTO
        $rtoCancel = (clone $query)
            ->where(
                'status',
                'cancel'
            )
            ->whereRaw(
                'UPPER(TRIM(order_source)) = ?',
                ['RTO']
            )
            ->count();


        /*
    |--------------------------------------------------------------------------
    | NOT REACHABLE
    |--------------------------------------------------------------------------
    */

        $notReachableOrders = (clone $query)
            ->where(
                'status',
                'not_reachable'
            )
            ->count();


        // Not Reachable Web
        $notReachableWeb = (clone $query)
            ->where(
                'status',
                'not_reachable'
            )
            ->where(function ($q) {

                $q->whereNull('order_source')
                    ->orWhere('order_source', '')
                    ->orWhereRaw(
                        'LOWER(TRIM(order_source)) = ?',
                        ['web']
                    );
            })
            ->count();


        // Not Reachable WhatsApp
        $notReachableWhatsapp = (clone $query)
            ->where(
                'status',
                'not_reachable'
            )
            ->whereRaw(
                'LOWER(TRIM(order_source)) = ?',
                ['whatsapp']
            )
            ->count();


        // Not Reachable RTO
        $rtoNotReachable = (clone $query)
            ->where(
                'status',
                'not_reachable'
            )
            ->whereRaw(
                'UPPER(TRIM(order_source)) = ?',
                ['RTO']
            )
            ->count();


        /*
    |--------------------------------------------------------------------------
    | SAME ORDER
    |--------------------------------------------------------------------------
    */

        $sameOrderOrders = (clone $query)
            ->where(
                'status',
                'same_order'
            )
            ->count();


        // Same Order Web
        $sameOrderWeb = (clone $query)
            ->where(
                'status',
                'same_order'
            )
            ->where(function ($q) {

                $q->whereNull('order_source')
                    ->orWhere('order_source', '')
                    ->orWhereRaw(
                        'LOWER(TRIM(order_source)) = ?',
                        ['web']
                    );
            })
            ->count();


        // Same Order WhatsApp
        $sameOrderWhatsapp = (clone $query)
            ->where(
                'status',
                'same_order'
            )
            ->whereRaw(
                'LOWER(TRIM(order_source)) = ?',
                ['whatsapp']
            )
            ->count();


        // Same Order RTO
        $rtoSameOrder = (clone $query)
            ->where(
                'status',
                'same_order'
            )
            ->whereRaw(
                'UPPER(TRIM(order_source)) = ?',
                ['RTO']
            )
            ->count();


        /*
    |--------------------------------------------------------------------------
    | OTHER
    |--------------------------------------------------------------------------
    */

        $otherOrders = (clone $query)
            ->where(function ($q) use ($standardStatuses) {

                $q->whereNotIn(
                    'status',
                    $standardStatuses
                )
                    ->orWhereNull('status');
            })
            ->count();


        // Other Web
        $otherWeb = (clone $query)
            ->where(function ($q) use ($standardStatuses) {

                $q->whereNotIn(
                    'status',
                    $standardStatuses
                )
                    ->orWhereNull('status');
            })
            ->where(function ($q) {

                $q->whereNull('order_source')
                    ->orWhere('order_source', '')
                    ->orWhereRaw(
                        'LOWER(TRIM(order_source)) = ?',
                        ['web']
                    );
            })
            ->count();


        // Other WhatsApp
        $otherWhatsapp = (clone $query)
            ->where(function ($q) use ($standardStatuses) {

                $q->whereNotIn(
                    'status',
                    $standardStatuses
                )
                    ->orWhereNull('status');
            })
            ->whereRaw(
                'LOWER(TRIM(order_source)) = ?',
                ['whatsapp']
            )
            ->count();


        // Other RTO
        $rtoOther = (clone $query)
            ->where(function ($q) use ($standardStatuses) {

                $q->whereNotIn(
                    'status',
                    $standardStatuses
                )
                    ->orWhereNull('status');
            })
            ->whereRaw(
                'UPPER(TRIM(order_source)) = ?',
                ['RTO']
            )
            ->count();


        /*
    |--------------------------------------------------------------------------
    | PAYMENT
    |--------------------------------------------------------------------------
    */

        $codOrders = (clone $query)
            ->whereIn(
                'payment_mode',
                ['COD', 'cod']
            )
            ->count();


        $prepaidOrders = (clone $query)
            ->whereIn(
                'payment_mode',
                [
                    'Prepaid',
                    'prepaid',
                    'PREPAID'
                ]
            )
            ->count();


        /*
    |--------------------------------------------------------------------------
    | ORDERS LIST
    |--------------------------------------------------------------------------
    */

        $orders = (clone $query)
            ->with('client')
            ->orderByDesc('updated_at')
            ->paginate(100)
            ->withQueryString();


        /*
    |--------------------------------------------------------------------------
    | VIEW
    |--------------------------------------------------------------------------
    */

        return view(
            'performance-orders',
            compact(

                // Delivered Re-Order
                'deliveredReorderOrders',
                'pendingDeliveredReorder',
                'verifiedDeliveredReorder',
                'cancelDeliveredReorder',
                'notReachableDeliveredReorder',
                'sameOrderDeliveredReorder',
                'otherDeliveredReorder',

                // Abandoned Checkout
                'abandonedOrders',
                'pendingAbandoned',
                'verifiedAbandoned',
                'cancelAbandoned',
                'notReachableAbandoned',
                'sameOrderAbandoned',
                'otherAbandoned',

                // Orders
                'orders',
                'staff',
                'from',
                'to',

                // Total
                'totalOrders',

                // Sources
                'webOrders',
                'whatsappOrders',
                'rtoOrders',

                // Pending
                'pendingOrders',
                'pendingWeb',
                'pendingWhatsapp',
                'rtoPending',

                // Verified
                'verifiedOrders',
                'verifiedWeb',
                'verifiedWhatsapp',
                'rtoVerified',

                // Cancel
                'cancelOrders',
                'cancelWeb',
                'cancelWhatsapp',
                'rtoCancel',

                // Not Reachable
                'notReachableOrders',
                'notReachableWeb',
                'notReachableWhatsapp',
                'rtoNotReachable',

                // Same Order
                'sameOrderOrders',
                'sameOrderWeb',
                'sameOrderWhatsapp',
                'rtoSameOrder',

                // Other
                'otherOrders',
                'otherWeb',
                'otherWhatsapp',
                'rtoOther',

                // Payment
                'codOrders',
                'prepaidOrders'
            )
        );
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

    public function clientsorders()
    {
        if ($this->isClient()) {

            $clients = Client::where('id', $this->clientId())
                ->orderBy('client_name')
                ->get();
        } else {

            $clients = Client::orderBy('client_name')->get();
        }

        return view('clientsorders', compact('clients'));
    }

    public function ordersdashboard($client_id = null)
    {
        $from = Carbon::yesterday()->startOfDay();
        $to   = Carbon::now();



        $getRepeatPending = function ($clientId) {

            $repeatDays = ((int) $clientId === 2) ? 20 : 25;


            $orderPhone = "
            RIGHT(
                REPLACE(
                    REPLACE(
                        REPLACE(
                            REPLACE(
                                REPLACE(
                                    REPLACE(
                                        orders.customer_phone,
                                        ' ',
                                        ''
                                    ),
                                    '+',
                                    ''
                                ),
                                '-',
                                ''
                            ),
                            '(',
                            ''
                        ),
                        ')',
                        ''
                    ),
                    '.',
                    ''
                ),
                10
            )
        ";

            /*
        |--------------------------------------------------------------------------
        | Repeat Customers
        |--------------------------------------------------------------------------
        */

            return DB::table('orders')

                ->where(
                    'orders.client_id',
                    $clientId
                )

                /*
            |--------------------------------------------------------------------------
            | Delivered only
            |--------------------------------------------------------------------------
            */

                ->whereRaw(
                    'LOWER(TRIM(orders.delivery_status)) = ?',
                    ['delivered']
                )

                /*
            |--------------------------------------------------------------------------
            | Delivery date required
            |--------------------------------------------------------------------------
            */

                ->whereNotNull(
                    'orders.delivery_date'
                )

                /*
            |--------------------------------------------------------------------------
            | 20 / 25 Days
            |--------------------------------------------------------------------------
            */

                ->whereDate(
                    'orders.delivery_date',
                    '<=',
                    now()
                        ->subDays($repeatDays)
                        ->toDateString()
                )

                /*
            |--------------------------------------------------------------------------
            | Already assigned delivered reorder customers
            | should NOT appear again
            |--------------------------------------------------------------------------
            */

                ->whereNotExists(function ($q) use ($orderPhone) {

                    $callingPhone = "
                    RIGHT(
                        REPLACE(
                            REPLACE(
                                REPLACE(
                                    REPLACE(
                                        REPLACE(
                                            REPLACE(
                                                callingorder.customer_phone,
                                                ' ',
                                                ''
                                            ),
                                            '+',
                                            ''
                                        ),
                                        '-',
                                        ''
                                    ),
                                    '(',
                                    ''
                                ),
                                ')',
                                ''
                            ),
                            '.',
                            ''
                        ),
                        10
                    )
                ";

                    $q->select(
                        DB::raw(1)
                    )

                        ->from('callingorder')

                        /*
                    |--------------------------------------------------------------------------
                    | Same Client
                    |--------------------------------------------------------------------------
                    */

                        ->whereColumn(
                            'callingorder.client_id',
                            'orders.client_id'
                        )

                        /*
                    |--------------------------------------------------------------------------
                    | Only Repeat Customer Assignment
                    |--------------------------------------------------------------------------
                    */

                        ->where(
                            'callingorder.order_source',
                            'deliveredreorder'
                        )

                        /*
                    |--------------------------------------------------------------------------
                    | Same Customer Phone
                    |--------------------------------------------------------------------------
                    */

                        ->whereRaw(
                            "{$callingPhone} = {$orderPhone}"
                        );
                })

                /*
            |--------------------------------------------------------------------------
            | UNIQUE CUSTOMER COUNT
            |--------------------------------------------------------------------------
            |
            | If same customer has multiple delivered orders,
            | customer will count only once.
            |
            */

                ->selectRaw(
                    "COUNT(
                    DISTINCT {$orderPhone}
                ) as total"
                )

                ->value('total');
        };


        /*
    |--------------------------------------------------------------------------
    | CLIENT LOGIN
    |--------------------------------------------------------------------------
    */

        if ($this->isClient()) {

            $clientId = $this->clientId();

            $ordersData = Client::where(
                'id',
                $clientId
            )

                /*
            |--------------------------------------------------------------------------
            | Shopify Pending Orders
            |--------------------------------------------------------------------------
            */

                ->withCount([

                    'orders as total_orders' => function ($q) use ($from, $to) {

                        $q->whereNull('assigned_to')
                            ->where('status', 'pending')
                            ->whereBetween('order_date', [$from, $to]);
                    },

                    'callingOrders as total_abandoned_orders' => function ($q) {

                        $q->whereNull('assigned_to')
                            ->where('status', 'pending')
                            ->where(
                                'order_source',
                                'shopify_abandoned_checkout'
                            );
                    }

                ])

                ->get()

                ->map(function ($client) use ($getRepeatPending) {

                    /*
                |--------------------------------------------------------------------------
                | RTO Pending
                |--------------------------------------------------------------------------
                */

                    $rtoPending = DB::table('rto_reports')

                        ->join(
                            'orders',
                            'orders.order_id',
                            '=',
                            'rto_reports.order_id'
                        )

                        ->where(
                            'orders.client_id',
                            $client->id
                        )

                        ->where(
                            'rto_reports.is_exported',
                            0
                        )

                        ->count();


                    /*
                |--------------------------------------------------------------------------
                | Repeat Customer Pending
                |--------------------------------------------------------------------------
                */

                    $repeatPending = $getRepeatPending(
                        $client->id
                    );


                    return [

                        'client_name' =>
                        $client->client_name,

                        'client_id' =>
                        $client->id,

                        'total_orders' =>
                        (int) $client->total_orders,
                        'total_abandoned_orders' =>
                        (int) $client->total_abandoned_orders,
                        'rto_pending' =>
                        (int) $rtoPending,

                        'repeat_pending' =>
                        (int) $repeatPending,
                    ];
                });


            /*
        |--------------------------------------------------------------------------
        | WhatsApp Clients
        |--------------------------------------------------------------------------
        */

            $waClients = Conversation::select(
                'client_id',
                DB::raw('COUNT(*) as total')
            )

                ->where(
                    'client_id',
                    $clientId
                )

                ->whereBetween(
                    'updated_at',
                    [$from, $to]
                )

                ->groupBy(
                    'client_id'
                )

                ->with(
                    'client'
                )

                ->get();


            /*
        |--------------------------------------------------------------------------
        | Client Dashboard
        |--------------------------------------------------------------------------
        */

            return view(
                'ordersdashboard',
                [

                    'ordersData' =>
                    $ordersData,

                    'waClients' =>
                    $waClients,

                    'staff' =>
                    collect(),

                    'allStaff' =>
                    CallingUser::where(
                        'status',
                        1
                    )->get(),
                ]
            );
        }


        /*
    |--------------------------------------------------------------------------
    | SUPER ADMIN
    |--------------------------------------------------------------------------
    */

        $query = Client::query();


        /*
    |--------------------------------------------------------------------------
    | Specific Client Selected
    |--------------------------------------------------------------------------
    */

        if ($client_id) {

            $query->where(
                'id',
                $client_id
            );
        }


        /*
    |--------------------------------------------------------------------------
    | Orders Data
    |--------------------------------------------------------------------------
    */

        $ordersData = $query

            ->withCount([

                'orders as total_orders' => function ($q) use ($from, $to) {

                    $q->whereNull('assigned_to')
                        ->where('status', 'pending')
                        ->whereBetween('order_date', [$from, $to]);
                },

                'callingOrders as total_abandoned_orders' => function ($q) {

                    $q->whereNull('assigned_to')
                        ->where('status', 'pending')
                        ->where(
                            'order_source',
                            'shopify_abandoned_checkout'
                        );
                }

            ])

            ->get()

            ->map(function ($client) use ($getRepeatPending) {

                /*
            |--------------------------------------------------------------------------
            | RTO Pending
            |--------------------------------------------------------------------------
            */

                $rtoPending = DB::table('rto_reports')

                    ->join(
                        'orders',
                        'orders.order_id',
                        '=',
                        'rto_reports.order_id'
                    )

                    ->where(
                        'orders.client_id',
                        $client->id
                    )

                    ->where(
                        'rto_reports.is_exported',
                        0
                    )

                    ->count();


                /*
            |--------------------------------------------------------------------------
            | Repeat Customer Pending
            |--------------------------------------------------------------------------
            */

                $repeatPending = $getRepeatPending(
                    $client->id
                );


                return [

                    'client_name' =>
                    $client->client_name,

                    'client_id' =>
                    $client->id,

                    'total_orders' =>
                    (int) $client->total_orders,
                    'total_abandoned_orders' =>
                    (int) $client->total_abandoned_orders,

                    'rto_pending' =>
                    (int) $rtoPending,

                    'repeat_pending' =>
                    (int) $repeatPending,
                ];
            });


        /*
    |--------------------------------------------------------------------------
    | WhatsApp Clients
    |--------------------------------------------------------------------------
    */

        $waClients = Conversation::select(
            'client_id',
            DB::raw('COUNT(*) as total')
        )

            ->when(
                $client_id,
                function ($q) use ($client_id) {

                    $q->where(
                        'client_id',
                        $client_id
                    );
                }
            )

            ->whereBetween(
                'updated_at',
                [$from, $to]
            )

            ->groupBy(
                'client_id'
            )

            ->with(
                'client'
            )

            ->get();


        /*
    |--------------------------------------------------------------------------
    | Staff Conversation Summary
    |--------------------------------------------------------------------------
    */

        $staff = Conversation::select(
            'assigned_to',
            DB::raw('COUNT(*) as total')
        )

            ->whereBetween(
                'updated_at',
                [$from, $to]
            )

            ->groupBy(
                'assigned_to'
            )

            ->with(
                'staff'
            )

            ->get();


        /*
    |--------------------------------------------------------------------------
    | SUPER ADMIN DASHBOARD
    |--------------------------------------------------------------------------
    */

        return view(
            'ordersdashboard',
            [

                'ordersData' =>
                $ordersData,

                'waClients' =>
                $waClients,

                'staff' =>
                $staff,

                'allStaff' =>
                CallingUser::all(),
            ]
        );
    }

    public function assignRtoOrders(Request $request)
    {
        DB::beginTransaction();

        try {

            // Get all pending RTO orders
            $rtoOrders = DB::table('rto_reports')
                ->join('orders', 'orders.order_id', '=', 'rto_reports.order_id')
                ->where('orders.client_id', $request->client_id)
                ->where('rto_reports.is_exported', 0)
                ->orderBy('rto_reports.id')
                ->select(
                    'rto_reports.*',

                    'orders.client_id',
                    'orders.city',
                    'orders.state',
                    'orders.pincode',
                    'orders.father_name'
                )
                ->get();

            foreach ($request->assign as $staffId => $qty) {

                if (empty($qty) || $qty <= 0) {
                    continue;
                }

                // Get selected orders
                $selectedOrders = $rtoOrders->splice(0, $qty);

                // Staff Details
                $staff = CallingUser::findOrFail($staffId);

                $name = strtoupper(trim($staff->name));

                // First + Last Letter
                $prefix = substr($name, 0, 1) . substr($name, -1, 1);

                $date = now()->format('d-m-y');

                // Last Serial Number of Today
                $lastSerial = CallingOrder::where('assigned_to', $staffId)
                    ->whereDate('created_at', today())
                    ->count();

                foreach ($selectedOrders as $order) {

                    // Increment Serial
                    $lastSerial++;

                    // Generate Order ID
                    $callingOrderId = $prefix . '-' . $date . '-' . $lastSerial;

                    CallingOrder::create([

                        'client_id'          => $order->client_id,

                        // Auto Generated Calling Order ID
                        'order_id'           => $callingOrderId,


                        'order_date'         => $order->order_date,



                        'product_name'       => $order->product,

                        'quantity'           => $order->quantity,

                        'weight'             => $order->weight,

                        'customer_name'      => $order->customer_name,

                        'father_name'        => $order->father_name,

                        'city'               => $order->city,

                        'state'              => $order->state,

                        'pincode'            => $order->pincode,

                        'customer_phone'     => $order->customer_phone,

                        'shipping_address'   => $order->shipping_address,

                        'payment_mode'       => $order->payment_mode,

                        'amount'             => $order->amount,

                        'assigned_to'        => $staffId,

                        'status'             => 'pending',

                        'order_source'       => 'RTO',



                    ]);

                    // Update RTO Report
                    $updated = DB::table('rto_reports')
                        ->where('order_id', $order->order_id)
                        ->update([
                            'is_exported'  => 1,
                            'assign_staff' => $staffId,
                            'assigndate'   => now(),
                        ]);

                    if (!$updated) {
                        throw new \Exception(
                            'Unable to update RTO Report. Order ID: ' . $order->order_id
                        );
                    }
                }
            }

            DB::commit();

            return back()->with('success', 'RTO Orders Assigned Successfully.');
        } catch (\Exception $e) {

            DB::rollBack();

            Log::error('RTO Assignment Error', [
                'message' => $e->getMessage(),
                'line'    => $e->getLine(),
                'file'    => $e->getFile(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return back()->withErrors([
                'error' => $e->getMessage() . ' | Line : ' . $e->getLine()
            ]);
        }
    }

    public function assignabandonedOrders(Request $request)
    {
        $request->validate([
            'client_id' => 'required|exists:clients,id',
            'assign'    => 'required|array',
        ]);

        $clientId = $request->client_id;

        if ($this->isClient() && $clientId != $this->clientId()) {
            abort(403, 'Unauthorized Access');
        }

        $assignData = $request->assign;



        $orders = CallingOrder::where('client_id', $clientId)

            ->whereNull('assigned_to')

            ->where('status', 'pending')

            ->where(
                'order_source',
                'shopify_abandoned_checkout'
            )

            ->inRandomOrder()

            ->get();


        $totalRequested = array_sum($assignData);



        if ($totalRequested > $orders->count()) {

            return back()->with(
                'error',
                'Assigned quantity exceeds available abandoned checkouts'
            );
        }


        $orderIndex = 0;

        foreach ($assignData as $staffId => $qty) {

            $qty = (int) $qty;

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
            'Abandoned Checkouts Assigned Successfully'
        );
    }
    public function assigndeliveredOrders(Request $request)
    {
        $request->validate([
            'client_id' => 'required|integer|exists:clients,id',
            'assign'    => 'required|array',
        ]);

        DB::beginTransaction();

        try {

            $clientId = (int) $request->client_id;
            $repeatDays = ($clientId == 2) ? 20 : 25;
            $repeatOrders = DB::table('orders')
                ->where('orders.client_id', $clientId)

                ->whereRaw(
                    'LOWER(TRIM(orders.delivery_status)) = ?',
                    ['delivered']
                )

                ->whereNotNull('orders.delivery_date')

                ->whereDate(
                    'orders.delivery_date',
                    '<=',
                    now()->subDays($repeatDays)->toDateString()
                )

                ->whereNotExists(function ($q) {

                    $q->select(DB::raw(1))
                        ->from('callingorder')
                        ->whereColumn(
                            'callingorder.client_id',
                            'orders.client_id'
                        )
                        ->whereColumn(
                            'callingorder.customer_phone',
                            'orders.customer_phone'
                        )
                        ->where(
                            'callingorder.order_source',
                            'deliveredreorder'
                        );
                })

                ->select([
                    'orders.id',
                    'orders.order_id',
                    'orders.client_id',
                    'orders.date',
                    'orders.delivery_date',
                    'orders.product',
                    'orders.quantity',
                    'orders.weight',
                    'orders.customer_name',
                    'orders.father_name',
                    'orders.age',
                    'orders.city',
                    'orders.state',
                    'orders.pincode',
                    'orders.customer_phone',
                    'orders.shipping_address',
                    'orders.payment_mode',
                    'orders.amount',
                ])

                ->orderBy('orders.delivery_date', 'asc')

                ->get();

            $totalRequested = collect($request->assign)
                ->sum(function ($qty) {
                    return (int) $qty;
                });


            $availableCount = $repeatOrders->count();


            if ($totalRequested > $availableCount) {

                throw new \Exception(
                    "Only {$availableCount} repeat customers are available. " .
                        "You requested {$totalRequested}."
                );
            }

            foreach ($request->assign as $staffId => $qty) {

                $qty = (int) $qty;

                if ($qty <= 0) {
                    continue;
                }

                $staff = CallingUser::find($staffId);

                if (!$staff) {

                    throw new \Exception(
                        "Staff not found. Staff ID: {$staffId}"
                    );
                }

                $selectedOrders = $repeatOrders->splice(0, $qty);

                $name = strtoupper(trim($staff->name));

                $prefix =
                    substr($name, 0, 1) .
                    substr($name, -1, 1);


                $date = now()->format('d-m-y');


                $lastOrder = CallingOrder::where('assigned_to', $staffId)
                    ->whereDate('created_at', today())
                    ->count();

                foreach ($selectedOrders as $order) {

                    $lastOrder++;

                    $callingOrderId =
                        $prefix . '-' . $date . '-' . $lastOrder;


                    while (
                        CallingOrder::where(
                            'order_id',
                            $callingOrderId
                        )->exists()
                    ) {

                        $lastOrder++;

                        $callingOrderId =
                            $prefix . '-' . $date . '-' . $lastOrder;
                    }

                    $alreadyAssigned = CallingOrder::where(
                        'client_id',
                        $order->client_id
                    )
                        ->where(
                            'customer_phone',
                            $order->customer_phone
                        )
                        ->where(
                            'order_source',
                            'deliveredreorder'
                        )
                        ->exists();


                    if ($alreadyAssigned) {
                        continue;
                    }

                    CallingOrder::create([

                        'client_id' =>
                        $order->client_id,

                        // New Calling Order ID
                        'order_id' =>
                        $callingOrderId,

                        // orders.date -> callingorder.order_date
                        'order_date' =>
                        $order->date,

                        'product_name' =>
                        $order->product,

                        'quantity' =>
                        $order->quantity,

                        'weight' =>
                        $order->weight,

                        'customer_name' =>
                        $order->customer_name,

                        'father_name' =>
                        $order->father_name,

                        'age' =>
                        $order->age,

                        'city' =>
                        $order->city,

                        'state' =>
                        $order->state,

                        'pincode' =>
                        $order->pincode,

                        'customer_phone' =>
                        $order->customer_phone,

                        'shipping_address' =>
                        $order->shipping_address,

                        'payment_mode' =>
                        $order->payment_mode,

                        'amount' =>
                        $order->amount,

                        'assigned_to' =>
                        $staffId,

                        'status' =>
                        'pending',

                        'order_source' =>
                        'deliveredreorder',
                    ]);
                }
            }


            DB::commit();


            return redirect()
                ->back()
                ->with(
                    'success',
                    'Repeat Customers assigned successfully.'
                );
        } catch (\Throwable $e) {

            DB::rollBack();

            return redirect()
                ->back()
                ->with(
                    'error',
                    'Repeat Customer Assignment Failed: ' .
                        $e->getMessage()
                );
        }
    }

    public function performance(Request $request)
    {

        $from = $request->filled('from')
            ? Carbon::parse($request->from)->startOfDay()
            : now()->startOfDay();

        $to = $request->filled('to')
            ? Carbon::parse($request->to)->endOfDay()
            : now()->endOfDay();

        $isClientUser = $this->isClient();

        if ($isClientUser) {
            $clientId = $this->clientId();
        } else {
            $clientId = $request->filled('client_id')
                ? (int) $request->client_id
                : null;
        }

        if ($isClientUser) {

            $clients = Client::where(
                'id',
                $clientId
            )
                ->orderBy('client_name')
                ->get();
        } else {

            $clients = Client::orderBy(
                'client_name'
            )->get();
        }

        $baseQuery = function () use (
            $clientId,
            $from,
            $to
        ) {

            return CallingOrder::query()

                ->when(
                    $clientId,
                    function ($q) use ($clientId) {
                        $q->where(
                            'client_id',
                            $clientId
                        );
                    }
                )

                ->whereBetween(
                    'updated_at',
                    [$from, $to]
                );
        };

        $staffs = CallingUser::orderBy(
            'name'
        )->get();

        $staffStats = CallingOrder::query()

            ->when(
                $clientId,
                function ($q) use ($clientId) {
                    $q->where(
                        'client_id',
                        $clientId
                    );
                }
            )

            ->whereBetween(
                'updated_at',
                [$from, $to]
            )

            ->select(
                'assigned_to',

                DB::raw("
                COUNT(*) AS total_orders
            "),

                DB::raw("
                SUM(
                    CASE
                        WHEN status = 'verified'
                        AND order_source IS NULL
                        THEN 1
                        ELSE 0
                    END
                ) AS web_verified_orders
            "),

                DB::raw("
                SUM(
                    CASE
                        WHEN status = 'verified'
                        AND order_source = 'whatsapp'
                        THEN 1
                        ELSE 0
                    END
                ) AS whatsapp_verified_orders
            "),

                DB::raw("
                SUM(
                    CASE
                        WHEN status = 'verified'
                        AND order_source = 'RTO'
                        THEN 1
                        ELSE 0
                    END
                ) AS rto_verified_orders
            "),

                DB::raw("
                SUM(
                    CASE
                        WHEN status = 'verified'
                        AND order_source = 'deliveredreorder'
                        THEN 1
                        ELSE 0
                    END
                ) AS delivered_reorder_orders
            "),
                DB::raw("
    SUM(
        CASE
            WHEN order_source = 'shopify_abandoned_checkout'
            THEN 1
            ELSE 0
        END
    ) AS abandoned_orders
"),

                DB::raw("
    SUM(
        CASE
            WHEN status = 'verified'
            AND order_source = 'shopify_abandoned_checkout'
            THEN 1
            ELSE 0
        END
    ) AS abandoned_verified
"),

                DB::raw("
    SUM(
        CASE
            WHEN status = 'pending'
            AND order_source = 'shopify_abandoned_checkout'
            THEN 1
            ELSE 0
        END
    ) AS abandoned_pending
"),

                DB::raw("
    SUM(
        CASE
            WHEN status = 'cancel'
            AND order_source = 'shopify_abandoned_checkout'
            THEN 1
            ELSE 0
        END
    ) AS abandoned_cancel
"),

                DB::raw("
    SUM(
        CASE
            WHEN status = 'not_reachable'
            AND order_source = 'shopify_abandoned_checkout'
            THEN 1
            ELSE 0
        END
    ) AS abandoned_not_reachable
"),

                DB::raw("
    SUM(
        CASE
            WHEN status = 'same_order'
            AND order_source = 'shopify_abandoned_checkout'
            THEN 1
            ELSE 0
        END
    ) AS abandoned_same_order
"),

                DB::raw("
    SUM(
        CASE
            WHEN order_source = 'shopify_abandoned_checkout'
            AND (
                status NOT IN (
                    'pending',
                    'verified',
                    'cancel',
                    'not_reachable',
                    'same_order'
                )
                OR status IS NULL
            )
            THEN 1
            ELSE 0
        END
    ) AS abandoned_other
"),
                DB::raw("
                SUM(
                    CASE
                        WHEN status = 'pending'
                        THEN 1
                        ELSE 0
                    END
                ) AS pending_orders
            "),

                DB::raw("
                SUM(
                    CASE
                        WHEN status = 'not_reachable'
                        THEN 1
                        ELSE 0
                    END
                ) AS not_reachable_orders
            "),

                DB::raw("
                SUM(
                    CASE
                        WHEN status = 'cancel'
                        THEN 1
                        ELSE 0
                    END
                ) AS cancel_orders
            "),

                DB::raw("
                SUM(
                    CASE
                        WHEN status = 'same_order'
                        THEN 1
                        ELSE 0
                    END
                ) AS same_order
            "),

                DB::raw("
                SUM(
                    CASE
                        WHEN status NOT IN (
                            'pending',
                            'verified',
                            'cancel',
                            'not_reachable',
                            'same_order'
                        )
                        OR status IS NULL
                        THEN 1
                        ELSE 0
                    END
                ) AS other
            "),


                DB::raw("
                SUM(
                    CASE
                        WHEN order_source = 'RTO'
                        AND status = 'pending'
                        THEN 1
                        ELSE 0
                    END
                ) AS rto_orders
            "),


                DB::raw("
                SUM(
                    CASE
                        WHEN order_source = 'RTO'
                        AND status = 'verified'
                        THEN 1
                        ELSE 0
                    END
                ) AS rto_verified
            "),


                DB::raw("
                SUM(
                    CASE
                        WHEN order_source = 'deliveredreorder'
                        THEN 1
                        ELSE 0
                    END
                ) AS delivered_reorder_total

            ")
            )

            ->groupBy(
                'assigned_to'
            )

            ->get()

            ->keyBy(
                'assigned_to'
            );

        $waData = Conversation::query()

            ->when(
                $clientId,
                function ($q) use ($clientId) {
                    $q->where(
                        'client_id',
                        $clientId
                    );
                }
            )

            ->whereBetween(
                'created_at',
                [$from, $to]
            )

            ->select(
                'assigned_to',

                DB::raw("
                COUNT(*) AS wa_total
            "),

                DB::raw("
                SUM(
                    CASE
                        WHEN status = 'verified'
                        THEN 1
                        ELSE 0
                    END
                ) AS wa_verified
            "),

                DB::raw("
                SUM(
                    CASE
                        WHEN status = 'open'
                        THEN 1
                        ELSE 0
                    END
                ) AS wa_pending
            ")
            )

            ->groupBy(
                'assigned_to'
            )

            ->get()

            ->keyBy(
                'assigned_to'
            );

        foreach ($staffs as $staff) {

            $stats = $staffStats->get(
                $staff->id
            );

            $wa = $waData->get(
                $staff->id
            );

            $staff->total_orders =
                (int) ($stats->total_orders ?? 0);

            $staff->web_verified_orders =
                (int) ($stats->web_verified_orders ?? 0);

            $staff->whatsapp_verified_orders =
                (int) ($stats->whatsapp_verified_orders ?? 0);
            $staff->abandoned_orders =
                (int) ($stats->abandoned_orders ?? 0);

            $staff->abandoned_verified =
                (int) ($stats->abandoned_verified ?? 0);

            $staff->abandoned_pending =
                (int) ($stats->abandoned_pending ?? 0);

            $staff->abandoned_cancel =
                (int) ($stats->abandoned_cancel ?? 0);

            $staff->abandoned_not_reachable =
                (int) ($stats->abandoned_not_reachable ?? 0);

            $staff->abandoned_same_order =
                (int) ($stats->abandoned_same_order ?? 0);

            $staff->abandoned_other =
                (int) ($stats->abandoned_other ?? 0);
            $staff->rto_verified_orders =
                (int) ($stats->rto_verified_orders ?? 0);

            $staff->delivered_reorder_orders =
                (int) ($stats->delivered_reorder_orders ?? 0);

            $staff->pending_orders =
                (int) ($stats->pending_orders ?? 0);

            $staff->not_reachable_orders =
                (int) ($stats->not_reachable_orders ?? 0);

            $staff->cancel_orders =
                (int) ($stats->cancel_orders ?? 0);

            $staff->same_order =
                (int) ($stats->same_order ?? 0);

            $staff->other =
                (int) ($stats->other ?? 0);

            $staff->rto_orders =
                (int) ($stats->rto_orders ?? 0);

            $staff->rto_verified =
                (int) ($stats->rto_verified ?? 0);

            $staff->delivered_reorder_total =
                (int) ($stats->delivered_reorder_total ?? 0);

            $staff->wa_total =
                (int) ($wa->wa_total ?? 0);

            $staff->wa_verified =
                (int) ($wa->wa_verified ?? 0);

            $staff->wa_pending =
                (int) ($wa->wa_pending ?? 0);

            $staff->delivered_reorder =
                $staff->delivered_reorder_orders;
        }


        $clientData = CallingOrder::with(
            'client'
        )

            ->when(
                $clientId,
                function ($q) use ($clientId) {
                    $q->where(
                        'client_id',
                        $clientId
                    );
                }
            )

            ->whereBetween(
                'updated_at',
                [$from, $to]
            )

            ->selectRaw("
            assigned_to,
            client_id,
            COUNT(*) AS total
        ")

            ->groupBy(
                'assigned_to',
                'client_id'
            )

            ->get();


        $clientWise = [];


        foreach ($clientData as $row) {

            $clientWise[$row->assigned_to][] = [

                'client' =>
                $row->client->client_name
                    ?? 'N/A',

                'total' =>
                (int) $row->total,
            ];
        }


        $totalWeb =
            $baseQuery()
            ->whereNull(
                'order_source'
            )
            ->count();


        $totalWhatsapp =
            $baseQuery()
            ->where(
                'order_source',
                'whatsapp'
            )
            ->count();


        $totalRtoAll =
            $baseQuery()
            ->where(
                'order_source',
                'RTO'
            )
            ->count();


        $totalDeliveredReorder =
            $baseQuery()
            ->where(
                'order_source',
                'deliveredreorder'
            )
            ->count();

        $totalPending =
            $baseQuery()
            ->where(
                'status',
                'pending'
            )
            ->count();


        $pendingWeb =
            $baseQuery()
            ->where(
                'status',
                'pending'
            )
            ->whereNull(
                'order_source'
            )
            ->count();


        $pendingWhatsapp =
            $baseQuery()
            ->where(
                'status',
                'pending'
            )
            ->where(
                'order_source',
                'whatsapp'
            )
            ->count();


        $pendingRto =
            $baseQuery()
            ->where(
                'status',
                'pending'
            )
            ->where(
                'order_source',
                'RTO'
            )
            ->count();


        $pendingDeliveredReorder =
            $baseQuery()
            ->where(
                'status',
                'pending'
            )
            ->where(
                'order_source',
                'deliveredreorder'
            )
            ->count();

        $totalVerified =
            $baseQuery()
            ->where(
                'status',
                'verified'
            )
            ->count();


        $verifiedWeb =
            $baseQuery()
            ->where(
                'status',
                'verified'
            )
            ->whereNull(
                'order_source'
            )
            ->count();


        $verifiedWhatsapp =
            $baseQuery()
            ->where(
                'status',
                'verified'
            )
            ->where(
                'order_source',
                'whatsapp'
            )
            ->count();


        $verifiedRto =
            $baseQuery()
            ->where(
                'status',
                'verified'
            )
            ->where(
                'order_source',
                'RTO'
            )
            ->count();


        $verifiedDeliveredReorder =
            $baseQuery()
            ->where(
                'status',
                'verified'
            )
            ->where(
                'order_source',
                'deliveredreorder'
            )
            ->count();


        $totalCancel =
            $baseQuery()
            ->where(
                'status',
                'cancel'
            )
            ->count();


        $cancelWeb =
            $baseQuery()
            ->where(
                'status',
                'cancel'
            )
            ->whereNull(
                'order_source'
            )
            ->count();


        $cancelWhatsapp =
            $baseQuery()
            ->where(
                'status',
                'cancel'
            )
            ->where(
                'order_source',
                'whatsapp'
            )
            ->count();


        $cancelRto =
            $baseQuery()
            ->where(
                'status',
                'cancel'
            )
            ->where(
                'order_source',
                'RTO'
            )
            ->count();


        $cancelDeliveredReorder =
            $baseQuery()
            ->where(
                'status',
                'cancel'
            )
            ->where(
                'order_source',
                'deliveredreorder'
            )
            ->count();


        $totalNotReachable =
            $baseQuery()
            ->where(
                'status',
                'not_reachable'
            )
            ->count();


        $notReachableWeb =
            $baseQuery()
            ->where(
                'status',
                'not_reachable'
            )
            ->whereNull(
                'order_source'
            )
            ->count();


        $notReachableWhatsapp =
            $baseQuery()
            ->where(
                'status',
                'not_reachable'
            )
            ->where(
                'order_source',
                'whatsapp'
            )
            ->count();


        $notReachableRto =
            $baseQuery()
            ->where(
                'status',
                'not_reachable'
            )
            ->where(
                'order_source',
                'RTO'
            )
            ->count();


        $notReachableDeliveredReorder =
            $baseQuery()
            ->where(
                'status',
                'not_reachable'
            )
            ->where(
                'order_source',
                'deliveredreorder'
            )
            ->count();

        $totalSameOrder =
            $baseQuery()
            ->where(
                'status',
                'same_order'
            )
            ->count();


        $sameOrderWeb =
            $baseQuery()
            ->where(
                'status',
                'same_order'
            )
            ->whereNull(
                'order_source'
            )
            ->count();


        $sameOrderWhatsapp =
            $baseQuery()
            ->where(
                'status',
                'same_order'
            )
            ->where(
                'order_source',
                'whatsapp'
            )
            ->count();


        $sameOrderRto =
            $baseQuery()
            ->where(
                'status',
                'same_order'
            )
            ->where(
                'order_source',
                'RTO'
            )
            ->count();


        $sameOrderDeliveredReorder =
            $baseQuery()
            ->where(
                'status',
                'same_order'
            )
            ->where(
                'order_source',
                'deliveredreorder'
            )
            ->count();

        $knownStatuses = [
            'pending',
            'verified',
            'cancel',
            'not_reachable',
            'same_order',
        ];


        $totalOther =
            $baseQuery()
            ->where(function ($q) use ($knownStatuses) {

                $q->whereNotIn(
                    'status',
                    $knownStatuses
                )
                    ->orWhereNull(
                        'status'
                    );
            })
            ->count();


        $otherWeb =
            $baseQuery()
            ->whereNull(
                'order_source'
            )
            ->where(function ($q) use ($knownStatuses) {

                $q->whereNotIn(
                    'status',
                    $knownStatuses
                )
                    ->orWhereNull(
                        'status'
                    );
            })
            ->count();


        $otherWhatsapp =
            $baseQuery()
            ->where(
                'order_source',
                'whatsapp'
            )
            ->where(function ($q) use ($knownStatuses) {

                $q->whereNotIn(
                    'status',
                    $knownStatuses
                )
                    ->orWhereNull(
                        'status'
                    );
            })
            ->count();


        $otherRto =
            $baseQuery()
            ->where(
                'order_source',
                'RTO'
            )
            ->where(function ($q) use ($knownStatuses) {

                $q->whereNotIn(
                    'status',
                    $knownStatuses
                )
                    ->orWhereNull(
                        'status'
                    );
            })
            ->count();


        $otherDeliveredReorder =
            $baseQuery()
            ->where(
                'order_source',
                'deliveredreorder'
            )
            ->where(function ($q) use ($knownStatuses) {

                $q->whereNotIn(
                    'status',
                    $knownStatuses
                )
                    ->orWhereNull(
                        'status'
                    );
            })
            ->count();



        $totalRto =
            $baseQuery()
            ->where(
                'order_source',
                'RTO'
            )
            ->where(
                'status',
                'pending'
            )
            ->count();
        $totalOrders =
            $baseQuery()->count();
        $totalAbandoned =
            $baseQuery()
            ->where(
                'order_source',
                'shopify_abandoned_checkout'
            )
            ->count();
        $pendingAbandoned =
            $baseQuery()
            ->where('status', 'pending')
            ->where(
                'order_source',
                'shopify_abandoned_checkout'
            )
            ->count();
        $verifiedAbandoned =
            $baseQuery()
            ->where('status', 'verified')
            ->where(
                'order_source',
                'shopify_abandoned_checkout'
            )
            ->count();
        $cancelAbandoned =
            $baseQuery()
            ->where('status', 'cancel')
            ->where(
                'order_source',
                'shopify_abandoned_checkout'
            )
            ->count();
        $notReachableAbandoned =
            $baseQuery()
            ->where('status', 'not_reachable')
            ->where(
                'order_source',
                'shopify_abandoned_checkout'
            )
            ->count();
        $sameOrderAbandoned =
            $baseQuery()
            ->where('status', 'same_order')
            ->where(
                'order_source',
                'shopify_abandoned_checkout'
            )
            ->count();
        $otherAbandoned =
            $baseQuery()
            ->where(
                'order_source',
                'shopify_abandoned_checkout'
            )
            ->where(function ($q) use ($knownStatuses) {

                $q->whereNotIn(
                    'status',
                    $knownStatuses
                )
                    ->orWhereNull('status');
            })
            ->count();

        $totalRtoVerified =
            $baseQuery()
            ->where(
                'order_source',
                'RTO'
            )
            ->where(
                'status',
                'verified'
            )
            ->count();

        $totalWA =
            Conversation::query()

            ->when(
                $clientId,
                function ($q) use ($clientId) {
                    $q->where(
                        'client_id',
                        $clientId
                    );
                }
            )

            ->whereBetween(
                'created_at',
                [$from, $to]
            )

            ->count();


        $verifiedWA =
            Conversation::query()

            ->when(
                $clientId,
                function ($q) use ($clientId) {
                    $q->where(
                        'client_id',
                        $clientId
                    );
                }
            )

            ->where(
                'status',
                'verified'
            )

            ->whereBetween(
                'created_at',
                [$from, $to]
            )

            ->count();


        $allStaff = $isClientUser
            ? collect()
            : CallingUser::where(
                'status',
                1
            )->get();


        return view(
            'performance',
            compact(

                'staffs',

                'from',
                'to',

                'clients',

                'clientId',

                'isClientUser',

                'clientWise',

                'allStaff',


                'totalOrders',

                'totalWeb',

                'totalWhatsapp',

                'totalRtoAll',

                'totalDeliveredReorder',

                'totalPending',

                'pendingWeb',
                'totalAbandoned',
                'pendingAbandoned',
                'verifiedAbandoned',
                'cancelAbandoned',
                'notReachableAbandoned',
                'sameOrderAbandoned',
                'otherAbandoned',
                'pendingWhatsapp',

                'pendingRto',

                'pendingDeliveredReorder',

                'totalVerified',

                'verifiedWeb',

                'verifiedWhatsapp',

                'verifiedRto',

                'verifiedDeliveredReorder',

                'totalCancel',

                'cancelWeb',

                'cancelWhatsapp',

                'cancelRto',

                'cancelDeliveredReorder',

                'totalNotReachable',

                'notReachableWeb',

                'notReachableWhatsapp',

                'notReachableRto',

                'notReachableDeliveredReorder',

                'totalSameOrder',

                'sameOrderWeb',

                'sameOrderWhatsapp',

                'sameOrderRto',

                'sameOrderDeliveredReorder',


                'totalOther',

                'otherWeb',

                'otherWhatsapp',

                'otherRto',

                'otherDeliveredReorder',

                'totalRto',

                'totalRtoVerified',

                'totalWA',

                'verifiedWA'
            )
        );
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
