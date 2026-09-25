<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;
use App\Imports\PaymentImport;
use App\Models\Client;
use App\Models\Payment;
use App\Exports\DeliveryReportExport;
use Illuminate\Support\Facades\Log;

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
            return back()->with(
                'error',
                'Excel file is empty or invalid.'
            );
        }

        $updated = 0;
        $notFound = 0;
        $skipped = 0;
        $paymentMatched = 0;

        $byteSpeedSent = 0;
        $aiSencySent = 0;
        $integrationFailed = 0;

        foreach ($rows[0] as $key => $row) {

            /*
        |--------------------------------------------------------------------------
        | SKIP HEADER
        |--------------------------------------------------------------------------
        */

            if ($key === 0) {
                continue;
            }

            /*
        |--------------------------------------------------------------------------
        | INDIA POST EXCEL COLUMNS
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
        |--------------------------------------------------------------------------
        */

            $trackingNo = trim((string) ($row[1] ?? ''));
            $status     = trim((string) ($row[6] ?? ''));
            $lastEvent  = trim((string) ($row[7] ?? ''));

            /*
        |--------------------------------------------------------------------------
        | VALIDATION
        |--------------------------------------------------------------------------
        */

            if ($trackingNo === '') {
                $skipped++;
                continue;
            }

            if ($status === '') {
                $skipped++;
                continue;
            }

            /*
        |--------------------------------------------------------------------------
        | NORMALIZE TRACKING NUMBER
        |--------------------------------------------------------------------------
        */

            $trackingNo = strtoupper(
                preg_replace('/\s+/', '', $trackingNo)
            );

            /*
        |--------------------------------------------------------------------------
        | FIND ORDER BY BARCODE
        |--------------------------------------------------------------------------
        */

            $order = Order::whereRaw(
                'TRIM(UPPER(barcode)) = ?',
                [$trackingNo]
            )
                ->latest('id')
                ->first();

            if (!$order) {
                $notFound++;
                continue;
            }

            /*
        |--------------------------------------------------------------------------
        | OLD STATUS
        |--------------------------------------------------------------------------
        */

            $oldStatus = trim(
                (string) $order->delivery_status
            );

            /*
        |--------------------------------------------------------------------------
        | UPDATE DELIVERY STATUS
        |--------------------------------------------------------------------------
        */

            $order->delivery_status = $status;

            /*
        |--------------------------------------------------------------------------
        | SAVE LAST EVENT
        |--------------------------------------------------------------------------
        */

            if ($lastEvent !== '') {
                $order->delivery_remark = $lastEvent;
            }

            /*
        |--------------------------------------------------------------------------
        | EXTRACT EVENT DATE
        |--------------------------------------------------------------------------
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
        | DELIVERED DATE
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
        | RTO DATE
        |--------------------------------------------------------------------------
        */

            if (
                stripos($status, 'RTO') === 0 &&
                $eventDate
            ) {
                $order->rtodate = $eventDate;
            }

            /*
        |--------------------------------------------------------------------------
        | IN TRANSIT DATE
        |--------------------------------------------------------------------------
        */

            $statusLower = strtolower(
                trim($status)
            );

            if (
                str_contains($statusLower, 'intrasit') ||
                str_contains($statusLower, 'in transit')
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
        | PAYMENT RECONCILIATION
        |--------------------------------------------------------------------------
        */

            if (
                strcasecmp($status, 'Delivered') === 0
            ) {

                $payment = Payment::whereRaw(
                    'TRIM(UPPER(article_number)) = ?',
                    [$trackingNo]
                )
                    ->latest('id')
                    ->first();

                if ($payment) {

                    $order->recivedpaysts = 1;

                    $order->receivedcodamt =
                        $payment->cod_value ?? 0;

                    if (!empty($payment->bill_date)) {

                        $order->pay_bill_date =
                            $payment->bill_date;
                    }

                    $payment->order_id = $order->id;

                    if ($eventDate) {
                        $payment->delivered_date =
                            $eventDate;
                    }

                    $payment->save();

                    $paymentMatched++;
                } else {

                    $order->recivedpaysts = 0;
                }
            }

            /*
        |--------------------------------------------------------------------------
        | SAVE ORDER
        |--------------------------------------------------------------------------
        */

            $order->save();

            /*
        |--------------------------------------------------------------------------
        | MAP INDIA POST STATUS
        |--------------------------------------------------------------------------
        |
        | Customer - Intrasit
        | Customer - In Transit
        | Intrasit
        | In Transit
        |       ↓
        | in_transit
        |
        | Out for Delivery
        |       ↓
        | out_for_delivery
        |
        | On Hold / Hold
        |       ↓
        | on_hold
        |
        | Delivered
        |       ↓
        | delivered
        |
        |--------------------------------------------------------------------------
        */

            $trackingStatus = match (true) {

                $statusLower === 'delivered'
                => 'delivered',

                str_contains(
                    $statusLower,
                    'customer - intrasit'
                ),
                str_contains(
                    $statusLower,
                    'customer - in transit'
                ),
                str_contains(
                    $statusLower,
                    'intrasit'
                ),
                str_contains(
                    $statusLower,
                    'in transit'
                )
                => 'in_transit',

                $statusLower === 'out for delivery'
                => 'out_for_delivery',

                $statusLower === 'on hold',
                $statusLower === 'hold'
                => 'on_hold',

                default => null,
            };

            /*
        |--------------------------------------------------------------------------
        | SEND TRACKING MESSAGE ONLY WHEN STATUS CHANGED
        |--------------------------------------------------------------------------
        */

            if (
                $trackingStatus &&
                strtolower($oldStatus) !== strtolower($status)
            ) {

                try {

                    /*
                |--------------------------------------------------------------------------
                | CLIENT 5 = BYTESPEED
                |--------------------------------------------------------------------------
                */

                    if (
                        (int) $order->client_id === 5
                    ) {

                        $sent = app(
                            \App\Services\ByteSpeedService::class
                        )->pushStatus(
                            $order,
                            $trackingStatus
                        );

                        if ($sent) {

                            $byteSpeedSent++;

                            Log::info(
                                'BYTESPEED DELIVERY MESSAGE SENT',
                                [
                                    'order_id' => $order->order_id,
                                    'client_id' => $order->client_id,
                                    'barcode' => $order->barcode,
                                    'status' => $trackingStatus,
                                ]
                            );
                        } else {

                            $integrationFailed++;
                        }
                    }

                    /*
                |--------------------------------------------------------------------------
                | CLIENT 2 = AI SENCY
                |--------------------------------------------------------------------------
                */ elseif (
                        (int) $order->client_id === 2
                    ) {

                        $sent = app(
                            \App\Services\AiSencyService::class
                        )->pushStatus(
                            $order,
                            $trackingStatus
                        );

                        if ($sent) {

                            $aiSencySent++;

                            Log::info(
                                'AI SENCY DELIVERY MESSAGE SENT',
                                [
                                    'order_id' => $order->order_id,
                                    'client_id' => $order->client_id,
                                    'barcode' => $order->barcode,
                                    'status' => $trackingStatus,
                                ]
                            );
                        } else {

                            $integrationFailed++;
                        }
                    }
                } catch (\Throwable $e) {

                    $integrationFailed++;

                    Log::error(
                        'DELIVERY MESSAGE INTEGRATION FAILED',
                        [
                            'order_id' => $order->order_id,
                            'client_id' => $order->client_id,
                            'status' => $trackingStatus,
                            'error' => $e->getMessage(),
                            'file' => $e->getFile(),
                            'line' => $e->getLine(),
                        ]
                    );
                }
            }

            /*
        |--------------------------------------------------------------------------
        | UPDATED COUNT
        |--------------------------------------------------------------------------
        */

            $updated++;
        }



        return back()->with(
            'delivery_success',

            "{$updated} records updated successfully. "
                . "{$paymentMatched} payment records matched. "
                . "{$byteSpeedSent} ByteSpeed messages sent. "
                . "{$aiSencySent} Ai Sency messages sent. "
                . "{$integrationFailed} integration failures. "
                . "{$notFound} tracking numbers not found. "
                . "{$skipped} rows skipped."
        );
    }
    public function paymentupload(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv'
        ]);

        Excel::import(
            new PaymentImport,
            $request->file('file')
        );

        return back()->with(
            'payment_success',
            'Payment uploaded and delivery-payment matching completed successfully.'
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
