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

        /*
    |--------------------------------------------------------------------------
    | Client Filter
    |--------------------------------------------------------------------------
    */

        if ($request->filled('client_id')) {
            $query->where(
                'client_id',
                $request->client_id
            );
        }

        /*
    |--------------------------------------------------------------------------
    | From Date
    |--------------------------------------------------------------------------
    */

        if ($request->filled('from_date')) {
            $query->whereDate(
                'date',
                '>=',
                $request->from_date
            );
        }

        /*
    |--------------------------------------------------------------------------
    | To Date
    |--------------------------------------------------------------------------
    */

        if ($request->filled('to_date')) {
            $query->whereDate(
                'date',
                '<=',
                $request->to_date
            );
        }

        /*
    |--------------------------------------------------------------------------
    | Total Orders
    |--------------------------------------------------------------------------
    */

        $totalOrders = (clone $query)->count();

        /*
    |--------------------------------------------------------------------------
    | Delivered Orders
    |--------------------------------------------------------------------------
    */

        $deliveredOrders = (clone $query)
            ->where('delivery_status', 'Delivered')
            ->count();

        /*
    |--------------------------------------------------------------------------
    | Payment Received
    |--------------------------------------------------------------------------
    */

        $paymentReceived = (clone $query)
            ->where('recivedpaysts', 1)
            ->sum('receivedcodamt');

        /*
    |--------------------------------------------------------------------------
    | Payment Pending
    |--------------------------------------------------------------------------
    */

        $paymentPending = (clone $query)
            ->where('delivery_status', 'Delivered')
            ->where(function ($q) {
                $q->whereNull('recivedpaysts')
                    ->orWhere('recivedpaysts', 0);
            })
            ->count();

        /*
    |--------------------------------------------------------------------------
    | Total RTO
    |--------------------------------------------------------------------------
    |
    | Matches:
    | RTO-intrasit
    | RTO Received
    | RTO anything
    |
    */

        $totalRTO = (clone $query)
            ->where('delivery_status', 'LIKE', 'RTO%')
            ->count();

        /*
    |--------------------------------------------------------------------------
    | RTO Received
    |--------------------------------------------------------------------------
    */

        $rtoReceived = (clone $query)
            ->where('delivery_status', 'RTO Received')
            ->where('rtorecivedsts', 1)
            ->count();

        /*
    |--------------------------------------------------------------------------
    | RTO Pending
    |--------------------------------------------------------------------------
    */

        $rtoPending = (clone $query)
            ->where('delivery_status', 'RTO Received')
            ->where(function ($q) {
                $q->whereNull('rtorecivedsts')
                    ->orWhere('rtorecivedsts', 0);
            })
            ->count();

        /*
    |--------------------------------------------------------------------------
    | In Transit
    |--------------------------------------------------------------------------
    |
    | These are the statuses from your India Post Excel
    | which are considered active/in-transit statuses.
    |
    */

        $inTransit = (clone $query)
            ->whereIn('delivery_status', [
                'RTO-intrasit',
                'Customer - Intrasit',
                'Out for Delivery',
                'On Hold'
            ])
            ->count();

        /*
    |--------------------------------------------------------------------------
    | Last Delivery Update
    |--------------------------------------------------------------------------
    */

        $lastDeliveryUpdate = Order::whereNotNull('delivery_date')
            ->max('delivery_date');

        /*
    |--------------------------------------------------------------------------
    | Last Payment Update
    |--------------------------------------------------------------------------
    */

        $lastPaymentUpdate = Order::where('recivedpaysts', 1)
            ->max('updated_at');

        return view(
            'delivery.index',
            compact(
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
            )
        );
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

        if (empty($rows) || empty($rows[0])) {
            return back()->with('error', 'Excel file is empty or invalid.');
        }

        $updated = 0;
        $notFound = 0;
        $skipped = 0;

        foreach ($rows[0] as $key => $row) {

            // Skip Excel Header
            if ($key === 0) {
                continue;
            }

            /*
        |--------------------------------------------------------------------------
        | Excel Columns
        |--------------------------------------------------------------------------
        |
        | 0 = Sr. No.
        | 1 = Article Number
        | 2 = Article Type
        | 3 = Booked At
        | 4 = Booked On
        | 5 = Destination
        | 6 = Status
        | 7 = Last Event
        |
        */

            $trackingNo = trim((string) ($row[1] ?? ''));
            $status     = trim((string) ($row[6] ?? ''));
            $lastEvent  = trim((string) ($row[7] ?? ''));

            // Skip if Article Number is empty
            if ($trackingNo === '') {
                $skipped++;
                continue;
            }

            // Skip if Status is empty
            if ($status === '') {
                $skipped++;
                continue;
            }

            /*
        |--------------------------------------------------------------------------
        | Find Order By Article Number / Barcode
        |--------------------------------------------------------------------------
        */

            $order = Order::where('barcode', $trackingNo)->first();

            if (!$order) {
                $notFound++;
                continue;
            }

            /*
        |--------------------------------------------------------------------------
        | IMPORTANT:
        | Save EXACT Excel Status
        |--------------------------------------------------------------------------
        */

            $order->delivery_status = $status;

            /*
        |--------------------------------------------------------------------------
        | Save Last Event / Remark
        |--------------------------------------------------------------------------
        */

            if ($lastEvent !== '') {
                $order->delivery_remark = $lastEvent;
            }

            /*
        |--------------------------------------------------------------------------
        | Extract Date From Last Event
        |--------------------------------------------------------------------------
        |
        | Example:
        | Item Delivered (Addressee) at Kahniwan SO on 21/08/2026 16:27:59
        |
        | Extracts:
        | 21/08/2026
        |
        */

            $eventDate = null;

            if (
                preg_match(
                    '/(\d{2}\/\d{2}\/\d{4})/',
                    $lastEvent,
                    $matches
                )
            ) {
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
        | Delivered Date
        |--------------------------------------------------------------------------
        */

            if (
                strcasecmp($status, 'Delivered') === 0 &&
                $eventDate
            ) {
                $order->delivery_date = $eventDate;
            }

            /*
        |--------------------------------------------------------------------------
        | RTO Date
        |--------------------------------------------------------------------------
        |
        | Any status beginning with RTO:
        |
        | RTO-intrasit
        | RTO Received
        |
        */

            if (
                stripos($status, 'RTO') === 0 &&
                $eventDate
            ) {
                $order->rtodate = $eventDate;
            }

            /*
        |--------------------------------------------------------------------------
        | In Transit Date
        |--------------------------------------------------------------------------
        |
        | Only set first time if currently empty.
        |
        */

            if (
                stripos($status, 'intransit') !== false ||
                stripos($status, 'in transit') !== false
            ) {
                if (
                    $eventDate &&
                    empty($order->intransitdate)
                ) {
                    $order->intransitdate = $eventDate;
                }
            }

            /*
        |--------------------------------------------------------------------------
        | Save Order
        |--------------------------------------------------------------------------
        */

            $order->save();

            $updated++;
        }

        return back()->with(
            'delivery_success',
            "{$updated} records updated successfully. {$notFound} tracking numbers not found. {$skipped} rows skipped."
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
