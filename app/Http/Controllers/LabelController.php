<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ShopifyOrder;
use App\Models\Order;

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

        // dd($orders);
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

        $orders = Order::whereIn('id', $orderIds)->get();

        if ($orders->isEmpty()) {
            return response()->json([
                'error' => 'No orders found'
            ], 422);
        }

        DB::beginTransaction();

        try {

            // Detect if "Use Old Barcode" checkbox is selected
            $useOldBarcode = $request->has('use_old_barcode');

            foreach ($orders as $order) {

                // ✅ OPTION 1: Use Old Barcode
                if ($useOldBarcode) {

                    if (empty($order->barcode)) {
                        throw new \Exception("Order ID {$order->id} does not have an existing barcode.");
                    }

                    // Keep existing barcode
                    continue;
                }

                // ✅ OPTION 2: Generate / Assign New Barcode

                $barcode = Barcode::where('client_id', $order->client_id)
                    ->where('is_used', 0)
                    ->lockForUpdate()
                    ->first();

                if (!$barcode) {
                    throw new \Exception("No unused barcode available for client ID {$order->client_id}");
                }

                // Assign new barcode to order
                $order->barcode = $barcode->barcode;
                $order->save();

                // Mark barcode as used
                $barcode->update([
                    'is_used' => 1
                ]);
            }

            DB::commit();
        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'error' => $e->getMessage()
            ], 422);
        }

        // Generate PDF
        $pdf = Pdf::loadView('labels.pdf', [
            'orders' => $orders,
            'sender' => $sender
        ])->setPaper([0, 0, 288, 432], 'portrait');

        $fileName = 'shipping_labels_' . now()->format('Y-m-d_H-i-s') . '.pdf';

        return $pdf->download($fileName);
    }
}
