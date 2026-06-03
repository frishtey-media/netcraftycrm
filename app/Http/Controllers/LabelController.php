<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ShopifyOrder;
use App\Models\Order;
use App\Models\LabelSender;
use App\Models\Barcode;

use Barryvdh\DomPDF\Facade\Pdf;

use Illuminate\Support\Facades\DB;

class LabelController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | CLIENT HELPERS
    |--------------------------------------------------------------------------
    */

    private function isClient()
    {
        return auth()->check()
            && auth()->user()->role === 'client';
    }

    private function clientId()
    {
        return auth()->user()->client_id;
    }

    /*
    |--------------------------------------------------------------------------
    | EXPORT ALL LABELS
    |--------------------------------------------------------------------------
    */

    public function export(Request $request)
    {
        ini_set('memory_limit', '1024M');

        ini_set('max_execution_time', 300);

        $request->validate([
            'sender_id' => 'required|exists:label_senders,id',
        ]);

        $sender = LabelSender::findOrFail(
            $request->sender_id
        );

        /*
    |--------------------------------------------------------------------------
    | CLIENT SECURITY
    |--------------------------------------------------------------------------
    */

        if (
            $this->isClient()
            &&
            $sender->client_id != $this->clientId()
        ) {
            abort(403);
        }

        /*
    |--------------------------------------------------------------------------
    | GET ORDERS
    |--------------------------------------------------------------------------
    */

        $query = ShopifyOrder::whereNotNull(
            'barcode'
        );

        if ($this->isClient()) {

            $query->where(
                'client_id',
                $this->clientId()
            );
        }

        $orders = $query
            ->latest()
            ->get();

        if ($orders->isEmpty()) {

            return back()->with(
                'error',
                'No orders found for label generation.'
            );
        }

        try {

            /*
        |--------------------------------------------------------------------------
        | PDF GENERATE
        |--------------------------------------------------------------------------
        */

            $pdf = Pdf::loadView(
                'labels.pdf',
                [
                    'orders' => $orders,
                    'sender' => $sender
                ]
            )
                ->setPaper([0, 0, 288, 432], 'portrait')
                ->setOptions([
                    'isRemoteEnabled' => true,
                    'defaultFont' => 'sans-serif'
                ]);

            /*
        |--------------------------------------------------------------------------
        | PDF OUTPUT
        |--------------------------------------------------------------------------
        */

            $pdfContent = $pdf->output();

            /*
        |--------------------------------------------------------------------------
        | DELETE SHOPIFY ORDERS
        |--------------------------------------------------------------------------
        */

            ShopifyOrder::whereIn(
                'id',
                $orders->pluck('id')
            )->delete();

            /*
        |--------------------------------------------------------------------------
        | DOWNLOAD RESPONSE
        |--------------------------------------------------------------------------
        */

            $fileName =
                'shipping_labels_' .
                now()->format('Y-m-d_H-i-s') .
                '.pdf';

            return response(
                $pdfContent,
                200,
                [
                    'Content-Type' =>
                    'application/pdf',

                    'Content-Disposition' =>
                    'attachment; filename="' .
                        $fileName .
                        '"',
                ]
            );
        } catch (\Exception $e) {

            return back()->with(
                'error',
                $e->getMessage()
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | EXPORT SELECTED LABELS
    |--------------------------------------------------------------------------
    */

    public function exportSelected(Request $request)
    {
        ini_set('memory_limit', '1024M');
        ini_set('max_execution_time', 300);

        $request->validate([

            'sender_id' => 'required|exists:label_senders,id',

            'ids' => 'required'
        ]);

        /*
        |--------------------------------------------------------------------------
        | ORDER IDS
        |--------------------------------------------------------------------------
        */

        $orderIds = array_map(
            'intval',
            array_filter(
                explode(',', $request->ids)
            )
        );

        if (empty($orderIds)) {

            return response()->json([
                'error' => 'No orders selected'
            ], 422);
        }

        /*
        |--------------------------------------------------------------------------
        | SENDER
        |--------------------------------------------------------------------------
        */

        $sender = LabelSender::findOrFail(
            $request->sender_id
        );

        /*
        |--------------------------------------------------------------------------
        | CLIENT SECURITY
        |--------------------------------------------------------------------------
        */

        if (
            $this->isClient()
            &&
            $sender->client_id != $this->clientId()
        ) {
            abort(403);
        }

        /*
        |--------------------------------------------------------------------------
        | GET ORDERS
        |--------------------------------------------------------------------------
        */

        $query = Order::whereIn(
            'id',
            $orderIds
        );

        if ($this->isClient()) {

            $query->where(
                'client_id',
                $this->clientId()
            );
        }

        $orders = $query->get();

        if ($orders->isEmpty()) {

            return response()->json([
                'error' => 'No orders found'
            ], 422);
        }

        /*
        |--------------------------------------------------------------------------
        | ASSIGN BARCODES
        |--------------------------------------------------------------------------
        */

        DB::beginTransaction();

        try {

            $useOldBarcode = $request->has(
                'use_old_barcode'
            );

            foreach ($orders as $order) {

                /*
                |--------------------------------------------------------------------------
                | USE EXISTING BARCODE
                |--------------------------------------------------------------------------
                */

                if ($useOldBarcode) {

                    if (empty($order->barcode)) {

                        throw new \Exception(
                            "Order ID {$order->id} does not have an existing barcode."
                        );
                    }

                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | NEW BARCODE
                |--------------------------------------------------------------------------
                */

                $barcode = Barcode::where(
                    'client_id',
                    $order->client_id
                )
                    ->where(
                        'is_used',
                        0
                    )
                    ->whereNotIn(
                        'barcode',
                        Order::whereNotNull('barcode')
                            ->pluck('barcode')
                    )
                    ->lockForUpdate()
                    ->first();

                if (!$barcode) {

                    throw new \Exception(
                        "No unused barcode available for client ID {$order->client_id}"
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | ASSIGN BARCODE
                |--------------------------------------------------------------------------
                */

                $order->barcode = $barcode->barcode;

                $order->save();

                /*
                |--------------------------------------------------------------------------
                | MARK USED
                |--------------------------------------------------------------------------
                */

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

        /*
        |--------------------------------------------------------------------------
        | GENERATE PDF
        |--------------------------------------------------------------------------
        */

        try {

            $pdf = Pdf::loadView(
                'labels.pdf',
                [
                    'orders' => $orders,
                    'sender' => $sender
                ]
            )
                ->setPaper([0, 0, 288, 432], 'portrait')
                ->setOptions([
                    'isRemoteEnabled' => true,
                    'defaultFont' => 'sans-serif'
                ]);

            $fileName =
                'shipping_labels_' .
                now()->format('Y-m-d_H-i-s') .
                '.pdf';

            return response()->streamDownload(

                function () use ($pdf) {

                    echo $pdf->output();
                },

                $fileName
            );
        } catch (\Exception $e) {

            return response()->json([
                'error' => $e->getMessage()
            ], 422);
        }
    }
}
