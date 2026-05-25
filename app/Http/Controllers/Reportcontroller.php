<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CallingUser;
use App\Models\Order;
use App\Models\callingorder;

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
                'Out For Delivery'
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
}
