<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\ShopifyExcelImport;
use App\Models\ShopifyImportOrder;
use App\Models\CourierOrder;

class ShopifyImportController extends Controller
{
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,csv'
        ]);

        Excel::import(new ShopifyExcelImport, $request->file('file'));

        return redirect('/labelgenerate');
    }

    public function popup()
    {
        $orders = ShopifyImportOrder::whereNull('barcode')->get();
        $finalOrders = CourierOrder::latest()->get();
        return view('labelgenrate', compact('orders', 'finalOrders'));
    }

    public function save(Request $request)
    {
        foreach ($request->barcode as $id => $barcode) {

            $temp = ShopifyImportOrder::find($id);

            CourierOrder::create([
                'order_id' => $temp->order_id,
                'order_date' => $temp->order_date,
                'barcode' => $barcode,
                'payment_mode' => $temp->payment_mode,
                'amount' => $temp->amount,
                'customer_name' => $temp->customer_name,
                'customer_father_name' => $temp->customer_father_name,
                'customer_phone' => $temp->customer_phone,
                'shipping_address' => $temp->shipping_address,
                'city' => $temp->city,
                'state' => $temp->state,
                'pincode' => $temp->pincode,
                'product' => $temp->product,
                'quantity' => $temp->quantity,
                'weight' => $request->weight[$id],
            ]);

            $temp->delete();
        }

        return redirect('/labelgenerate')
            ->with('success', 'Orders saved successfully');
    }
}
