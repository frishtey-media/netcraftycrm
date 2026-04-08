<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index()
    {
        return view('suppliers.index', [
            'suppliers' => Supplier::latest()->get()
        ]);
    }

    public function store(Request $request)
    {
        $request->validate(['name' => 'required']);
        Supplier::create($request->all());
        return back();
    }

    public function destroy(Supplier $supplier)
    {
        $supplier->delete();
        return back();
    }
}
