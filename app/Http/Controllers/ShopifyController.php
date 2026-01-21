<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Barcode;
use App\Models\ShopifyOrder;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\ShopifyOrdersImport;

class ShopifyController extends Controller
{
    public function importPage()
    {
        return view('shopify.import', [
            'clients' => Client::all(),
            'orders'  => ShopifyOrder::latest()->get(),
        ]);
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
        $orders = ShopifyOrder::whereNull('barcode')->get();

        foreach ($orders as $order) {
            $barcode = Barcode::where('is_used', 0)->first();

            if ($barcode) {
                $order->update([
                    'barcode' => $barcode->barcode
                ]);

                $barcode->update(['is_used' => 1]);
            }
        }

        return back()->with('success', 'Barcodes assigned');
    }
}
