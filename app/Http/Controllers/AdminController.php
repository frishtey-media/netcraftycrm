<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\LabelSender;

class AdminController extends Controller
{

    public function dashboard()
    {
        $totalOrders = Order::count();

        return view('dashboard', compact('totalOrders'));
    }
    public function labelsenders()
    {
        $senders = LabelSender::latest()->get();
        return view('labelsenders', compact('senders'));
    }
    public function storeLabelSenders(Request $request)
    {
        $request->validate([
            'customer_name' => 'required|string|max:255|unique:label_senders,customer_name',
            'customer_phone' => 'required|string|max:255',
        ]);

        LabelSender::create([
            'customer_name' => $request->customer_name,
            'customer_phone' => $request->customer_phone,
        ]);

        return redirect()
            ->route('labelsenders')
            ->with('success', 'Label sender saved successfully');
    }
    public function labelgenrate()
    {
        $senders = LabelSender::latest()->get();
        return view('labelgenrate', compact('senders'));
    }
}
