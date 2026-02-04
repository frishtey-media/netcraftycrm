<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;

class MoneyorderExportController extends Controller
{
    public function Moneyorder(Request $request)
    {
        $request->validate([
            'ids' => 'required'
        ]);

        $orderIds = array_filter(explode(',', $request->ids));

        if (empty($orderIds)) {
            return back()->with('error', 'No orders selected');
        }

        $orders = Order::whereIn('id', $orderIds)->get();

        //dd($orders);

        if ($orders->isEmpty()) {
            return back()->with('error', 'No orders found');
        }

        $pdf = Pdf::loadView(
            'moneyorder.combined',
            compact('orders')
        )->setPaper('A4', 'portrait');

        $fileName = 'moneyorder_' . now()->format('Y-m-d_H-i-s') . '.pdf';

        return $pdf->download($fileName);
    }
}
