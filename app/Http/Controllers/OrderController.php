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
    public function index(Request $request)
    {
        $sortOrder = $request->get('sort_order', 'desc');

        $query = Order::with([
            'callingOrder.staff'
        ]);

        // Client Login
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
            )->orderBy('customer_name')->get();
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
        // Product Filter
        if ($request->filled('product')) {

            $query->where('product', $request->product);
        }
        // Staff Filter
        if ($request->filled('staff_id')) {

            $query->whereHas('callingOrder', function ($q) use ($request) {

                $q->where(
                    'assigned_to',
                    $request->staff_id
                );
            });
        }

        // Status Filter
        if ($request->filled('delivery_status')) {

            if ($request->delivery_status == 'null') {

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

        // Date Filter
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
        // $summaryQuery = clone $query;

        // ORDERS
        $totalOrders = (clone $query)->count();

        $webOrders = (clone $query)
            ->where(function ($main) {
                $main->whereDoesntHave('callingOrder')
                    ->orWhereHas('callingOrder', function ($q) {
                        $q->whereNull('order_source')
                            ->orWhere('order_source', '');
                    });
            })
            ->count();

        $whatsappOrders = $totalOrders - $webOrders;


        // DELIVERED
        $totalDelivered = (clone $query)
            ->where('delivery_status', 'Delivered')
            ->count();

        $webDelivered = (clone $query)
            ->where('delivery_status', 'Delivered')
            ->where(function ($main) {
                $main->whereDoesntHave('callingOrder')
                    ->orWhereHas('callingOrder', function ($q) {
                        $q->whereNull('order_source')
                            ->orWhere('order_source', '');
                    });
            })
            ->count();

        $whatsappDelivered = $totalDelivered - $webDelivered;


        // PAYMENTS (Based on pay_bill_date)

        // PAYMENTS (Based on pay_bill_date)

        $paymentQuery = Order::query();

        // Client Filter
        if ($this->isClient()) {
            $paymentQuery->where('client_id', $this->clientId());
        } elseif ($request->filled('client_id')) {
            $paymentQuery->where('client_id', $request->client_id);
        }

        // Staff Filter
        if ($request->filled('staff_id')) {
            $paymentQuery->whereHas('callingOrder', function ($q) use ($request) {
                $q->where('assigned_to', $request->staff_id);
            });
        }

        // Payment Date Filter
        if ($request->filled('date_from')) {
            $paymentQuery->whereRaw(
                "STR_TO_DATE(pay_bill_date,'%d-%m-%Y') >= ?",
                [$request->date_from]
            );
        }

        if ($request->filled('date_to')) {
            $paymentQuery->whereRaw(
                "STR_TO_DATE(pay_bill_date,'%d-%m-%Y') <= ?",
                [$request->date_to]
            );
        }


        if ($request->filled('search')) {

            $items = preg_split('/[\r\n,]+/', trim($request->search));

            $items = array_filter(array_map('trim', $items));

            $query->where(function ($q) use ($items) {

                $q->whereIn('orders.barcode', $items)
                    ->orWhereIn('orders.order_id', $items)
                    ->orWhereIn('orders.customer_phone', $items);

                foreach ($items as $item) {

                    $q->orWhere('orders.customer_name', 'LIKE', "%{$item}%");
                }
            });
        }
        // Received
        $paymentReceivedOrders = (clone $query)
            ->where('delivery_status', 'Delivered')
            ->whereNotNull('pay_bill_date')
            ->where('pay_bill_date', '!=', '')
            ->count();

        $paymentReceivedAmount = (clone $query)
            ->where('delivery_status', 'Delivered')
            ->whereNotNull('pay_bill_date')
            ->where('pay_bill_date', '!=', '')
            ->sum('receivedcodamt');

        // Pending
        $paymentPendingOrders = (clone $query)
            ->where('delivery_status', 'Delivered')
            ->where(function ($q) {
                $q->whereNull('pay_bill_date')
                    ->orWhere('pay_bill_date', '');
            })
            ->count();

        $paymentPendingAmount = (clone $query)
            ->where('delivery_status', 'Delivered')
            ->where(function ($q) {
                $q->whereNull('pay_bill_date')
                    ->orWhere('pay_bill_date', '');
            })
            ->sum('amount');


        // RTO
        $totalRto = (clone $query)
            ->where('delivery_status', 'RTO')
            ->count();

        $webRto = (clone $query)
            ->where('delivery_status', 'RTO')
            ->where(function ($main) {
                $main->whereDoesntHave('callingOrder')
                    ->orWhereHas('callingOrder', function ($q) {
                        $q->whereNull('order_source')
                            ->orWhere('order_source', '');
                    });
            })
            ->count();

        $whatsappRto = $totalRto - $webRto;

        // Total RTO Received
        $totalRtoReceived = (clone $query)
            ->where('rtorecivedsts', 1)
            ->count();

        // Web RTO Received
        $webRtoReceived = (clone $query)
            ->where('rtorecivedsts', 1)
            ->where(function ($main) {
                $main->whereDoesntHave('callingOrder')
                    ->orWhereHas('callingOrder', function ($q) {
                        $q->whereNull('order_source')
                            ->orWhere('order_source', '');
                    });
            })
            ->count();

        // WhatsApp RTO Received
        $whatsappRtoReceived = $totalRtoReceived - $webRtoReceived;


        // IN TRANSIT
        $totalTransit = (clone $query)
            ->whereIn('delivery_status', [
                'In Transit',
                'Out For Delivery'
            ])
            ->count();

        $webTransit = (clone $query)
            ->whereIn('delivery_status', [
                'In Transit',
                'Out For Delivery'
            ])
            ->where(function ($main) {
                $main->whereDoesntHave('callingOrder')
                    ->orWhereHas('callingOrder', function ($q) {
                        $q->whereNull('order_source')
                            ->orWhere('order_source', '');
                    });
            })
            ->count();

        $whatsappTransit = $totalTransit - $webTransit;


        // NO STATUS
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
            ->where(function ($main) {
                $main->whereDoesntHave('callingOrder')
                    ->orWhereHas('callingOrder', function ($q) {
                        $q->whereNull('order_source')
                            ->orWhere('order_source', '');
                    });
            })
            ->count();

        $whatsappNoStatus = $totalNoStatus - $webNoStatus;
        /*
|--------------------------------------------------------------------------
| DASHBOARD ANALYTICS
|--------------------------------------------------------------------------
*/

        $trendQuery = clone $query;

        $dailyOrders = $trendQuery
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

            $labels[] = Carbon::parse($row->order_date)->format('d M');

            $totalOrdersChart[] = $row->total;
        }




        $webTrend = (clone $query)

            ->where(function ($main) {

                $main->whereDoesntHave('callingOrder')

                    ->orWhereHas('callingOrder', function ($q) {

                        $q->whereNull('order_source')

                            ->orWhere('order_source', '');
                    });
            })

            ->select(

                DB::raw('DATE(created_at) as order_date'),

                DB::raw('COUNT(*) as total')

            )

            ->groupBy('order_date')

            ->orderBy('order_date')

            ->pluck('total', 'order_date');

        $webOrdersChart = [];

        foreach ($dailyOrders as $day) {

            $webOrdersChart[] = $webTrend[$day->order_date] ?? 0;
        }

        $waTrend = (clone $query)

            ->whereHas('callingOrder', function ($q) {

                $q->whereNotNull('order_source')

                    ->where('order_source', '!=', '');
            })

            ->select(

                DB::raw('DATE(created_at) as order_date'),

                DB::raw('COUNT(*) as total')

            )

            ->groupBy('order_date')

            ->orderBy('order_date')

            ->pluck('total', 'order_date');

        $waOrdersChart = [];

        foreach ($dailyOrders as $day) {

            $waOrdersChart[] = $waTrend[$day->order_date] ?? 0;
        }


        $deliveryTrend = (clone $query)

            ->where('delivery_status', 'Delivered')

            ->select(

                DB::raw('DATE(created_at) as order_date'),

                DB::raw('COUNT(*) as total')

            )

            ->groupBy('order_date')

            ->orderBy('order_date')

            ->pluck('total', 'order_date');

        $deliveryChart = [];

        foreach ($dailyOrders as $day) {

            $deliveryChart[] = $deliveryTrend[$day->order_date] ?? 0;
        }


        $rtoTrend = (clone $query)

            ->where('delivery_status', 'RTO')

            ->select(

                DB::raw('DATE(created_at) as order_date'),

                DB::raw('COUNT(*) as total')

            )

            ->groupBy('order_date')

            ->orderBy('order_date')

            ->pluck('total', 'order_date');

        $rtoChart = [];

        foreach ($dailyOrders as $day) {

            $rtoChart[] = $rtoTrend[$day->order_date] ?? 0;
        }


        $paymentTrend = (clone $query)

            ->where('delivery_status', 'Delivered')

            ->select(

                DB::raw('DATE(created_at) as order_date'),

                DB::raw('SUM(receivedcodamt) as amount')

            )

            ->groupBy('order_date')

            ->orderBy('order_date')

            ->pluck('amount', 'order_date');

        $paymentChart = [];

        foreach ($dailyOrders as $day) {

            $paymentChart[] = $paymentTrend[$day->order_date] ?? 0;
        }

        $sourceChart = [
            $webOrders,
            $whatsappOrders
        ];

        $statusChart = [
            $totalDelivered,
            $totalRto,
            $totalTransit,
            $totalNoStatus
        ];



        /*
|--------------------------------------------------------------------------
| STAFF PERFORMANCE
|--------------------------------------------------------------------------
*/

        $staffPerformance = DB::table('callingorder')
            ->leftJoin('calling_users', 'calling_users.id', '=', 'callingorder.assigned_to')
            ->leftJoin('orders', 'orders.order_id', '=', 'callingorder.order_id')
            ->select(
                'calling_users.id',
                'calling_users.name',
                DB::raw('COUNT(orders.id) as total_orders'),
                DB::raw("SUM(CASE WHEN orders.delivery_status='Delivered' THEN 1 ELSE 0 END) as delivered"),
                DB::raw("SUM(CASE WHEN orders.delivery_status='RTO' THEN 1 ELSE 0 END) as rto")
            )
            ->groupBy('calling_users.id', 'calling_users.name')
            ->orderByDesc('total_orders')
            ->get();

        foreach ($staffPerformance as $staff) {

            $staff->success = $staff->total_orders
                ? round(($staff->delivered * 100) / $staff->total_orders, 2)
                : 0;
        }


        /*
|--------------------------------------------------------------------------
| TOP CLIENTS
|--------------------------------------------------------------------------
*/

        $topClients = DB::table('orders')

            ->leftJoin('clients', 'clients.id', '=', 'orders.client_id')

            ->leftJoin('callingorder', 'callingorder.order_id', '=', 'orders.order_id')

            ->select(

                'clients.client_name',

                DB::raw('COUNT(orders.id) as total_orders'),

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

        // Delivery %
        $deliveryRate = $totalOrders
            ? round(($totalDelivered / $totalOrders) * 100, 2)
            : 0;

        // RTO %
        $rtoRate = $totalOrders
            ? round(($totalRto / $totalOrders) * 100, 2)
            : 0;

        // Transit %
        $transitRate = $totalOrders
            ? round(($totalTransit / $totalOrders) * 100, 2)
            : 0;

        // Pending %
        $pendingRate = $totalOrders
            ? round(($totalNoStatus / $totalOrders) * 100, 2)
            : 0;

        // Best Day
        $bestDay = (clone $query)

            ->select(
                DB::raw('DATE(created_at) as day'),
                DB::raw('COUNT(*) total')
            )

            ->groupBy('day')

            ->orderByDesc('total')

            ->first();

        // Average Orders
        $averageOrders = $labels
            ? round($totalOrders / count($labels), 2)
            : 0;

        // Highest Payment Day
        $highestPayment = (clone $query)

            ->whereNotNull('pay_bill_date')

            ->select(
                DB::raw("STR_TO_DATE(pay_bill_date,'%d-%m-%Y') as pay_date"),
                DB::raw("SUM(receivedcodamt) as amount")
            )

            ->groupBy('pay_date')

            ->orderByDesc('amount')

            ->first();

        // Best Staff
        $bestStaff = $staffPerformance->sortByDesc('delivered')->first();
        $perPage = $request->get('per_page', 100);

        $orders = $query
            ->orderBy('created_at', $sortOrder)
            ->paginate($perPage)
            ->withQueryString();

        $searchTerms = [];
        $notFound = [];

        if ($request->filled('search')) {

            $searchTerms = preg_split('/[\r\n,]+/', trim($request->search));
            $searchTerms = array_filter(array_map('trim', $searchTerms));

            $query->where(function ($q) use ($searchTerms) {

                foreach ($searchTerms as $term) {

                    $q->orWhere('order_id', 'LIKE', "%{$term}%")
                        ->orWhere('barcode', 'LIKE', "%{$term}%")
                        ->orWhere('customer_phone', 'LIKE', "%{$term}%")
                        ->orWhere('customer_name', 'LIKE', "%{$term}%");
                }
            });

            // Not Found Check
            $foundValues = [];

            foreach ($searchTerms as $term) {

                $matches = Order::where('order_id', 'LIKE', "%{$term}%")
                    ->orWhere('barcode', 'LIKE', "%{$term}%")
                    ->orWhere('customer_phone', 'LIKE', "%{$term}%")
                    ->orWhere('customer_name', 'LIKE', "%{$term}%")
                    ->exists();

                if ($matches) {
                    $foundValues[] = strtoupper(trim($term));
                }
            }

            $notFound = collect($searchTerms)
                ->map(fn($v) => strtoupper(trim($v)))
                ->reject(fn($item) => in_array($item, $foundValues))
                ->values()
                ->toArray();
        }
        $staffs = CallingUser::orderBy('name')->get();
        $ourSidePending = (clone $query)
            ->where(function ($q) {
                $q->whereNull('delivery_status')
                    ->orWhere('delivery_status', '');
            })
            ->count();

        $indiaPostPending = (clone $query)
            ->whereNotNull('delivery_status')
            ->where('delivery_status', '!=', '')
            ->where('delivery_status', '!=', 'Delivered')
            ->where('delivery_status', '!=', 'RTO')
            ->count();

        $transit7Days = (clone $query)
            ->whereNotNull('delivery_status')
            ->where('delivery_status', '!=', '')
            ->where('delivery_status', '!=', 'Delivered')
            ->where('delivery_status', '!=', 'RTO')
            ->whereDate('created_at', '<=', Carbon::now()->subDays(7))
            ->count();



        $paymentReceivedPercent = $totalOrders > 0 ? round(($paymentReceivedOrders / $totalOrders) * 100, 2) : 0;

        $paymentPendingPercent = $totalOrders > 0 ? round(($paymentPendingOrders / $totalOrders) * 100, 2) : 0;


        $webDeliveredPercent = $webOrders > 0
            ? round(($webDelivered / $webOrders) * 100, 2)
            : 0;

        $webRtoPercent = $webOrders > 0
            ? round(($webRto / $webOrders) * 100, 2)
            : 0;

        $webTransitPercent = $webOrders > 0
            ? round(($webTransit / $webOrders) * 100, 2)
            : 0;

        $webNoStatusPercent = $webOrders > 0
            ? round(($webNoStatus / $webOrders) * 100, 2)
            : 0;

        $webRtoReceivedPercent = $webOrders > 0
            ? round(($webRtoReceived / $webOrders) * 100, 2)
            : 0;

        $waDeliveredPercent = $whatsappOrders > 0
            ? round(($whatsappDelivered / $whatsappOrders) * 100, 2)
            : 0;

        $waRtoPercent = $whatsappOrders > 0
            ? round(($whatsappRto / $whatsappOrders) * 100, 2)
            : 0;

        $waTransitPercent = $whatsappOrders > 0
            ? round(($whatsappTransit / $whatsappOrders) * 100, 2)
            : 0;

        $waNoStatusPercent = $whatsappOrders > 0
            ? round(($whatsappNoStatus / $whatsappOrders) * 100, 2)
            : 0;

        $waRtoReceivedPercent = $whatsappOrders > 0
            ? round(($whatsappRtoReceived / $whatsappOrders) * 100, 2)
            : 0;

        // =========================================
        // COMPARE REPORT
        // =========================================

        $compareData = null;
        $comparePercent = null;
        if ($request->filled('compare_from') && $request->filled('compare_to')) {

            $compareQuery = Order::query()
                ->leftJoin(
                    'callingorder',
                    'callingorder.order_id',
                    '=',
                    'orders.order_id'
                )
                ->select('orders.*', 'callingorder.order_source', 'callingorder.assigned_to');

            // Client Login
            if ($this->isClient()) {
                $compareQuery->where('orders.client_id', $this->clientId());
            }

            // Client Filter
            if ($request->filled('client_id')) {
                $compareQuery->where('orders.client_id', $request->client_id);
            }

            // Product Filter
            if ($request->filled('product')) {
                $compareQuery->where('orders.product_name', $request->product);
            }

            // Status Filter
            if ($request->filled('delivery_status')) {

                if ($request->delivery_status == 'null') {

                    $compareQuery->whereNull('orders.delivery_status');
                } else {

                    $compareQuery->where(
                        'orders.delivery_status',
                        $request->delivery_status
                    );
                }
            }

            // Staff Filter
            if ($request->filled('staff_id')) {

                $compareQuery->where(
                    'callingorder.assigned_to',
                    $request->staff_id
                );
            }

            // Compare Date
            $compareQuery->whereBetween('orders.date', [
                $request->compare_from,
                $request->compare_to
            ]);


            $compareData = [

                'totalOrders' => (clone $compareQuery)->count(),

                'webOrders' => (clone $compareQuery)
                    ->whereNull('callingorder.order_source')
                    ->count(),

                'whatsappOrders' => (clone $compareQuery)
                    ->where('callingorder.order_source', 'whatsapp')
                    ->count(),

                'totalDelivered' => (clone $compareQuery)
                    ->where('orders.delivery_status', 'Delivered')
                    ->count(),

                'webDelivered' => (clone $compareQuery)
                    ->where('orders.delivery_status', 'Delivered')
                    ->whereNull('order_source')
                    ->count(),

                'whatsappDelivered' => (clone $compareQuery)
                    ->where('orders.delivery_status', 'Delivered')
                    ->where('order_source', 'whatsapp')
                    ->count(),

                'totalRto' => (clone $compareQuery)
                    ->where('orders.delivery_status', 'RTO')
                    ->count(),

                'webRto' => (clone $compareQuery)
                    ->where('orders.delivery_status', 'RTO')
                    ->whereNull('order_source')
                    ->count(),

                'whatsappRto' => (clone $compareQuery)
                    ->where('orders.delivery_status', 'RTO')
                    ->where('order_source', 'whatsapp')
                    ->count(),

                'totalTransit' => (clone $compareQuery)
                    ->where('orders.delivery_status', 'In Transit')
                    ->count(),

                'webTransit' => (clone $compareQuery)
                    ->where('orders.delivery_status', 'In Transit')
                    ->whereNull('order_source')
                    ->count(),

                'whatsappTransit' => (clone $compareQuery)
                    ->where('orders.delivery_status', 'In Transit')
                    ->where('order_source', 'whatsapp')
                    ->count(),

                'totalNoStatus' => (clone $compareQuery)
                    ->whereNull('orders.delivery_status')
                    ->count(),

                'webNoStatus' => (clone $compareQuery)
                    ->whereNull('orders.delivery_status')
                    ->whereNull('order_source')
                    ->count(),

                'whatsappNoStatus' => (clone $compareQuery)
                    ->whereNull('orders.delivery_status')
                    ->where('order_source', 'whatsapp')
                    ->count(),

                'paymentReceivedOrders' => (clone $compareQuery)
                    ->whereNotNull('orders.pay_bill_date')
                    ->count(),

                'paymentReceivedAmount' => (clone $compareQuery)
                    ->whereNotNull('orders.pay_bill_date')
                    ->sum('receivedcodamt'),

                'paymentPendingOrders' => (clone $compareQuery)
                    ->where('orders.delivery_status', 'Delivered')
                    ->whereNull('orders.pay_bill_date')
                    ->count(),

                'paymentPendingAmount' => (clone $compareQuery)
                    ->where('delivery_status', 'Delivered')
                    ->whereNull('pay_bill_date')
                    ->sum('orders.amount')

            ];
        }

        $calculatePercentage = function ($current, $compare) {

            if ($compare == 0) {
                return $current > 0 ? 100 : 0;
            }

            return round((($current - $compare) / $compare) * 100, 2);
        };

        $comparePercent = [];

        if ($compareData) {

            $comparePercent = [

                'orders' => $calculatePercentage(
                    $totalOrders,
                    $compareData['totalOrders']
                ),

                'delivered' => $calculatePercentage(
                    $totalDelivered,
                    $compareData['totalDelivered']
                ),

                'rto' => $calculatePercentage(
                    $totalRto,
                    $compareData['totalRto']
                ),

                'transit' => $calculatePercentage(
                    $totalTransit,
                    $compareData['totalTransit']
                ),

                'noStatus' => $calculatePercentage(
                    $totalNoStatus,
                    $compareData['totalNoStatus']
                ),

                'paymentReceived' => $calculatePercentage(
                    $paymentReceivedAmount,
                    $compareData['paymentReceivedAmount']
                ),

                'paymentPending' => $calculatePercentage(
                    $paymentPendingAmount,
                    $compareData['paymentPendingAmount']
                ),

            ];
        }
        return view(
            'orders.index',
            compact(
                'comparePercent',
                'compareData',
                'webDeliveredPercent',
                'webRtoPercent',
                'webTransitPercent',
                'webNoStatusPercent',
                'webRtoReceivedPercent',
                'waDeliveredPercent',

                'waRtoPercent',
                'waTransitPercent',
                'waNoStatusPercent',
                'waRtoReceivedPercent',

                'paymentReceivedPercent',
                'paymentPendingPercent',
                'ourSidePending',
                'indiaPostPending',
                'transit7Days',
                'orders',
                'clients',
                'senders',
                'staffs',

                'totalOrders',
                'webOrders',
                'whatsappOrders',

                'totalDelivered',
                'webDelivered',
                'whatsappDelivered',

                'paymentReceivedOrders',
                'paymentReceivedAmount',

                'paymentPendingOrders',
                'paymentPendingAmount',

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
