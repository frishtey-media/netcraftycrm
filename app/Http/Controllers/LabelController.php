<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ShopifyOrder;
use App\Models\LabelSender;
use Barryvdh\DomPDF\Facade\Pdf;

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

        return $pdf->download('shipping_labels.pdf');
    }
}
