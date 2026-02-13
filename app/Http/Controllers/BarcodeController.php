<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Barcode;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\BarcodeImport;

class BarcodeController extends Controller
{


    public function index()
    {
        $clients = Client::orderBy('client_name')->get();

        $barcodes = Barcode::with('client')
            ->orderBy('is_used', 'asc')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('barcodes.import', compact('clients', 'barcodes'));
    }



    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,csv',
            'client_id' => 'required|exists:clients,id',
        ]);

        $import = new BarcodeImport($request->client_id);

        Excel::import($import, $request->file('file'));

        return back()->with(
            'success',
            "Imported: {$import->imported}, Skipped: {$import->skipped}"
        );
    }
}
