<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Warehouse;
use Illuminate\Http\Request;

class WarehouseController extends Controller
{
    public function index()
    {
        $warehouses = Warehouse::latest()->get();
        return view('inventory.warehouses.index', compact('warehouses'));
    }

    public function create()
    {
        return view('inventory.warehouses.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'location' => 'required'
        ]);

        Warehouse::create([
            'name' => $request->name,
            'location' => $request->location
        ]);

        return redirect()->route('warehouses.index')
            ->with('success', 'Warehouse Created Successfully');
    }

    public function edit(Warehouse $warehouse)
    {
        return view('inventory.warehouses.edit', compact('warehouse'));
    }

    public function update(Request $request, Warehouse $warehouse)
    {
        $request->validate([
            'name' => 'required',
            'location' => 'required',
        ]);

        $warehouse->update([
            'name' => $request->name,
            'location' => $request->location,
        ]);

        return redirect()->route('warehouses.index')
            ->with('success', 'Warehouse Updated Successfully');
    }


    public function destroy(Warehouse $warehouse)
    {
        $warehouse->delete();

        return redirect()->route('warehouses.index')
            ->with('success', 'Warehouse Deleted Successfully');
    }
}
