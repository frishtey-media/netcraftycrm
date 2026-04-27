<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CallingUser;
use App\Models\CallingOrder;
use Illuminate\Support\Facades\Hash;

class CallingUserController extends Controller
{
    public function index()
    {
        $users = CallingUser::all();
        return view('calling-users', compact('users'));
    }
    public function performance()
    {
        $staffs = CallingUser::withCount([
            'orders as total_orders',
            'orders as verified_orders' => function ($q) {
                $q->where('status', 'verified');
            },
            'orders as pending_orders' => function ($q) {
                $q->where('status', 'pending');
            },
            'orders as not_reachable_orders' => function ($q) {
                $q->where('status', 'not_reachable');
            }
        ])->get();

        return view('/performance', compact('staffs'));
    }
    public function store(Request $request)
    {
        CallingUser::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'status' => 1
        ]);

        return back()->with('success', 'Staff Created');
    }

    public function toggle($id)
    {
        $user = CallingUser::findOrFail($id);

        $user->status = $user->status == 1 ? 0 : 1;
        $user->save();

        return back()->with('success', 'Status Updated');
    }
    public function assignOrders(Request $request)
    {
        $clientId = $request->client_id;
        $assignData = $request->assign;

        // 👉 Get pending orders
        $orders = CallingOrder::where('client_id', $clientId)
            ->whereNull('assigned_to')
            ->where('status', 'pending')
            ->get();

        $orderIndex = 0;

        foreach ($assignData as $staffId => $qty) {

            if ($qty <= 0) continue;

            for ($i = 0; $i < $qty; $i++) {

                if (!isset($orders[$orderIndex])) break;

                $orders[$orderIndex]->assigned_to = $staffId;
                $orders[$orderIndex]->save();

                $orderIndex++;
            }
        }

        return back()->with('success', 'Orders Assigned Successfully');
    }
}
