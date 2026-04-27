<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CallingOrder;
use Illuminate\Support\Facades\Auth;

class CallingOrderController extends Controller
{
    public function create()
    {
        return view('calling.manual_order');
    }

    public function store(Request $request)
    {
        CallingOrder::create([

            'client_id' => 1, // 🔥 change if needed

            'order_id' => rand(10000, 99999),

            'order_date' => now(),

            'product_name' => $request->product_name,
            'quantity' => $request->quantity ?? 1,

            'customer_name' => $request->customer_name,
            'customer_phone' => $request->customer_phone,

            'shipping_address' => $request->address,
            'city' => $request->city,
            'state' => $request->state,
            'pincode' => $request->pincode,

            'payment_mode' => $request->payment_mode ?? 'cod',
            'amount' => $request->amount ?? 0,

            'status' => 'pending',
            'order_source' => 'whatsapp',
            // 🔥 assign to current staff
            'assigned_to' => Auth::guard('calling_user')->id(),
        ]);

        return redirect()->back()->with('success', 'Order Added');
    }
    public function whatsappOrders(Request $request)
    {
        $userId = Auth::guard('calling_user')->id();

        // 🔥 CLIENT LIST (ONLY WHATSAPP ORDERS)
        $clients = CallingOrder::select('client_id', DB::raw('COUNT(*) as total'))
            ->where('assigned_to', $userId)
            ->where('order_source', 'whatsapp')
            ->groupBy('client_id')
            ->with('client')
            ->get();

        // 🔥 FILTER
        $query = CallingOrder::where('assigned_to', $userId)
            ->where('order_source', 'whatsapp');

        if ($request->client_id) {
            $query->where('client_id', $request->client_id);
        }

        $orders = $query->latest()->get();

        return view('calling.orders', compact('orders', 'clients'));
    }
}
