<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;
use App\Imports\PaymentImport;
use App\Models\Client;

use App\Exports\DeliveryReportExport;

class DeliveryController extends Controller
{
    public function index(Request $request)
    {
        $clients = Client::orderBy('client_name')->get();

        $query = Order::query();

        if ($request->filled('client_id')) {
            $query->where('client_id', $request->client_id);
        }

        if ($request->filled('from_date')) {
            $query->whereDate('date', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('date', '<=', $request->to_date);
        }

        $totalOrders = (clone $query)->count();

        $deliveredOrders = (clone $query)
            ->where('delivery_status', 'Delivered')
            ->count();

        $paymentReceived = (clone $query)
            ->where('recivedpaysts', 1)
            ->sum('receivedcodamt');

        $paymentPending = (clone $query)
            ->where('delivery_status', 'Delivered')
            ->where(function ($q) {
                $q->whereNull('recivedpaysts')
                    ->orWhere('recivedpaysts', 0);
            })
            ->count();

        $totalRTO = (clone $query)
            ->where('delivery_status', 'RTO')
            ->count();

        $rtoReceived = (clone $query)
            ->where('delivery_status', 'RTO')
            ->where('rtorecivedsts', 1)
            ->count();

        $rtoPending = (clone $query)
            ->where('delivery_status', 'RTO')
            ->where(function ($q) {
                $q->whereNull('rtorecivedsts')
                    ->orWhere('rtorecivedsts', 0);
            })
            ->count();

        $inTransit = (clone $query)
            ->where('delivery_status', 'In Transit')
            ->count();
        $lastDeliveryUpdate = Order::whereNotNull('delivery_status')
            ->max('delivery_date');

        $lastPaymentUpdate = Order::where('recivedpaysts', 1)
            ->max('updated_at');
        return view('delivery.index', compact(
            'clients',
            'totalOrders',
            'deliveredOrders',
            'paymentReceived',
            'paymentPending',
            'totalRTO',
            'rtoReceived',
            'rtoPending',
            'inTransit',
            'lastDeliveryUpdate',
            'lastPaymentUpdate'
        ));
    }
    public function report(Request $request, $type)
    {
        $query = Order::with('client');

        // Client Filter
        if ($request->filled('client_id')) {
            $query->where('client_id', $request->client_id);
        }

        // Date Filter
        if ($request->filled('from_date')) {
            $query->whereDate('date', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('date', '<=', $request->to_date);
        }

        switch ($type) {

            case 'delivered':
                $query->where('delivery_status', 'Delivered');
                break;

            case 'payment_received':
                $query->where('delivery_status', 'Delivered')
                    ->where('recivedpaysts', 1);
                break;

            case 'payment_pending':
                $query->where('delivery_status', 'Delivered')
                    ->where(function ($q) {
                        $q->whereNull('recivedpaysts')
                            ->orWhere('recivedpaysts', 0);
                    });
                break;

            case 'rto':
                $query->where('delivery_status', 'RTO');
                break;

            case 'rto_received':
                $query->where('delivery_status', 'RTO')
                    ->where('rtorecivedsts', 1);
                break;

            case 'rto_pending':
                $query->where('delivery_status', 'RTO')
                    ->where(function ($q) {
                        $q->whereNull('rtorecivedsts')
                            ->orWhere('rtorecivedsts', 0);
                    });
                break;

            case 'in_transit':
                $query->where('delivery_status', 'In Transit');
                break;

            case 'all':
                break;
        }

        // Datatable use kar rahe ho to pagination mat use karo
        $orders = $query->orderBy('date', 'desc')->get();

        return view('delivery.report', compact('orders', 'type'));
    }
    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv'
        ]);

        $rows = Excel::toArray([], $request->file('file'));

        $updated = 0;
        $notFound = 0;

        foreach ($rows[0] as $key => $row) {

            // Skip Header
            if ($key == 0) {
                continue;
            }

            // Excel Columns
            $trackingNo = trim($row[1] ?? '');   // Article Number
            $status     = trim($row[6] ?? '');   // Status
            $lastEvent  = trim($row[7] ?? '');   // Last Event

            if (empty($trackingNo)) {
                continue;
            }

            $order = Order::where('barcode', $trackingNo)->first();

            if (!$order) {
                $notFound++;
                continue;
            }

            $statusLower = strtolower(trim($status));
            $eventLower  = strtolower(trim($lastEvent));

            /*
        |--------------------------------------------------------------------------
        | CRM Status Mapping
        |--------------------------------------------------------------------------
        */

            if (
                str_contains($eventLower, 'sender') ||
                str_contains($eventLower, 'return to sender') ||
                str_contains($eventLower, 'returned to sender')
            ) {

                $crmStatus = 'RTO';
            } elseif (
                $statusLower == 'delivered' &&
                str_contains($eventLower, 'addressee')
            ) {

                $crmStatus = 'Delivered';
            } elseif (
                $statusLower == 'not delivered' ||
                str_contains($eventLower, 'in transit') ||
                str_contains($eventLower, 'out for delivery') ||
                str_contains($eventLower, 'bagged') ||
                str_contains($eventLower, 'received') ||
                str_contains($eventLower, 'dispatched') ||
                str_contains($eventLower, 'booked')
            ) {

                $crmStatus = 'In Transit';
            } else {

                $crmStatus = 'In Transit';
            }

            /*
        |--------------------------------------------------------------------------
        | Extract Event Date
        |--------------------------------------------------------------------------
        */

            $eventDate = null;

            if (preg_match('/(\d{2}\/\d{2}\/\d{4})/', $lastEvent, $matches)) {

                try {

                    $eventDate = Carbon::createFromFormat(
                        'd/m/Y',
                        $matches[1]
                    )->format('Y-m-d');
                } catch (\Exception $e) {

                    $eventDate = null;
                }
            }

            /*
        |--------------------------------------------------------------------------
        | Save Order
        |--------------------------------------------------------------------------
        */

            $order->delivery_status = $crmStatus;
            $order->delivery_remark = $lastEvent;

            // Delivered Date
            if ($crmStatus == 'Delivered' && $eventDate) {

                $order->delivery_date = $eventDate;
            }

            // RTO Date
            if ($crmStatus == 'RTO' && $eventDate) {

                if (empty($order->rtodate)) {
                    $order->rtodate = $eventDate;
                }
            }

            // Transit Date
            if ($crmStatus == 'In Transit' && $eventDate) {

                if (empty($order->intransitdate)) {
                    $order->intransitdate = $eventDate;
                }
            }

            $order->save();

            $updated++;
        }

        return back()->with(
            'delivery_success',
            "{$updated} records updated successfully. {$notFound} tracking numbers not found."
        );
    }
    public function paymentupload(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv'
        ]);

        Excel::import(new PaymentImport, $request->file('file'));

        return back()->with(
            'payment_success',
            'Payment uploaded successfully.'
        );
    }


    public function rtoReceivedUpload(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv'
        ]);

        $rows = Excel::toArray([], $request->file('file'));

        $trackingNumbers = [];

        foreach ($rows[0] as $index => $row) {

            // Skip header
            if ($index == 0) {
                continue;
            }

            if (!empty($row[2])) { // tracking_no column
                $trackingNumbers[] = trim($row[2]);
            }
        }

        $updated = Order::whereIn('barcode', $trackingNumbers)
            ->update([
                'rtorecivedsts' => 1
            ]);

        return back()->with(
            'rto_success',
            $updated . ' RTO records updated successfully.'
        );
    }
    public function export(Request $request, $type)
    {
        $query = Order::with('client');

        if ($request->filled('client_id')) {
            $query->where('client_id', $request->client_id);
        }

        if ($request->filled('from_date')) {
            $query->whereDate('date', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('date', '<=', $request->to_date);
        }

        switch ($type) {

            case 'delivered':
                $query->where('delivery_status', 'Delivered');
                break;

            case 'payment_received':
                $query->where('delivery_status', 'Delivered')
                    ->where('recivedpaysts', 1);
                break;

            case 'payment_pending':
                $query->where('delivery_status', 'Delivered')
                    ->where('recivedpaysts', 0);
                break;

            case 'rto':
                $query->where('delivery_status', 'RTO');
                break;

            case 'rto_received':
                $query->where('delivery_status', 'RTO')
                    ->where('rtorecivedsts', 1);
                break;

            case 'rto_pending':
                $query->where('delivery_status', 'RTO')
                    ->where('rtorecivedsts', 0);
                break;

            case 'in_transit':
                $query->where('delivery_status', 'In Transit');
                break;
        }

        $orders = $query->get();

        return Excel::download(
            new DeliveryReportExport($orders),
            $type . '_report.xlsx'
        );
    }
}
