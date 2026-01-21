<?php

namespace App\Http\Controllers;

use App\Models\Barcode;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\BarcodeImport;

class BarcodeController extends Controller
{


    public function index()
    {
        return view('barcodes.import', [
            'barcodes' => Barcode::orderBy('is_used', 'asc')
                ->orderBy('created_at', 'desc')
                ->get(),
        ]);
    }



    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file'
        ]);

        $import = new BarcodeImport();
        Excel::import($import, $request->file('file'));

        return back()->with(
            'success',
            "Import completed. Imported: {$import->imported}, Skipped (duplicates): {$import->skipped}"
        );
    }
}
