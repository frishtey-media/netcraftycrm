<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Imports\RTOBarcodeImport;
use App\Exports\RTOOrdersExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Session;
use App\Models\RtoReport;

class RTOController extends Controller
{
    public function index()
    {
        $orders = [];
        return view('rto.index', compact('orders'));
    }

    public function search(Request $request)
    {
        $request->validate([
            'rtobarcodes' => 'required|mimes:xls,xlsx'
        ]);


        $barcodes = Excel::toArray(new RTOBarcodeImport, $request->file('rtobarcodes'));
        $barcodeList = $barcodes[0];


        $orders = Order::whereIn('barcode', $barcodeList)
            ->orderBy('date', 'desc')
            ->get();

        Session::put('rto_export_ids', $orders->pluck('id')->toArray());

        return view('rto.index', compact('orders'));
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
