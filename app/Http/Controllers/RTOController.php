<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Imports\RTOBarcodeImport;
use App\Exports\RTOOrdersExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Session;
use App\Models\RtoReport;
use Illuminate\Support\Facades\DB;
use App\Models\CallingUser;
use App\Models\Client;
use Carbon\Carbon;
use App\Exports\RTODetailExport;


class RTOController extends Controller
{
    public function index(Request $request)
    {
        $clients = Client::orderBy('client_name')->get();

        $staffs = CallingUser::orderBy('name')->get();

        $staffCounts = DB::table('orders')

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
                'RTO'
            )

            ->when($request->filled('from_date'), function ($q) use ($request) {

                $q->whereDate(
                    'orders.created_at',
                    '>=',
                    $request->from_date
                );
            })

            ->when($request->filled('to_date'), function ($q) use ($request) {

                $q->whereDate(
                    'orders.created_at',
                    '<=',
                    $request->to_date
                );
            })

            ->when($request->filled('client_id'), function ($q) use ($request) {

                $q->where(
                    'orders.client_id',
                    $request->client_id
                );
            })

            ->when($request->filled('staff_id'), function ($q) use ($request) {

                if ($request->staff_id == 'other') {

                    $q->whereNull(
                        'callingorder.assigned_to'
                    );
                } else {

                    $q->where(
                        'callingorder.assigned_to',
                        $request->staff_id
                    );
                }
            })

            ->selectRaw("
    COALESCE(calling_users.name,'Other') as staff_name,
    COALESCE(clients.client_name,'Client Mapping Missing') as client_name,

    COUNT(DISTINCT orders.id) as total_rto,

    COUNT(DISTINCT CASE
        WHEN callingorder.order_source IS NULL
        THEN orders.id
    END) as web_rto,

    COUNT(DISTINCT CASE
        WHEN callingorder.order_source='whatsapp'
        THEN orders.id
    END) as whatsapp_rto,

    SUM(
        CASE
            WHEN orders.rtorecivedsts=1
            THEN 1
            ELSE 0
        END
    ) as rto_received
")

            ->groupByRaw("
            COALESCE(calling_users.name,'Other'),
            COALESCE(clients.client_name,'Client Mapping Missing')
        ")

            ->orderByDesc('total_rto')

            ->get();

        foreach ($staffCounts as $row) {

            $row->pending_rto =
                $row->total_rto - $row->rto_received;
        }

        $grandTotal = $staffCounts->sum('total_rto');
        $grandWeb = $staffCounts->sum('web_rto');

        $grandWhatsapp = $staffCounts->sum('whatsapp_rto');

        $grandReceived = $staffCounts->sum('rto_received');

        $grandPending = $staffCounts->sum('pending_rto');
        $grandReceived = $staffCounts->sum('rto_received');

        $grandPending = $staffCounts->sum('pending_rto');

        $orders = [];

        return view(
            'rto.index',
            compact(

                'staffCounts',

                'grandTotal',

                'grandWeb',

                'grandWhatsapp',

                'grandReceived',

                'grandPending',

                'clients',

                'staffs',

                'orders'

            )
        );
    }
    public function details(Request $request)
    {
        $query = Order::query()
            ->leftJoin('callingorder', 'orders.order_id', '=', 'callingorder.order_id')
            ->leftJoin('calling_users', 'callingorder.assigned_to', '=', 'calling_users.id')
            ->leftJoin('clients', 'orders.client_id', '=', 'clients.id')
            ->where('orders.delivery_status', 'RTO');

        //Client Filter

        if ($request->client_id) {
            $query->where('orders.client_id', $request->client_id);
        }

        //Staff

        if ($request->staff_id) {

            if ($request->staff_id == "other") {
                $query->whereNull('callingorder.assigned_to');
            } else {
                $query->where('callingorder.assigned_to', $request->staff_id);
            }
        }

        //Date

        if ($request->from_date) {
            $query->whereDate('orders.created_at', '>=', $request->from_date);
        }

        if ($request->to_date) {
            $query->whereDate('orders.created_at', '<=', $request->to_date);
        }

        switch ($request->status) {

            case 'received':

                $query->where('orders.rtorecivedsts', 1);

                break;

            case 'pending':

                $query->where('orders.rtorecivedsts', 0);

                break;

            case 'web':

                $query->whereNull('callingorder.order_source');

                break;

            case 'whatsapp':

                $query->where('callingorder.order_source', 'whatsapp');

                break;
            case 'total':
            default:

                break;
        }
        $headings = [
            'total' => 'Total RTO Orders',
            'web' => 'Web RTO Orders',
            'whatsapp' => 'WhatsApp RTO Orders',
            'received' => 'RTO Received Orders',
            'pending' => 'Pending RTO Orders',
        ];

        $heading = $headings[$request->status] ?? 'RTO Orders';
        $orders = $query
            ->select(
                'orders.*',
                'clients.client_name',
                'calling_users.name as staff_name'
            )
            ->latest()
            ->paginate(100);

        return view('rto.received_details', compact('orders', 'heading'));
    }

    public function detailsExport(Request $request)
    {
        return Excel::download(
            new RTODetailExport($request),
            'RTO_' . $request->status . '_' . now()->format('d-m-Y') . '.xlsx'
        );
    }
    public function search(Request $request)
    {
        $request->validate([
            'rtobarcodes' => 'required|mimes:xls,xlsx'
        ]);

        $barcodes = Excel::toArray(
            new RTOBarcodeImport,
            $request->file('rtobarcodes')
        );

        $barcodeList = collect($barcodes[0])
            ->flatten()
            ->filter()
            ->map(fn($barcode) => trim($barcode))
            ->unique()
            ->values()
            ->toArray();

        $totalUploaded = count($barcodeList);

        // Already scanned barcodes
        $existingBarcodes = RtoReport::whereIn(
            'tracking_no',
            $barcodeList
        )->pluck('tracking_no')->toArray();

        $skippedBarcodes = $existingBarcodes;

        // New barcodes only
        $newBarcodes = array_diff(
            $barcodeList,
            $existingBarcodes
        );

        $skippedCount = count($existingBarcodes);

        // Update RTO Status
        Order::whereIn('barcode', $newBarcodes)
            ->update([
                'rtorecivedsts'  => 1,
                'rtoreciveddate' => Carbon::now(),
            ]);

        // Orders found
        $orders = Order::whereIn('barcode', $newBarcodes)
            ->orderBy('date', 'desc')
            ->get();
        // Found barcodes in orders table
        $foundBarcodes = $orders->pluck('barcode')->toArray();

        // Not Found barcodes
        $notFoundBarcodes = array_diff(
            $newBarcodes,
            $foundBarcodes
        );

        $notFoundCount = count($notFoundBarcodes);
        $foundCount = $orders->count();

        Session::put(
            'rto_export_ids',
            $orders->pluck('id')->toArray()
        );

        // Filters data
        $clients = Client::orderBy('client_name')->get();
        $staffs = CallingUser::orderBy('name')->get();

        $staffCounts = collect();
        $grandTotal = 0;

        // Success Message
        $message = "
<strong>Total Uploaded:</strong> {$totalUploaded}<br>
<strong>New RTO Found:</strong> {$foundCount}<br>
<strong>Already Scanned / Skipped:</strong> {$skippedCount}<br>
<strong>Not Found:</strong> {$notFoundCount}
";
        if ($skippedCount > 0) {

            $message .= "<strong>Skipped Barcodes:</strong><br>";

            foreach ($skippedBarcodes as $barcode) {
                $message .= $barcode . "<br>";
            }
        }
        //dd($message);
        session()->flash('success', $message);

        return view('rto.index', compact(
            'orders',
            'clients',
            'staffs',
            'staffCounts',
            'grandTotal',
            'skippedBarcodes',
            'notFoundBarcodes'
        ));
    }

    public function export()
    {
        $orderIds = Session::get('rto_export_ids');

        if (empty($orderIds)) {
            return redirect()->back()->with('error', 'No RTO data to export');
        }

        $orders = Order::whereIn('id', $orderIds)->get();

        foreach ($orders as $order) {

            if (
                !RtoReport::where(
                    'tracking_no',
                    $order->barcode
                )->exists()
            ) {

                RtoReport::create([
                    'order_id' => $order->order_id,
                    'tracking_no' => $order->barcode,
                    'customer_name' => $order->customer_name,
                    'customer_phone' => $order->customer_phone,
                    'father_name' => $order->father_name,
                    'shipping_address' => $order->shipping_address,
                    'payment_mode' => $order->payment_mode,
                    'amount' => $order->amount,
                    'product' => $order->product,
                    'quantity' => $order->quantity,
                    'weight' => $order->weight,
                    'order_date' => $order->date,
                    'is_exported' => '0',
                ]);
            }
        }

        return Excel::download(
            new RTOOrdersExport($orderIds),
            'RTO_Orders_' . now()->format('d-m-Y') . '.xlsx'
        );
    }
}
