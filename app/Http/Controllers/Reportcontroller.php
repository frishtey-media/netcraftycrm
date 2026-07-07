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


    public function index($type)
    {

        $query = Order::query()
            ->whereDate(
                'orders.created_at',
                '>=',
                now()->subDays(30)
            );

        /*
        |--------------------------------------------------------------------------
        | Client Login
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

            /*
            |--------------------------------------------------------------------------
            | Not Book India Post
            |--------------------------------------------------------------------------
            */

            case 'not_booked':

                $title = 'Not Book India Post';

                $query->where(function ($q) {

                    $q->whereNull('orders.delivery_status')

                        ->orWhere('orders.delivery_status', '');
                });

                break;


            /*
            |--------------------------------------------------------------------------
            | 7 Days In Transit
            |--------------------------------------------------------------------------
            */

            case 'transit7':

                $title = '7 Days In Transit';

                $query->where('orders.delivery_status', 'In Transit')

                    ->whereNotNull('orders.intransitdate')

                    ->whereDate(
                        'orders.intransitdate',
                        '<=',
                        now()->subDays(7)
                    );

                break;


            /*
            |--------------------------------------------------------------------------
            | RTO Not Received
            |--------------------------------------------------------------------------
            */

            case 'rto5':

                $title = 'RTO Not Received';

                $query->where('orders.delivery_status', 'RTO')

                    ->where('orders.rtorecivedsts', 0)

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
        | Total Orders
        |--------------------------------------------------------------------------
        */

        $totalOrders = (clone $query)->count();

        /*
        |--------------------------------------------------------------------------
        | Client Wise Summary
        |--------------------------------------------------------------------------
        */

        $clientSummary = (clone $query)

            ->join(
                'clients',
                'clients.id',
                '=',
                'orders.client_id'
            )

            ->select(

                'clients.client_name',

                DB::raw('COUNT(*) as total')

            )

            ->groupBy(

                'clients.client_name'

            )

            ->orderByDesc('total')

            ->get();

        /*
        |--------------------------------------------------------------------------
        | Orders
        |--------------------------------------------------------------------------
        */

        $orders = (clone $query)

            ->with('client')

            ->latest()

            ->get();
        /*
        |--------------------------------------------------------------------------
        | Return View
        |--------------------------------------------------------------------------
        */

        return view(
            'reports.orders',
            compact(
                'title',
                'type',
                'totalOrders',
                'clientSummary',
                'orders'
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Export Excel
    |--------------------------------------------------------------------------
    */

    public function export($type)
    {
        $query = Order::query()
            ->whereDate(
                'orders.created_at',
                '>=',
                now()->subDays(30)
            );

        /*
        |--------------------------------------------------------------------------
        | Client Login
        |--------------------------------------------------------------------------
        */

        if (auth()->check() && auth()->user()->role == 'client') {

            $query->where(
                'orders.client_id',
                auth()->user()->client_id
            );
        }

        switch ($type) {

            case 'not_booked':

                $query->where(function ($q) {

                    $q->whereNull('orders.delivery_status')
                        ->orWhere('orders.delivery_status', '');
                });

                break;

            case 'transit7':

                $query->where('orders.delivery_status', 'In Transit')
                    ->whereNotNull('orders.intransitdate')
                    ->whereDate(
                        'orders.intransitdate',
                        '<=',
                        now()->subDays(7)
                    );

                break;

            case 'rto_not_received':

                $query->where('orders.delivery_status', 'RTO')
                    ->where('orders.rtorecivedsts', 0)
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

        $orders = $query
            ->with('client')
            ->latest()
            ->get();

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\OrdersReportExport($orders),
            $type . '_' . now()->format('Ymd_His') . '.xlsx'
        );
    }
}
