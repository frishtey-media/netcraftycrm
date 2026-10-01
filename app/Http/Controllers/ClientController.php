<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\Client;
use App\Models\CallingUser;

class ClientController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Client List
    |--------------------------------------------------------------------------
    */

    public function index()
    {
        $clients = Client::latest()->get();

        return view('clients.index', compact('clients'));
    }


    /*
    |--------------------------------------------------------------------------
    | Store Client
    |--------------------------------------------------------------------------
    */

    public function store(Request $request)
    {
        $validated = $request->validate([

            // Basic Information
            'client_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('clients', 'client_name'),
            ],

            'company_name' => [
                'nullable',
                'string',
                'max:255',
            ],

            'mobile' => [
                'nullable',
                'string',
                'max:20',
            ],

            'email' => [
                'nullable',
                'email',
                'max:255',
            ],

            'address' => [
                'nullable',
                'string',
                'max:1000',
            ],

            'city' => [
                'nullable',
                'string',
                'max:255',
            ],

            'state' => [
                'nullable',
                'string',
                'max:255',
            ],

            'pincode' => [
                'nullable',
                'string',
                'max:20',
            ],


            // Shopify
            'shopify_store_url' => [
                'nullable',
                'string',
                'max:255',
            ],

            'shopify_client_id' => [
                'nullable',
                'string',
                'max:255',
            ],

            'shopify_client_secret' => [
                'nullable',
                'string',
                'max:1000',
            ],

            'shopify_access_token' => [
                'nullable',
                'string',
            ],


            // WhatsApp
            'phone_number_id' => [
                'nullable',
                'string',
                'max:255',
            ],

            'whatsapp_number' => [
                'nullable',
                'string',
                'max:50',
            ],

            'webhook_secret' => [
                'nullable',
                'string',
            ],

            'access_token' => [
                'nullable',
                'string',
            ],


            // Token
            'token_expires_at' => [
                'nullable',
                'date',
            ],

            'token_updated_at' => [
                'nullable',
                'date',
            ],


            // Shopify Status
            'shopify_status' => [
                'nullable',
                'string',
                'max:50',
            ],

            'shopify_last_error' => [
                'nullable',
                'string',
            ],

            'shopify_last_sync_at' => [
                'nullable',
                'date',
            ],


            // Warehouse
            'warehouse_sync' => [
                'nullable',
                'boolean',
            ],
        ]);


        /*
        |--------------------------------------------------------------------------
        | Create Client
        |--------------------------------------------------------------------------
        */

        Client::create([

            // Basic
            'client_name' => $validated['client_name'],
            'company_name' => $validated['company_name'] ?? null,
            'mobile' => $validated['mobile'] ?? null,
            'email' => $validated['email'] ?? null,

            'address' => $validated['address'] ?? null,
            'city' => $validated['city'] ?? null,
            'state' => $validated['state'] ?? null,
            'pincode' => $validated['pincode'] ?? null,


            // Shopify
            'shopify_store_url' => $validated['shopify_store_url'] ?? null,
            'shopify_client_id' => $validated['shopify_client_id'] ?? null,
            'shopify_client_secret' => $validated['shopify_client_secret'] ?? null,
            'shopify_access_token' => $validated['shopify_access_token'] ?? null,


            // WhatsApp
            'phone_number_id' => $validated['phone_number_id'] ?? null,
            'whatsapp_number' => $validated['whatsapp_number'] ?? null,
            'webhook_secret' => $validated['webhook_secret'] ?? null,
            'access_token' => $validated['access_token'] ?? null,


            // Token
            'token_expires_at' => $validated['token_expires_at'] ?? null,
            'token_updated_at' => $validated['token_updated_at'] ?? null,


            // Status
            'shopify_status' => $validated['shopify_status'] ?? 'pending',
            'shopify_last_error' => $validated['shopify_last_error'] ?? null,
            'shopify_last_sync_at' => $validated['shopify_last_sync_at'] ?? null,


            // Warehouse
            'warehouse_sync' => $request->boolean('warehouse_sync'),
        ]);


        return redirect()
            ->route('clients.index')
            ->with('success', 'Client added successfully.');
    }


    /*
    |--------------------------------------------------------------------------
    | Client Staff Form
    |--------------------------------------------------------------------------
    */

    public function clientStaffForm()
    {
        $clients = Client::all();
        $staffs = CallingUser::all();

        return view(
            'client_assign_staff',
            compact('clients', 'staffs')
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Save Client Staff
    |--------------------------------------------------------------------------
    */

    public function saveClientStaff(Request $request)
    {
        $request->validate([
            'client_id' => 'required|exists:clients,id',
            'staff_ids' => 'nullable|array',
            'staff_ids.*' => 'exists:calling_users,id',
        ]);


        $client = Client::findOrFail(
            $request->client_id
        );


        $client->staffs()->sync(
            $request->staff_ids ?? []
        );


        return back()->with(
            'success',
            'Mapping Saved Successfully'
        );
    }
}
