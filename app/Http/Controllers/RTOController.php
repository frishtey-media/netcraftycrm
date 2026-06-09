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

class RTOController extends Controller
{
    public function index(Request $request)
    {
        $ordersQuery = RtoReport::with([
            'callingOrder.staff'
        ]);

        // Date Filter
        if ($request->filled('from_date')) {
            $ordersQuery->whereDate('created_at', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $ordersQuery->whereDate('created_at', '<=', $request->to_date);
        }

        $orders = $ordersQuery->orderBy('created_at', 'desc')->get();
        $dateWiseCounts = DB::table('rto_reports')
            ->join('callingorder', 'rto_reports.order_id', '=', 'callingorder.order_id')
            ->join('calling_users', 'callingorder.assigned_to', '=', 'calling_users.id')

            ->when($request->filled('from_date'), function ($q) use ($request) {
                $q->whereDate('rto_reports.created_at', '>=', $request->from_date);
            })

            ->when($request->filled('to_date'), function ($q) use ($request) {
                $q->whereDate('rto_reports.created_at', '<=', $request->to_date);
            })

            ->selectRaw('
        DATE(rto_reports.created_at) as rto_date,
        calling_users.name as staff_name,
        COUNT(*) as total_rto
    ')
            ->groupBy(
                DB::raw('DATE(rto_reports.created_at)'),
                'calling_users.id',
                'calling_users.name'
            )
            ->orderByDesc('rto_date')
            ->get();
        // Staff Wise RTO Count
        $staffCounts = DB::table('rto_reports')
            ->join('callingorder', 'rto_reports.order_id', '=', 'callingorder.order_id')
            ->join('calling_users', 'callingorder.assigned_to', '=', 'calling_users.id')

            ->when($request->filled('from_date'), function ($q) use ($request) {
                $q->whereDate('rto_reports.created_at', '>=', $request->from_date);
            })

            ->when($request->filled('to_date'), function ($q) use ($request) {
                $q->whereDate('rto_reports.created_at', '<=', $request->to_date);
            })

            ->select(
                'calling_users.id',
                'calling_users.name as staff_name',
                DB::raw('COUNT(rto_reports.id) as total_rto')
            )
            ->groupBy('calling_users.id', 'calling_users.name')
            ->orderByDesc('total_rto')
            ->get();

        return view('rto.index', compact(
            'orders',
            'staffCounts',
            'dateWiseCounts'
        ));
    }

    public function search(Request $request)
    {
        $request->validate([
            'rtobarcodes' => 'required|mimes:xls,xlsx'
        ]);

        $barcodes = Excel::toArray(new RTOBarcodeImport, $request->file('rtobarcodes'));

        $barcodeList = collect($barcodes[0])
            ->flatten()
            ->filter()
            ->map(fn($barcode) => trim($barcode))
            ->toArray();

        // Update RTO Received Status
        Order::whereIn('barcode', $barcodeList)
            ->update([
                'rtorecivedsts' => 1
            ]);

        // Fetch Orders
        $orders = Order::whereIn('barcode', $barcodeList)
            ->orderBy('date', 'desc')
            ->get();

        Session::put('rto_export_ids', $orders->pluck('id')->toArray());

        return view('rto.index', compact('orders'))
            ->with('success', $orders->count() . ' records found and RTO status updated.');
    }

    public function export()
    {
        $orderIds = Session::get('rto_export_ids');

        if (empty($orderIds)) {
            return redirect()->back()->with('error', 'No RTO data to export');
        }

        $orders = Order::whereIn('id', $orderIds)->get();

        foreach ($orders as $order) {

            RtoReport::updateOrCreate(
                ['tracking_no' => $order->barcode],
                [
                    'order_id' => $order->order_id,
                    'tracking_no' => $order->barcode,
                    'customer_name' => $order->customer_name,
                    'customer_phone' => $order->customer_phone,
                    'shipping_address' => $order->shipping_address,
                    'payment_mode' => $order->payment_mode,
                    'amount' => $order->amount,
                    'product' => $order->product,
                    'quantity' => $order->quantity,
                    'weight' => $order->weight,
                    'order_date' => $order->date
                ]
            );
        }

        return Excel::download(
            new RTOOrdersExport($orderIds),
            'RTO_Orders_' . now()->format('d-m-Y') . '.xlsx'
        );
    }
}
