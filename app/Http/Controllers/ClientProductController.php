<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\ClientProduct;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ClientProductController extends Controller
{
    public function index()
    {
        return view('client-products.index', [
            'clients' => Client::all(),
            'products' => ClientProduct::with('client')->latest()->get()
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'client_id' => 'required',
            'shopify_product_name' => [
                'required',
                Rule::unique('client_products')
                    ->where(function ($query) use ($request) {
                        return $query->where('client_id', $request->client_id);
                    }),
            ],
            'weight_per_unit' => 'required|numeric',
        ], [
            'shopify_product_name.unique' => 'This product already exists for the selected client.',
        ]);

        ClientProduct::create([
            'client_id' => $request->client_id,
            'shopify_product_name' => $request->shopify_product_name,
            'weight_per_unit' => $request->weight_per_unit,
        ]);

        return back()->with('success', 'Product weight saved successfully');
    }
}
