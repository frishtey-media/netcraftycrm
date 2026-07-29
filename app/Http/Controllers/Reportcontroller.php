<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CallingUser;
use App\Models\Order;

use App\Models\Client;
use App\Models\callingorder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Exports\OrdersReportExport;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    public function staffReport(Request $request, $staff_id)
    {
        // $from = $request->from ?? now()->startOfMonth()->format('Y-m-d');
        $from = $request->from ?? now()->format('Y-m-d');
        $to   = $request->to ?? now()->format('Y-m-d');

        // Staff Details
        $staff = CallingUser::findOrFail($staff_id);

        // Calling Orders Query
        $orders = CallingOrder::where('assigned_to', $staff_id)
            ->whereBetween('created_at', [
                $from . ' 00:00:00',
                $to . ' 23:59:59'
            ]);

        $totalOrders = (clone $orders)->count();

        $webOrders = (clone $orders)
            ->whereNull('order_source')
            ->count();

        $whatsappOrders = (clone $orders)
            ->where('order_source', 'whatsapp')
            ->count();

        $confirmedOrders = (clone $orders)
            ->where('status', 'verified')
            ->count();

        $pendingOrders = (clone $orders)
            ->where('status', 'pending')
            ->count();

        $not_reachable = (clone $orders)
            ->where('status', 'not_reachable')
            ->count();

        $same_order = (clone $orders)
            ->where('status', 'same_order')
            ->count();

        $cancel = (clone $orders)
            ->where('status', 'cancel')
            ->count();
        $notReachableOrders = (clone $orders)
            ->where('status', 'not reachable')
            ->count();

        // Delivery Orders Query
        $deliveryOrders = callingorder::where('assigned_to', $staff_id)
            ->whereBetween('created_at', [
                $from . ' 00:00:00',
                $to . ' 23:59:59'
            ]);

        $delivered = CallingOrder::join(
            'orders',
            'orders.order_id',
            '=',
            'callingorder.order_id'
        )
            ->where('callingorder.assigned_to', $staff_id)
            ->whereBetween('callingorder.created_at', [
                $from . ' 00:00:00',
                $to . ' 23:59:59'
            ])
            ->where('orders.delivery_status', 'Delivered')
            ->count();

        $inTransit = CallingOrder::join(
            'orders',
            'orders.order_id',
            '=',
            'callingorder.order_id'
        )
            ->where('callingorder.assigned_to', $staff_id)
            ->whereBetween('callingorder.created_at', [
                $from . ' 00:00:00',
                $to . ' 23:59:59'
            ])
            ->whereIn('orders.delivery_status', [
                'In Transit',
                'Out For Delivery',
                'Not Delivered'
            ])
            ->count();

        $rto = CallingOrder::join(
            'orders',
            'orders.order_id',
            '=',
            'callingorder.order_id'
        )
            ->where('callingorder.assigned_to', $staff_id)
            ->whereBetween('callingorder.created_at', [
                $from . ' 00:00:00',
                $to . ' 23:59:59'
            ])
            ->whereIn('orders.delivery_status', [
                'Returned',
                'RTO',
                'Failed Delivery'
            ])
            ->count();

        return view('staff-report', compact(
            'staff',
            'from',
            'to',
            'totalOrders',
            'webOrders',
            'whatsappOrders',
            'confirmedOrders',
            'pendingOrders',
            'not_reachable',
            'same_order',
            'cancel',
            'notReachableOrders',
            'delivered',
            'inTransit',
            'rto'
        ));
    }


    public function index(Request $request, $type)
    {
        /*
    |--------------------------------------------------------------------------
    | Base Query - Last 30 Days
    |--------------------------------------------------------------------------
    */

        $query = Order::query()
            ->whereDate(
                'orders.created_at',
                '>=',
                now()->subDays(30)
            );

        /*
    |--------------------------------------------------------------------------
    | Client Login Restriction
    |--------------------------------------------------------------------------
    */

        if (auth()->check() && auth()->user()->role == 'client') {

            $query->where(
                'orders.client_id',
                auth()->user()->client_id
            );
        }

        /*
    |--------------------------------------------------------------------------
    | Report Type
    |--------------------------------------------------------------------------
    */

        switch ($type) {

            case 'not_booked':

                $title = 'Not Book India Post';

                $query->where(function ($q) {

                    $q->whereNull('orders.delivery_status')
                        ->orWhere('orders.delivery_status', '');
                });

                break;


            case 'transit7':

                $title = '7 Days In Transit';

                $query->where(
                    'orders.delivery_status',
                    'In Transit'
                )
                    ->whereNotNull('orders.intransitdate')
                    ->whereDate(
                        'orders.intransitdate',
                        '<=',
                        now()->subDays(7)
                    );

                break;


            case 'rto5':

                $title = 'RTO Not Received';

                $query->where(
                    'orders.delivery_status',
                    'RTO'
                )
                    ->where(
                        'orders.rtorecivedsts',
                        0
                    )
                    ->whereNotNull('orders.rtodate')
                    ->whereDate(
                        'orders.rtodate',
                        '<=',
                        now()->subDays(5)
                    );

                break;


            default:

                abort(404);
        }


        /*
    |--------------------------------------------------------------------------
    | IMPORTANT:
    | Client Summary BEFORE selected client filter
    |--------------------------------------------------------------------------
    |
    | Isliye selected client ke baad bhi sidebar/summary me
    | saare clients visible rahenge.
    |
    */

        $clientSummary = (clone $query)
            ->join(
                'clients',
                'clients.id',
                '=',
                'orders.client_id'
            )
            ->select(
                'orders.client_id',
                'clients.client_name',
                DB::raw('COUNT(*) as total')
            )
            ->groupBy(
                'orders.client_id',
                'clients.client_name'
            )
            ->orderByDesc('total')
            ->get();


        /*
    |--------------------------------------------------------------------------
    | Selected Client Filter
    |--------------------------------------------------------------------------
    */

        $selectedClient = null;

        if (
            auth()->check() &&
            auth()->user()->role != 'client' &&
            $request->filled('client_id')
        ) {

            $selectedClient = $request->client_id;

            $query->where(
                'orders.client_id',
                $selectedClient
            );
        }


        /*
    |--------------------------------------------------------------------------
    | Total Orders - AFTER client filter
    |--------------------------------------------------------------------------
    */

        $totalOrders = (clone $query)->count();


        /*
    |--------------------------------------------------------------------------
    | Orders
    |--------------------------------------------------------------------------
    */

        $orders = (clone $query)
            ->with('client')
            ->orderByDesc('orders.created_at')
            ->get();


        /*
    |--------------------------------------------------------------------------
    | Return
    |--------------------------------------------------------------------------
    */

        return view(
            'reports.orders',
            compact(
                'title',
                'type',
                'totalOrders',
                'clientSummary',
                'orders',
                'selectedClient'
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Export Excel
    |--------------------------------------------------------------------------
    */

    public function export(Request $request, $type)
    {
        /*
    |--------------------------------------------------------------------------
    | Base Query
    |--------------------------------------------------------------------------
    */

        $query = Order::query()
            ->whereDate(
                'orders.created_at',
                '>=',
                now()->subDays(30)
            );


        /*
    |--------------------------------------------------------------------------
    | Client Login Restriction
    |--------------------------------------------------------------------------
    */

        if (auth()->check() && auth()->user()->role == 'client') {

            $query->where(
                'orders.client_id',
                auth()->user()->client_id
            );
        }


        /*
    |--------------------------------------------------------------------------
    | Report Type
    |--------------------------------------------------------------------------
    */

        switch ($type) {

            case 'not_booked':

                $query->where(function ($q) {

                    $q->whereNull('orders.delivery_status')
                        ->orWhere('orders.delivery_status', '');
                });

                break;


            case 'transit7':

                $query->where(
                    'orders.delivery_status',
                    'In Transit'
                )
                    ->whereNotNull('orders.intransitdate')
                    ->whereDate(
                        'orders.intransitdate',
                        '<=',
                        now()->subDays(7)
                    );

                break;


            case 'rto5':

                $query->where(
                    'orders.delivery_status',
                    'RTO'
                )
                    ->where(
                        'orders.rtorecivedsts',
                        0
                    )
                    ->whereNotNull('orders.rtodate')
                    ->whereDate(
                        'orders.rtodate',
                        '<=',
                        now()->subDays(5)
                    );

                break;


            default:

                abort(404);
        }


        /*
    |--------------------------------------------------------------------------
    | Selected Client
    |--------------------------------------------------------------------------
    */

        if (
            auth()->check() &&
            auth()->user()->role != 'client' &&
            $request->filled('client_id')
        ) {

            $query->where(
                'orders.client_id',
                $request->client_id
            );
        }


        /*
    |--------------------------------------------------------------------------
    | Get Orders
    |--------------------------------------------------------------------------
    */

        $orders = $query
            ->with('client')
            ->orderByDesc('orders.created_at')
            ->get();


        /*
    |--------------------------------------------------------------------------
    | File Name
    |--------------------------------------------------------------------------
    */

        $fileName = $type;

        if ($request->filled('client_id')) {

            $client = Client::find($request->client_id);

            if ($client) {

                $fileName .= '_' . \Illuminate\Support\Str::slug(
                    $client->client_name,
                    '_'
                );
            }
        }

        $fileName .= '_' . now()->format('Ymd_His') . '.xlsx';


        /*
    |--------------------------------------------------------------------------
    | Export
    |--------------------------------------------------------------------------
    */

        return Excel::download(
            new OrdersReportExport($orders),
            $fileName
        );
    }
}
