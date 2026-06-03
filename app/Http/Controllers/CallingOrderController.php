<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CallingOrder;
use Illuminate\Support\Facades\Auth;
use App\Models\Client;

class CallingOrderController extends Controller
{
    public function create()
    {
        $clients = Client::all();

        return view('calling.manual_order', compact('clients'));
    }

    public function store(Request $request)
    {
        $user = Auth::guard('calling_user')->user();


        $name = strtolower($user->name);

        $shortName =
            strtoupper(substr($name, 0, 1)) .
            strtolower(substr($name, -1));



        $date = now()->format('d-m-y');

        // Daily Serial Number
        $todayCount = CallingOrder::whereDate('created_at', today())->count() + 1;

        // Final Order ID
        // Example: Gn-15-5-26-1

        $customOrderId = $shortName . '-' . $date . '-' . $todayCount;

        CallingOrder::create([

            'client_id' => $request->client_id,

            'order_id' => $customOrderId,

            'order_date' => now(),

            'product_name' => $request->product_name,
            'quantity' => $request->quantity ?? 1,

            'customer_name' => $request->customer_name,
            'father_name' => $request->father_name,
            'age' => $request->age,

            'customer_phone' => $request->customer_phone,

            'shipping_address' => $request->address,
            'city' => $request->city,
            'state' => $request->state,
            'pincode' => $request->pincode,

            'payment_mode' => $request->payment_mode ?? 'cod',
            'amount' => $request->amount ?? 0,

            'status' => 'verified',
            'order_source' => 'whatsapp',

            'assigned_to' => $user->id,
        ]);

        return redirect()->back()->with('success', 'Order Added');
    }
    public function whatsappOrders(Request $request)
    {
        $userId = Auth::guard('calling_user')->id();

        $clients = CallingOrder::select('client_id', DB::raw('COUNT(*) as total'))
            ->where('assigned_to', $userId)
            ->where('order_source', 'whatsapp')
            ->groupBy('client_id')
            ->with('client')
            ->get();


        $query = CallingOrder::where('assigned_to', $userId)
            ->where('order_source', 'whatsapp');

        if ($request->client_id) {
            $query->where('client_id', $request->client_id);
        }

        $orders = $query->latest()->get();

        return view('calling.orders', compact('orders', 'clients'));
    }
}
