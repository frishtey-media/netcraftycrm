<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Client;
use Illuminate\Validation\Rule;

class ClientController extends Controller
{

    public function index()
    {
        $clients = Client::all();
        return view('clients.index', compact('clients'));
    }

    public function store(Request $request)
    {

        $request->validate([
            'client_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('clients', 'client_name'),
            ],
            'company_name' => ['nullable', 'string', 'max:255'],
            'mobile' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'pincode' => ['nullable', 'string', 'max:20'],
            'shopify_store_url' => ['nullable', 'string', 'max:255'],
        ], [
            'client_name.unique' => 'Client name already exists.',
        ]);


        Client::create([
            'client_name' => $request->client_name,
            'company_name' => $request->company_name,
            'mobile' => $request->mobile,
            'email' => $request->email,
            'address' => $request->address,
            'city' => $request->city,
            'state' => $request->state,
            'pincode' => $request->pincode,
            'shopify_store_url' => $request->shopify_store_url,
        ]);

        return redirect()->back()->with('success', 'Client added successfully');
    }
}
