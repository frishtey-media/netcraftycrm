<?php

namespace App\Http\Controllers;

use App\Exports\FinalLabelExport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\OrdersImport;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use App\Models\ShopifyOrder;
use App\Models\LabelSender;
use App\Models\Client;
use App\Exports\PostOfficeExport;
use App\Models\CallingUser;
use App\Exports\SelectedOrdersExport;
use Carbon\Carbon;
use App\Exports\StaffPerformanceExport;

use App\Exports\DeliveredOrdersExport;



class OrderController extends Controller
{
    private function isClient()
    {
        return auth()->check() && auth()->user()->role === 'client';
    }

    private function clientId()
    {
        return auth()->user()->client_id;
    }
    public function staffDeliveryDetail(Request $request)
    {
        $query = Order::query()

            ->leftJoin(
                'callingorder',
                'callingorder.order_id',
                '=',
                'orders.order_id'
            )

            ->leftJoin(
                'calling_users',
                'calling_users.id',
                '=',
                'callingorder.assigned_to'
            )

            ->leftJoin(
                'clients',
                'clients.id',
                '=',
                'orders.client_id'
            )

            ->where('orders.delivery_status', 'Delivered');

        // Staff
        if ($request->filled('staff_id')) {

            if ($request->staff_id == 'other') {

                $query->whereNull('callingorder.assigned_to');
            } else {

                $query->where(
                    'callingorder.assigned_to',
                    $request->staff_id
                );
            }
        }

        // Client
        if ($request->filled('client_id')) {

            $query->where(
                'orders.client_id',
                $request->client_id
            );
        }

        // Date
        if ($request->filled('from')) {

            $query->whereDate(
                'orders.delivery_date',
                '>=',
                $request->from
            );
        }

        if ($request->filled('to')) {

            $query->whereDate(
                'orders.delivery_date',
                '<=',
                $request->to
            );
        }

        // Web / WhatsApp
        if ($request->filled('order_source')) {

            if ($request->order_source == 'web') {

                $query->whereNull('callingorder.order_source');
            } else {

                $query->where(
                    'callingorder.order_source',
                    'whatsapp'
                );
            }
        }

        /*
    |--------------------------------------------------------------------------
    | Product Summary
    |--------------------------------------------------------------------------
    */

        $productSummary = (clone $query)

            ->selectRaw("
            orders.product,
            COUNT(*) as orders,
            SUM(orders.quantity) as qty,
            SUM(orders.amount) as amount
        ")

            ->groupBy('orders.product')

            ->orderByDesc('amount')

            ->get();

        /*
    |--------------------------------------------------------------------------
    | Order Details
    |--------------------------------------------------------------------------
    */

        $orders = (clone $query)

            ->select(
                'orders.order_id',
                'orders.barcode',
                'orders.customer_name',
                'orders.customer_phone',
                'orders.city',
                'orders.state',
                'orders.product',
                'orders.quantity',
                'orders.amount',
                'orders.delivery_date',
                'callingorder.order_source'
            )

            ->latest('orders.delivery_date')

            ->paginate(100);

        /*
    |--------------------------------------------------------------------------
    | Cards
    |--------------------------------------------------------------------------
    */

        $totalOrders = $productSummary->sum('orders');

        $totalQty = $productSummary->sum('qty');

        $totalAmount = $productSummary->sum('amount');

        return view(
            'reports.staff_delivery_detail',
            compact(
                'orders',
                'productSummary',
                'totalOrders',
                'totalQty',
                'totalAmount'
            )
        );
    }
    public function getProducts($clientId)
    {
        $products = Order::where('client_id', $clientId)
            ->whereNotNull('product')
            ->where('product', '!=', '')
            ->distinct()
            ->orderBy('product')
            ->pluck('product');

        return response()->json($products);
    }
    public function manualDelivery(Request $request)
    {
        $request->validate([

            'order_id' => 'required',

            'delivery_status' => 'required',

            'delivery_date' => 'required'

        ]);

        $order = Order::findOrFail(
            $request->order_id
        );

        $order->delivery_status = $request->delivery_status;

        $order->delivery_date = $request->delivery_date;

        $order->delivery_remark = $request->remark;

        $order->manual_delivery = 1;

        $order->manual_delivery_by = auth()->id();

        $order->manual_delivery_date = now();

        $order->save();

        return redirect()
            ->back()
            ->with(
                'success',
                'Status Updated Successfully.'
            );
    }

    public function deliverindex(Request $request)
    {
        $query = Order::query()

            ->leftJoin(
                'clients',
                'clients.id',
                '=',
                'orders.client_id'
            )

            ->leftJoin(
                'callingorder',
                'callingorder.order_id',
                '=',
                'orders.order_id'
            )

            ->where(
                'orders.delivery_status',
                'Delivered'
            );

        /*
    |--------------------------------------------------------------------------
    | Client
    |--------------------------------------------------------------------------
    */

        if ($this->isClient()) {

            $query->where(
                'orders.client_id',
                $this->clientId()
            );

            $clients = Client::where(
                'id',
                $this->clientId()
            )->get();
        } else {

            $clients = Client::orderBy('client_name')->get();

            if ($request->filled('client_id')) {

                $query->where(
                    'orders.client_id',
                    $request->client_id
                );
            }
        }

        /*
    |--------------------------------------------------------------------------
    | Delivery Date
    |--------------------------------------------------------------------------
    */

        if ($request->filled('from')) {

            $query->whereDate(
                'orders.delivery_date',
                '>=',
                $request->from
            );
        }

        if ($request->filled('to')) {

            $query->whereDate(
                'orders.delivery_date',
                '<=',
                $request->to
            );
        }

        /*
    |--------------------------------------------------------------------------
    | Product
    |--------------------------------------------------------------------------
    */

        if ($request->filled('product')) {

            $query->where(
                'orders.product',
                $request->product
            );
        }


        if ($request->filled('payment_mode')) {

            $query->where(
                'orders.payment_mode',
                $request->payment_mode
            );
        }
        /*
    |--------------------------------------------------------------------------
    | Order Source
    |--------------------------------------------------------------------------
    */

        if ($request->filled('order_source')) {

            if ($request->order_source == 'web') {

                $query->whereNull(
                    'callingorder.order_source'
                );
            } else {

                $query->where(
                    'callingorder.order_source',
                    'whatsapp'
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

                $q->where(
                    'orders.order_id',
                    'like',
                    "%{$search}%"
                )

                    ->orWhere(
                        'orders.barcode',
                        'like',
                        "%{$search}%"
                    )

                    ->orWhere(
                        'orders.customer_name',
                        'like',
                        "%{$search}%"
                    )

                    ->orWhere(
                        'orders.customer_phone',
                        'like',
                        "%{$search}%"
                    );
            });
        }

        $staffSummary = DB::table('orders')

            ->leftJoin(
                'callingorder',
                'orders.order_id',
                '=',
                'callingorder.order_id'
            )

            ->leftJoin(
                'calling_users',
                'callingorder.assigned_to',
                '=',
                'calling_users.id'
            )

            ->leftJoin(
                'clients',
                'orders.client_id',
                '=',
                'clients.id'
            )

            ->where(
                'orders.delivery_status',
                'Delivered'
            )

            // Client
            ->when($request->filled('client_id'), function ($q) use ($request) {
                $q->where('orders.client_id', $request->client_id);
            })

            // Staff
            ->when($request->filled('staff_id'), function ($q) use ($request) {

                if ($request->staff_id == 'other') {

                    $q->whereNull('callingorder.assigned_to');
                } else {

                    $q->where(
                        'callingorder.assigned_to',
                        $request->staff_id
                    );
                }
            })

            // Delivery Date
            ->when($request->filled('from'), function ($q) use ($request) {

                $q->whereDate(
                    'orders.delivery_date',
                    '>=',
                    $request->from
                );
            })

            ->when($request->filled('to'), function ($q) use ($request) {

                $q->whereDate(
                    'orders.delivery_date',
                    '<=',
                    $request->to
                );
            })
            ->when($request->filled('payment_mode'), function ($q) use ($request) {

                $q->where(
                    'orders.payment_mode',
                    $request->payment_mode
                );
            })
            ->selectRaw("
    callingorder.assigned_to as staff_id,

    COALESCE(calling_users.name,'Other') as staff_name,

    COALESCE(clients.client_name,'No Client') as client_name,

    COUNT(DISTINCT orders.id) as total_delivered,

    COUNT(DISTINCT CASE
        WHEN callingorder.order_source IS NULL
        THEN orders.id
    END) as web_delivered,

    COUNT(DISTINCT CASE
        WHEN callingorder.order_source='whatsapp'
        THEN orders.id
    END) as whatsapp_delivered,

    SUM(orders.amount) as total_amount
")

            ->groupBy(
                'callingorder.assigned_to',
                'calling_users.name',
                'clients.client_name'
            )

            ->orderByDesc('total_delivered')

            ->get();
        /*
    |--------------------------------------------------------------------------
    | Dashboard
    |--------------------------------------------------------------------------
    */

        $totalOrders = (clone $query)->count();

        $totalAmount = (clone $query)->sum(
            'orders.amount'
        );

        $products = Order::select('product')

            ->distinct()

            ->orderBy('product')

            ->pluck('product');

        $orders = (clone $query)

            ->leftJoin(
                'calling_users',
                'callingorder.assigned_to',
                '=',
                'calling_users.id'
            )

            ->select(

                'orders.*',

                'clients.client_name',

                'callingorder.order_source',

                'calling_users.name as staff_name'

            )

            ->latest('orders.delivery_date')

            ->paginate(
                $request->records ?? 100
            );


        $grandDelivered = $staffSummary->sum('total_delivered');

        $grandWeb = $staffSummary->sum('web_delivered');

        $grandWhatsapp = $staffSummary->sum('whatsapp_delivered');

        $grandAmount = $staffSummary->sum('total_amount');
        $staffs = CallingUser::orderBy('name')->get();
        return view(
            'reports.delivered',
            compact(
                'orders',
                'clients',
                'products',
                'staffs',

                'staffSummary',

                'grandDelivered',
                'grandWeb',
                'grandWhatsapp',
                'grandAmount',

                'totalOrders',
                'totalAmount'
            )
        );
    }
    public function deliverExport(Request $request)
    {
        return Excel::download(
            new DeliveredOrdersExport($request),
            'Delivered_Orders_' . now()->format('YmdHis') . '.xlsx'
        );
    }



    public function index(Request $request)
    {
        $sortOrder = $request->get('sort_order', 'desc');

        /*
    |--------------------------------------------------------------------------
    | BASE QUERY
    |--------------------------------------------------------------------------
    */

        $query = Order::with([
            'callingOrder.staff',
            'delhiveryShipment'
        ]);


        /*
    |--------------------------------------------------------------------------
    | CLIENT
    |--------------------------------------------------------------------------
    */

        if ($this->isClient()) {

            $query->where(
                'client_id',
                $this->clientId()
            );

            $clients = Client::where(
                'id',
                $this->clientId()
            )->get();

            $senders = LabelSender::where(
                'client_id',
                $this->clientId()
            )
                ->orderBy('customer_name')
                ->get();
        } else {

            $clients = Client::orderBy('client_name')->get();

            $senders = LabelSender::orderBy('customer_name')->get();

            if ($request->filled('client_id')) {

                $query->where(
                    'client_id',
                    $request->client_id
                );
            }
        }


        /*
    |--------------------------------------------------------------------------
    | PRODUCT FILTER
    |--------------------------------------------------------------------------
    */

        if ($request->filled('product')) {

            $query->where(
                'product',
                $request->product
            );
        }


        /*
    |--------------------------------------------------------------------------
    | STAFF FILTER
    |--------------------------------------------------------------------------
    */

        if ($request->filled('staff_id')) {

            $query->whereHas(
                'callingOrder',
                function ($q) use ($request) {

                    $q->where(
                        'assigned_to',
                        $request->staff_id
                    );
                }
            );
        }


        /*
    |--------------------------------------------------------------------------
    | DELIVERY STATUS FILTER
    |--------------------------------------------------------------------------
    */

        if ($request->filled('delivery_status')) {

            if ($request->delivery_status === 'null') {

                $query->where(function ($q) {

                    $q->whereNull('delivery_status')
                        ->orWhere('delivery_status', '');
                });
            } else {

                $query->where(
                    'delivery_status',
                    $request->delivery_status
                );
            }
        }


        /*
    |--------------------------------------------------------------------------
    | PAYMENT MODE
    |--------------------------------------------------------------------------
    */

        if ($request->filled('payment_mode')) {

            $query->where(
                'payment_mode',
                $request->payment_mode
            );
        }


        /*
    |--------------------------------------------------------------------------
    | DATE FILTER
    |--------------------------------------------------------------------------
    */

        if ($request->filled('date_from')) {

            $query->whereDate(
                'created_at',
                '>=',
                $request->date_from
            );
        }

        if ($request->filled('date_to')) {

            $query->whereDate(
                'created_at',
                '<=',
                $request->date_to
            );
        }


        /*
    |--------------------------------------------------------------------------
    | SEARCH
    |--------------------------------------------------------------------------
    */

        $searchTerms = [];
        $notFound = [];

        if ($request->filled('search')) {

            $searchTerms = preg_split(
                '/[\r\n,]+/',
                trim($request->search)
            );

            $searchTerms = array_values(
                array_filter(
                    array_map('trim', $searchTerms)
                )
            );

            if (!empty($searchTerms)) {

                $query->where(function ($q) use ($searchTerms) {

                    foreach ($searchTerms as $term) {

                        $q->orWhere(
                            'order_id',
                            'LIKE',
                            "%{$term}%"
                        )
                            ->orWhere(
                                'barcode',
                                'LIKE',
                                "%{$term}%"
                            )
                            ->orWhere(
                                'customer_phone',
                                'LIKE',
                                "%{$term}%"
                            )
                            ->orWhere(
                                'customer_name',
                                'LIKE',
                                "%{$term}%"
                            );
                    }
                });


                /*
            |--------------------------------------------------------------------------
            | NOT FOUND
            |--------------------------------------------------------------------------
            */

                $foundValues = [];

                foreach ($searchTerms as $term) {

                    $exists = Order::where(
                        'order_id',
                        'LIKE',
                        "%{$term}%"
                    )
                        ->orWhere(
                            'barcode',
                            'LIKE',
                            "%{$term}%"
                        )
                        ->orWhere(
                            'customer_phone',
                            'LIKE',
                            "%{$term}%"
                        )
                        ->orWhere(
                            'customer_name',
                            'LIKE',
                            "%{$term}%"
                        )
                        ->exists();

                    if ($exists) {

                        $foundValues[] = strtoupper(
                            trim($term)
                        );
                    }
                }

                $notFound = collect($searchTerms)
                    ->map(
                        fn($value) =>
                        strtoupper(trim($value))
                    )
                    ->reject(
                        fn($value) =>
                        in_array($value, $foundValues)
                    )
                    ->values()
                    ->toArray();
            }
        }


        /*
    |--------------------------------------------------------------------------
    | TOTAL ORDERS
    |--------------------------------------------------------------------------
    */

        $totalOrders = (clone $query)->count();


        /*
    |--------------------------------------------------------------------------
    | WEB / WHATSAPP HELPER
    |--------------------------------------------------------------------------
    */

        $webCondition = function ($main) {

            $main->whereDoesntHave('callingOrder')
                ->orWhereHas(
                    'callingOrder',
                    function ($q) {

                        $q->whereNull('order_source')
                            ->orWhere('order_source', '');
                    }
                );
        };


        /*
    |--------------------------------------------------------------------------
    | WEB / WHATSAPP ORDERS
    |--------------------------------------------------------------------------
    */

        $webOrders = (clone $query)
            ->where($webCondition)
            ->count();

        $whatsappOrders =
            $totalOrders - $webOrders;


        /*
    |--------------------------------------------------------------------------
    | DELIVERED
    |--------------------------------------------------------------------------
    */

        $totalDelivered = (clone $query)
            ->where(
                'delivery_status',
                'Delivered'
            )
            ->count();

        $webDelivered = (clone $query)
            ->where(
                'delivery_status',
                'Delivered'
            )
            ->where($webCondition)
            ->count();

        $whatsappDelivered =
            $totalDelivered - $webDelivered;


        /*
    |--------------------------------------------------------------------------
    | RTO INTRANSIT
    |--------------------------------------------------------------------------
    */

        $totalRto = (clone $query)
            ->where(
                'delivery_status',
                'RTO-intrasit'
            )
            ->count();

        $webRto = (clone $query)
            ->where(
                'delivery_status',
                'RTO-intrasit'
            )
            ->where($webCondition)
            ->count();

        $whatsappRto =
            $totalRto - $webRto;


        /*
    |--------------------------------------------------------------------------
    | RTO RECEIVED
    |--------------------------------------------------------------------------
    */

        $totalRtoReceived = (clone $query)
            ->where(
                'delivery_status',
                'RTO Received'
            )
            ->count();

        $webRtoReceived = (clone $query)
            ->where(
                'delivery_status',
                'RTO Received'
            )
            ->where($webCondition)
            ->count();

        $whatsappRtoReceived =
            $totalRtoReceived - $webRtoReceived;


        /*
    |--------------------------------------------------------------------------
    | CUSTOMER INTRANSIT
    |--------------------------------------------------------------------------
    */

        $totalTransit = (clone $query)
            ->where(
                'delivery_status',
                'Customer - Intrasit'
            )
            ->count();
        $ofd = (clone $query)
            ->where(
                'delivery_status',
                'Out for Delivery'
            )
            ->count();
        $hold = (clone $query)
            ->where(
                'delivery_status',
                'On Hold'
            )
            ->count();

        $webTransit = (clone $query)
            ->where(
                'delivery_status',
                'Customer - Intrasit'
            )
            ->where($webCondition)
            ->count();

        $whatsappTransit =
            $totalTransit - $webTransit;


        /*
    |--------------------------------------------------------------------------
    | NO STATUS
    |--------------------------------------------------------------------------
    */

        $totalNoStatus = (clone $query)
            ->where(function ($q) {

                $q->whereNull('delivery_status')
                    ->orWhere('delivery_status', '');
            })
            ->count();

        $webNoStatus = (clone $query)
            ->where(function ($q) {

                $q->whereNull('delivery_status')
                    ->orWhere('delivery_status', '');
            })
            ->where($webCondition)
            ->count();

        $whatsappNoStatus =
            $totalNoStatus - $webNoStatus;


        /*
    |--------------------------------------------------------------------------
    | PAYMENT RECEIVED
    |--------------------------------------------------------------------------
    */

        $paymentReceivedOrders = (clone $query)
            ->where(
                'delivery_status',
                'Delivered'
            )
            ->whereNotNull('pay_bill_date')
            ->where(
                'pay_bill_date',
                '!=',
                ''
            )
            ->count();

        $paymentReceivedAmount = (clone $query)
            ->where(
                'delivery_status',
                'Delivered'
            )
            ->whereNotNull('pay_bill_date')
            ->where(
                'pay_bill_date',
                '!=',
                ''
            )
            ->sum('receivedcodamt');


        /*
    |--------------------------------------------------------------------------
    | PAYMENT PENDING
    |--------------------------------------------------------------------------
    */

        $paymentPendingOrders = (clone $query)
            ->where(
                'delivery_status',
                'Delivered'
            )
            ->where(function ($q) {

                $q->whereNull('pay_bill_date')
                    ->orWhere('pay_bill_date', '');
            })
            ->count();

        $paymentPendingAmount = (clone $query)
            ->where(
                'delivery_status',
                'Delivered'
            )
            ->where(function ($q) {

                $q->whereNull('pay_bill_date')
                    ->orWhere('pay_bill_date', '');
            })
            ->sum('amount');


        /*
    |--------------------------------------------------------------------------
    | OUR SIDE PENDING
    |--------------------------------------------------------------------------
    */

        $ourSidePending = $totalNoStatus;


        /*
    |--------------------------------------------------------------------------
    | INDIA POST PENDING
    |--------------------------------------------------------------------------
    */

        $indiaPostPending = (clone $query)
            ->whereNotNull('delivery_status')
            ->where(
                'delivery_status',
                '!=',
                ''
            )
            ->where(
                'delivery_status',
                '!=',
                'Delivered'
            )
            ->count();


        /*
    |--------------------------------------------------------------------------
    | CUSTOMER INTRANSIT > 7 DAYS
    |--------------------------------------------------------------------------
    */

        $transit7Days = (clone $query)
            ->where(
                'delivery_status',
                'Customer - Intrasit'
            )
            ->whereDate(
                'created_at',
                '<=',
                Carbon::now()->subDays(7)
            )
            ->count();


        /*
    |--------------------------------------------------------------------------
    | PERCENTAGES
    |--------------------------------------------------------------------------
    */

        $totalDeliveredPercent = $totalOrders > 0
            ? round(
                ($totalDelivered / $totalOrders) * 100,
                2
            )
            : 0;

        $totalRtoPercent = $totalOrders > 0
            ? round(
                ($totalRto / $totalOrders) * 100,
                2
            )
            : 0;

        $totalRtoReceivedPercent = $totalOrders > 0
            ? round(
                ($totalRtoReceived / $totalOrders) * 100,
                2
            )
            : 0;

        $totalTransitPercent = $totalOrders > 0
            ? round(
                ($totalTransit / $totalOrders) * 100,
                2
            )
            : 0;

        $totalNoStatusPercent = $totalOrders > 0
            ? round(
                ($totalNoStatus / $totalOrders) * 100,
                2
            )
            : 0;


        $paymentReceivedPercent = $totalOrders > 0
            ? round(
                ($paymentReceivedOrders / $totalOrders) * 100,
                2
            )
            : 0;

        $paymentPendingPercent = $totalOrders > 0
            ? round(
                ($paymentPendingOrders / $totalOrders) * 100,
                2
            )
            : 0;


        /*
    |--------------------------------------------------------------------------
    | WEB PERCENTAGES
    |--------------------------------------------------------------------------
    */

        $webDeliveredPercent = $webOrders > 0
            ? round(
                ($webDelivered / $webOrders) * 100,
                2
            )
            : 0;

        $webRtoPercent = $webOrders > 0
            ? round(
                ($webRto / $webOrders) * 100,
                2
            )
            : 0;

        $webRtoReceivedPercent = $webOrders > 0
            ? round(
                ($webRtoReceived / $webOrders) * 100,
                2
            )
            : 0;

        $webTransitPercent = $webOrders > 0
            ? round(
                ($webTransit / $webOrders) * 100,
                2
            )
            : 0;

        $webNoStatusPercent = $webOrders > 0
            ? round(
                ($webNoStatus / $webOrders) * 100,
                2
            )
            : 0;


        /*
    |--------------------------------------------------------------------------
    | WHATSAPP PERCENTAGES
    |--------------------------------------------------------------------------
    */

        $waDeliveredPercent = $whatsappOrders > 0
            ? round(
                ($whatsappDelivered / $whatsappOrders) * 100,
                2
            )
            : 0;

        $waRtoPercent = $whatsappOrders > 0
            ? round(
                ($whatsappRto / $whatsappOrders) * 100,
                2
            )
            : 0;

        $waRtoReceivedPercent = $whatsappOrders > 0
            ? round(
                ($whatsappRtoReceived / $whatsappOrders) * 100,
                2
            )
            : 0;

        $waTransitPercent = $whatsappOrders > 0
            ? round(
                ($whatsappTransit / $whatsappOrders) * 100,
                2
            )
            : 0;

        $waNoStatusPercent = $whatsappOrders > 0
            ? round(
                ($whatsappNoStatus / $whatsappOrders) * 100,
                2
            )
            : 0;


        /*
    |--------------------------------------------------------------------------
    | DASHBOARD CHARTS
    |--------------------------------------------------------------------------
    */

        $dailyOrders = (clone $query)
            ->select(
                DB::raw('DATE(created_at) as order_date'),
                DB::raw('COUNT(*) as total')
            )
            ->groupBy('order_date')
            ->orderBy('order_date')
            ->get();

        $labels = [];
        $totalOrdersChart = [];

        foreach ($dailyOrders as $row) {

            $labels[] = Carbon::parse(
                $row->order_date
            )->format('d M');

            $totalOrdersChart[] = $row->total;
        }


        /*
    |--------------------------------------------------------------------------
    | WEB CHART
    |--------------------------------------------------------------------------
    */

        $webTrend = (clone $query)
            ->where($webCondition)
            ->select(
                DB::raw('DATE(created_at) as order_date'),
                DB::raw('COUNT(*) as total')
            )
            ->groupBy('order_date')
            ->orderBy('order_date')
            ->pluck(
                'total',
                'order_date'
            );

        $webOrdersChart = [];

        foreach ($dailyOrders as $day) {

            $webOrdersChart[] =
                $webTrend[$day->order_date] ?? 0;
        }


        /*
    |--------------------------------------------------------------------------
    | WHATSAPP CHART
    |--------------------------------------------------------------------------
    */

        $waTrend = (clone $query)
            ->whereHas(
                'callingOrder',
                function ($q) {

                    $q->whereNotNull('order_source')
                        ->where(
                            'order_source',
                            '!=',
                            ''
                        );
                }
            )
            ->select(
                DB::raw('DATE(created_at) as order_date'),
                DB::raw('COUNT(*) as total')
            )
            ->groupBy('order_date')
            ->orderBy('order_date')
            ->pluck(
                'total',
                'order_date'
            );

        $waOrdersChart = [];

        foreach ($dailyOrders as $day) {

            $waOrdersChart[] =
                $waTrend[$day->order_date] ?? 0;
        }


        /*
    |--------------------------------------------------------------------------
    | DELIVERY CHART
    |--------------------------------------------------------------------------
    */

        $deliveryTrend = (clone $query)
            ->where(
                'delivery_status',
                'Delivered'
            )
            ->select(
                DB::raw('DATE(created_at) as order_date'),
                DB::raw('COUNT(*) as total')
            )
            ->groupBy('order_date')
            ->orderBy('order_date')
            ->pluck(
                'total',
                'order_date'
            );

        $deliveryChart = [];

        foreach ($dailyOrders as $day) {

            $deliveryChart[] =
                $deliveryTrend[$day->order_date] ?? 0;
        }


        /*
    |--------------------------------------------------------------------------
    | RTO CHART
    |--------------------------------------------------------------------------
    */

        $rtoTrend = (clone $query)
            ->where(
                'delivery_status',
                'RTO-intrasit'
            )
            ->select(
                DB::raw('DATE(created_at) as order_date'),
                DB::raw('COUNT(*) as total')
            )
            ->groupBy('order_date')
            ->orderBy('order_date')
            ->pluck(
                'total',
                'order_date'
            );

        $rtoChart = [];

        foreach ($dailyOrders as $day) {

            $rtoChart[] =
                $rtoTrend[$day->order_date] ?? 0;
        }


        /*
    |--------------------------------------------------------------------------
    | PAYMENT CHART
    |--------------------------------------------------------------------------
    */

        $paymentTrend = (clone $query)
            ->where(
                'delivery_status',
                'Delivered'
            )
            ->select(
                DB::raw('DATE(created_at) as order_date'),
                DB::raw('SUM(receivedcodamt) as amount')
            )
            ->groupBy('order_date')
            ->orderBy('order_date')
            ->pluck(
                'amount',
                'order_date'
            );

        $paymentChart = [];

        foreach ($dailyOrders as $day) {

            $paymentChart[] =
                $paymentTrend[$day->order_date] ?? 0;
        }


        /*
    |--------------------------------------------------------------------------
    | SOURCE CHART
    |--------------------------------------------------------------------------
    */

        $sourceChart = [
            $webOrders,
            $whatsappOrders
        ];


        /*
    |--------------------------------------------------------------------------
    | STATUS CHART
    |--------------------------------------------------------------------------
    */

        $statusChart = [
            $totalDelivered,
            $totalRto,
            $totalRtoReceived,
            $totalTransit,
            $totalNoStatus
        ];


        /*
    |--------------------------------------------------------------------------
    | STAFF PERFORMANCE
    |--------------------------------------------------------------------------
    */

        $staffPerformance = DB::table('callingorder')
            ->leftJoin(
                'calling_users',
                'calling_users.id',
                '=',
                'callingorder.assigned_to'
            )
            ->leftJoin(
                'orders',
                'orders.order_id',
                '=',
                'callingorder.order_id'
            )
            ->select(
                'calling_users.id',
                'calling_users.name',
                DB::raw(
                    'COUNT(orders.id) as total_orders'
                ),
                DB::raw(
                    "SUM(
                    CASE
                        WHEN orders.delivery_status='Delivered'
                        THEN 1
                        ELSE 0
                    END
                ) as delivered"
                ),
                DB::raw(
                    "SUM(
                    CASE
                        WHEN orders.delivery_status='RTO-intrasit'
                        THEN 1
                        ELSE 0
                    END
                ) as rto"
                )
            )
            ->groupBy(
                'calling_users.id',
                'calling_users.name'
            )
            ->orderByDesc('total_orders')
            ->get();


        foreach ($staffPerformance as $staff) {

            $staff->success =
                $staff->total_orders > 0
                ? round(
                    ($staff->delivered * 100)
                        / $staff->total_orders,
                    2
                )
                : 0;
        }


        /*
    |--------------------------------------------------------------------------
    | TOP CLIENTS
    |--------------------------------------------------------------------------
    */

        $topClients = DB::table('orders')
            ->leftJoin(
                'clients',
                'clients.id',
                '=',
                'orders.client_id'
            )
            ->leftJoin(
                'callingorder',
                'callingorder.order_id',
                '=',
                'orders.order_id'
            )
            ->select(
                'clients.client_name',

                DB::raw(
                    'COUNT(orders.id) as total_orders'
                ),

                DB::raw("
                SUM(
                    CASE
                        WHEN callingorder.order_source IS NULL
                        OR callingorder.order_source=''
                        THEN 1
                        ELSE 0
                    END
                ) as web_orders
            "),

                DB::raw("
                SUM(
                    CASE
                        WHEN callingorder.order_source IS NOT NULL
                        AND callingorder.order_source<>''
                        THEN 1
                        ELSE 0
                    END
                ) as whatsapp_orders
            ")
            )
            ->groupBy('clients.client_name')
            ->orderByDesc('total_orders')
            ->limit(10)
            ->get();


        /*
    |--------------------------------------------------------------------------
    | QUICK INSIGHTS
    |--------------------------------------------------------------------------
    */

        $deliveryRate = $totalOrders > 0
            ? round(
                ($totalDelivered / $totalOrders) * 100,
                2
            )
            : 0;

        $rtoRate = $totalOrders > 0
            ? round(
                ($totalRto / $totalOrders) * 100,
                2
            )
            : 0;

        $transitRate = $totalOrders > 0
            ? round(
                ($totalTransit / $totalOrders) * 100,
                2
            )
            : 0;

        $pendingRate = $totalOrders > 0
            ? round(
                ($totalNoStatus / $totalOrders) * 100,
                2
            )
            : 0;


        $bestDay = (clone $query)
            ->select(
                DB::raw('DATE(created_at) as day'),
                DB::raw('COUNT(*) as total')
            )
            ->groupBy('day')
            ->orderByDesc('total')
            ->first();


        $averageOrders = count($labels) > 0
            ? round(
                $totalOrders / count($labels),
                2
            )
            : 0;


        $highestPayment = (clone $query)
            ->whereNotNull('pay_bill_date')
            ->select(
                DB::raw(
                    "STR_TO_DATE(
                    pay_bill_date,
                    '%d-%m-%Y'
                ) as pay_date"
                ),
                DB::raw(
                    "SUM(receivedcodamt) as amount"
                )
            )
            ->groupBy('pay_date')
            ->orderByDesc('amount')
            ->first();


        $bestStaff = $staffPerformance
            ->sortByDesc('delivered')
            ->first();


        /*
    |--------------------------------------------------------------------------
    | STAFF LIST
    |--------------------------------------------------------------------------
    */

        $staffs = CallingUser::orderBy('name')->get();


        /*
    |--------------------------------------------------------------------------
    | PAGINATION
    |--------------------------------------------------------------------------
    */

        $perPage = (int) $request->get(
            'per_page',
            100
        );

        $allowedPerPage = [
            10,
            25,
            50,
            100,
            500,
            1000,
            5000,
            10000,
            15000
        ];

        if (!in_array(
            $perPage,
            $allowedPerPage
        )) {
            $perPage = 100;
        }


        $orders = $query
            ->orderBy(
                'created_at',
                $sortOrder
            )
            ->paginate($perPage)
            ->withQueryString();


        /*
    |--------------------------------------------------------------------------
    | RETURN VIEW
    |--------------------------------------------------------------------------
    */

        return view(
            'orders.index',
            compact(

                'totalOrders',

                'webOrders',
                'whatsappOrders',

                'totalDelivered',
                'webDelivered',
                'whatsappDelivered',

                'totalRto',
                'webRto',
                'whatsappRto',

                'totalRtoReceived',
                'webRtoReceived',
                'whatsappRtoReceived',

                'totalTransit',
                'webTransit',
                'whatsappTransit',

                'totalNoStatus',
                'webNoStatus',
                'whatsappNoStatus',

                'paymentReceivedOrders',
                'paymentReceivedAmount',

                'paymentPendingOrders',
                'paymentPendingAmount',

                'paymentReceivedPercent',
                'paymentPendingPercent',

                'totalDeliveredPercent',
                'totalRtoPercent',
                'totalRtoReceivedPercent',
                'totalTransitPercent',
                'totalNoStatusPercent',
                'ofd',
                'hold',

                'webDeliveredPercent',
                'webRtoPercent',
                'webRtoReceivedPercent',
                'webTransitPercent',
                'webNoStatusPercent',

                'waDeliveredPercent',
                'waRtoPercent',
                'waRtoReceivedPercent',
                'waTransitPercent',
                'waNoStatusPercent',

                'ourSidePending',
                'indiaPostPending',
                'transit7Days',

                'orders',
                'clients',
                'senders',
                'staffs',

                'searchTerms',
                'notFound',

                'labels',
                'totalOrdersChart',
                'webOrdersChart',
                'waOrdersChart',
                'deliveryChart',
                'rtoChart',
                'paymentChart',
                'sourceChart',
                'statusChart',

                'topClients',

                'deliveryRate',
                'rtoRate',
                'transitRate',
                'pendingRate',

                'bestDay',
                'averageOrders',
                'highestPayment',
                'bestStaff',
                'staffPerformance'
            )
        );
    }

    public function exportStaffSummary(Request $request)
    {
        $staffSummary = $this->getStaffSummary($request);

        foreach ($staffSummary as $row) {

            $row->web_percentage =
                $row->web_orders > 0
                ? round(($row->web_delivered * 100) / $row->web_orders, 2)
                : 0;

            $row->whatsapp_percentage =
                $row->whatsapp_orders > 0
                ? round(($row->whatsapp_delivered * 100) / $row->whatsapp_orders, 2)
                : 0;
        }

        return Excel::download(
            new StaffPerformanceExport($staffSummary),
            'Staff_Performance_' . now()->format('YmdHis') . '.xlsx'
        );
    }
    public function exportSelected(Request $request)
    {
        $ids = explode(',', $request->ids);

        return Excel::download(
            new SelectedOrdersExport($ids),
            'Selected_Orders_' . now()->format('d-m-Y_H-i-s') . '.xlsx'
        );
    }
    public function downloadBarcodes(Request $request)
    {
        $request->validate([
            'from_date' => 'required|date',
            'to_date'   => 'required|date',
        ]);

        $query = DB::table('orders')
            ->whereDate('date', '>=', $request->from_date)
            ->whereDate('date', '<=', $request->to_date);

        if ($this->isClient()) {
            $query->where(
                'client_id',
                $this->clientId()
            );
        }

        $barcodes = $query
            ->whereNotNull('barcode')
            ->pluck('barcode')
            ->toArray();

        if (empty($barcodes)) {
            return back()->with(
                'error',
                'No barcodes found.'
            );
        }

        return response(
            implode(',', $barcodes)
        )
            ->header('Content-Type', 'text/plain')
            ->header(
                'Content-Disposition',
                'attachment; filename=barcodes_' .
                    now()->format('Ymd_His') .
                    '.txt'
            );
    }

    public function deleteOrdersWithLog(Request $request)
    {
        if ($this->isClient()) {
            abort(403);
        }

        $request->validate([
            'from_date' => 'required|date',
            'to_date'   => 'required|date',
        ]);

        DB::transaction(function () use ($request) {

            $orders = DB::table('orders')
                ->whereDate('date', '>=', $request->from_date)
                ->whereDate('date', '<=', $request->to_date)
                ->get();

            foreach ($orders as $order) {

                DB::table('order_delete_logs')->insert([
                    'barcode'     => $order->barcode,
                    'order_date'  => $order->date,
                    'deleted_by'  => Auth::user()->name,
                    'deleted_at'  => now(),
                ]);
            }

            DB::table('orders')
                ->whereDate('date', '>=', $request->from_date)
                ->whereDate('date', '<=', $request->to_date)
                ->delete();
        });

        return back()->with(
            'success',
            'Orders deleted successfully.'
        );
    }

    public function importForm()
    {
        if ($this->isClient()) {

            $clients = Client::where(
                'id',
                $this->clientId()
            )->get();
        } else {

            $clients = Client::orderBy(
                'client_name'
            )->get();
        }

        return view(
            'orders.import',
            compact('clients')
        );
    }


    public function importExcel(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls'
        ]);

        $import = new OrdersImport(
            $this->isClient()
                ? $this->clientId()
                : null
        );

        Excel::import(
            $import,
            $request->file('file')
        );

        $message =
            $import->imported .
            ' orders imported successfully.';

        if (count($import->duplicates)) {

            $message .=
                ' Duplicate IDs: ' .
                implode(',', $import->duplicates);

            return back()->with(
                'warning',
                $message
            );
        }

        return back()->with(
            'success',
            $message
        );
    }

    public function finalLabelExport()
    {
        $query = ShopifyOrder::query();

        if ($this->isClient()) {

            $query->where(
                'client_id',
                $this->clientId()
            );
        }

        $orders = $query->get();

        return Excel::download(
            new FinalLabelExport($orders),
            'Courier-Labels.xlsx'
        );
    }
    public function labelIndex()
    {
        if ($this->isClient()) {

            $senders = LabelSender::where(
                'client_id',
                $this->clientId()
            )
                ->orderBy('customer_name')
                ->get();

            $clients = Client::where(
                'id',
                $this->clientId()
            )->get();
        } else {

            $senders = LabelSender::orderBy('customer_name')->get();

            $clients = Client::orderBy('client_name')->get();
        }
        // Get selected import date
        $importDate = request()->get('import_date');

        if (empty($importDate)) {

            $query = ShopifyOrder::query();

            if ($this->isClient()) {
                $query->where('client_id', $this->clientId());
            }

            $importDate = $query
                ->orderByDesc('created_at')
                ->value(DB::raw('DATE(created_at)'));
        }

        $importDate = $importDate ?: date('Y-m-d');

        $query = ShopifyOrder::query();

        if ($this->isClient()) {
            $query->where('client_id', $this->clientId());
        }

        $query->whereDate('created_at', $importDate);

        $orders = $query->latest()->paginate(20);

        $canGenerate = ShopifyOrder::whereDate(
            'created_at',
            $importDate
        )

            ->when($this->isClient(), function ($q) {

                $q->where(
                    'client_id',
                    $this->clientId()
                );
            })

            ->where(
                'postoffice_exported',
                1
            )

            ->exists();
        return view(
            'labels.index',
            compact(

                'orders',

                'clients',

                'senders',

                'canGenerate',

                'importDate'

            )
        );
    }
}
