<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with('client')
            ->where('role', '!=', 'super_admin')
            ->latest()
            ->get();

        $clients = Client::orderBy('client_name')->get();

        return view(
            'users.index',
            compact(
                'users',
                'clients'
            )
        );
    }

    public function create()
    {
        $clients = Client::orderBy('client_name')->get();

        return view('users.create', compact('clients'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'      => 'required',
            'email'     => 'required|email|unique:users,email',
            'password'  => 'required|min:6',
            'client_id' => 'required|exists:clients,id',
        ]);

        User::create([
            'name'      => $request->name,
            'email'     => $request->email,
            'password'  => Hash::make($request->password),
            'role'      => 'client',
            'client_id' => $request->client_id,
        ]);

        return back()->with('success', 'User Created');
    }

    public function edit($id)
    {
        $user = User::where('role', '!=', 'super_admin')
            ->findOrFail($id);

        $clients = Client::orderBy('client_name')->get();

        return view(
            'users.edit',
            compact('user', 'clients')
        );
    }

    public function update(Request $request, $id)
    {
        $user = User::where('role', '!=', 'super_admin')
            ->findOrFail($id);

        $request->validate([
            'name'      => 'required',
            'email'     => 'required|email|unique:users,email,' . $user->id,
            'client_id' => 'required|exists:clients,id',
        ]);

        $user->update([
            'name'      => $request->name,
            'email'     => $request->email,
            'client_id' => $request->client_id,
        ]);

        if ($request->filled('password')) {

            $user->update([
                'password' => Hash::make($request->password)
            ]);
        }

        return redirect()
            ->route('users.index')
            ->with('success', 'User updated successfully');
    }
}
