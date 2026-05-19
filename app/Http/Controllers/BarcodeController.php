<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Barcode;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\BarcodeImport;

use Illuminate\Support\Str;
use App\Models\printbar_codes;
use Barryvdh\DomPDF\Facade\Pdf;

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
    public function indexbarcode()
    {
        $barcodes = [];

        for ($i = 0; $i < 110; $i++) {

            do {

                $code = 'NET' . mt_rand(10000000, 99999999);
            } while (printbar_codes::where('barcode', $code)->exists());

            $barcode = printbar_codes::create([
                'barcode' => $code
            ]);

            $barcodes[] = $barcode;
        }

        return view('barcodes.barcodesview', compact('barcodes'));
    }

    // Download PDF
    public function download()
    {
        $barcodes = printbar_codes::latest()
            ->take(110)
            ->get();

        $pdf = Pdf::loadView('barcodes.pdf', compact('barcodes'))
            ->setPaper([0, 0, 864, 1296], 'portrait');

        return $pdf->stream('barcodes.pdf');
    }


    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,csv',
            'client_id' => 'required|exists:clients,id',
            'barcode_type' => 'required|in:VPP,COD',
        ]);

        $import = new BarcodeImport(
            $request->client_id,
            $request->input('barcode_type')
        );

        Excel::import($import, $request->file('file'));

        return back()->with(
            'success',
            "Imported: {$import->imported}, Skipped: {$import->skipped}"
        );
    }
}
