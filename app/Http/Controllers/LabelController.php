<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ShopifyOrder;
use App\Models\order;

use App\Models\LabelSender;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Barcode;

class LabelController extends Controller
{
    public function export(Request $request)
    {
        $request->validate([
            'sender_id' => 'required|exists:label_senders,id',
        ]);
        $sender = LabelSender::findOrFail($request->sender_id);
        $orders = ShopifyOrder::whereNotNull('barcode')->get();
        if ($orders->isEmpty()) {
            return back()->with('error', 'No orders found for label generation.');
        }
        $pdf = Pdf::loadView('labels.pdf', compact('orders', 'sender'))
            ->setPaper([0, 0, 288, 432], 'portrait');
        $orderIds = $orders->pluck('id')->toArray();
        register_shutdown_function(function () use ($orderIds) {
            ShopifyOrder::whereIn('id', $orderIds)->delete();
        });
        $dateTime = now()->format('Y-m-d_H-i-s');
        $fileName = "shipping_labels_{$dateTime}.pdf";
        return $pdf->download($fileName);
    }

    public function exportSelected(Request $request)
    {
        $request->validate([
            'sender_id' => 'required|exists:label_senders,id',
            'ids'       => 'required'
        ]);

        $orderIds = array_map('intval', array_filter(explode(',', $request->ids)));

        if (empty($orderIds)) {
            return response()->json([
                'error' => 'No orders selected'
            ], 422);
        }

        $sender = LabelSender::findOrFail($request->sender_id);


        $orders = order::whereIn('id', $orderIds)->get();

        if ($orders->isEmpty()) {
            return response()->json([
                'error' => 'No orders found'
            ], 422);
        }


        $unusedBarcodes = Barcode::where('is_used', 0)->take($orders->count())->get();

        if ($unusedBarcodes->count() < $orders->count()) {
            return response()->json([
                'error' => 'Not enough unused barcodes'
            ], 422);
        }

        foreach ($orders as $index => $order) {
            $barcode = $unusedBarcodes[$index];
            $order->barcode = $barcode->barcode;
            $order->barcode_id = $barcode->id;
        }


        $pdf = Pdf::loadView('labels.pdf', [
            'orders' => $orders,
            'sender' => $sender
        ])->setPaper([0, 0, 288, 432], 'portrait');

        Barcode::whereIn('id', $orders->pluck('barcode_id'))->update(['is_used' => 1]);

        $fileName = 'shipping_labels_' . now()->format('Y-m-d_H-i-s') . '.pdf';
        return $pdf->download($fileName);
    }
}
