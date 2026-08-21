<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Barcode;
use App\Models\ShopifyOrder;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\ShopifyOrdersImport;
use App\Models\LabelSender;
use App\Models\Order;

class ShopifyController extends Controller
{
    private function isClient()
    {
        return auth()->check()
            && auth()->user()->role === 'client';
    }

    private function clientId()
    {
        return auth()->user()->client_id;
    }
    public function importPage()
    {
        session()->forget('duplicate_orders');
        session()->forget('duplicate_barcodes');
        if ($this->isClient()) {

            $clients = Client::where(
                'id',
                $this->clientId()
            )->get();

            $orders = ShopifyOrder::where(
                'client_id',
                $this->clientId()
            )->latest()->get();

            $senders = LabelSender::where(
                'client_id',
                $this->clientId()
            )->orderBy('customer_name')
                ->get();
        } else {

            $clients = Client::orderBy(
                'client_name'
            )->get();

            $orders = ShopifyOrder::latest()->get();

            $senders = LabelSender::orderBy(
                'customer_name'
            )->get();
        }

        $showPostOffice = ShopifyOrder::when(
            $this->isClient(),
            fn($q) => $q->where('client_id', $this->clientId())
        )->exists();

        $showLabel = session('show_label', false);

        return view('shopify.import', compact(
            'clients',
            'orders',
            'senders',
            'showPostOffice',
            'showLabel'
        ));
    }


    public function importExcel(Request $request)
    {
        $request->validate([
            'client_id' => 'required',
            'file' => 'required|file'
        ]);

        try {
            Excel::import(
                new ShopifyOrdersImport($request->client_id),
                $request->file('file')
            );

            return redirect()->back()->with('success', 'Orders imported successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }


    public function orders()
    {
        return view('shopify.orders', [
            'orders' => ShopifyOrder::with('client')->latest()->get()
        ]);
    }

    public function assignBarcodes()
    {
        $orders = ShopifyOrder::whereNull('barcode')
            ->orderBy('id', 'asc')
            ->get();

        foreach ($orders as $order) {

            /*
        PAYMENT MODE MAPPING

        VPP -> vpp barcode
        COD -> cod barcode
        */

            $paymentMode = strtoupper(
                trim($order->payment_mode ?? 'COD')
            );

            $barcodeType = $paymentMode == 'VPP'
                ? 'vpp'
                : 'cod';

            /*
        FETCH UNUSED BARCODE
        */

            $barcode = Barcode::where('client_id', $order->client_id)
                ->where('barcode_type', $barcodeType)
                ->where('is_used', 0)
                ->orderBy('id', 'asc')
                ->first();

            /*
        IF BARCODE AVAILABLE
        */

            if ($barcode) {

                $order->update([
                    'barcode' => $barcode->barcode
                ]);

                $barcode->update([
                    'is_used' => 1
                ]);
            }
        }

        return back()->with(
            'success',
            'Barcodes assigned successfully'
        );
    }
}
