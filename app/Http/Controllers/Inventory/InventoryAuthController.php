<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InventoryAuthController extends Controller
{
    public function showLogin()
    {
        return view('inventory.auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if (Auth::guard('inventory')->attempt([
            'email' => $request->email,
            'password' => $request->password
        ])) {

            $request->session()->regenerate();

            return redirect()->route('inventory.dashboard');
        }

        return back()->with('error', 'Invalid Credentials');
    }


    public function logout()
    {
        Auth::guard('inventory')->logout();
        return redirect()->route('inventory.login');
    }
}
