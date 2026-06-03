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
    private function isClient()
    {
        return auth()->check() && auth()->user()->role === 'client';
    }

    private function clientId()
    {
        return auth()->user()->client_id;
    }
    public function index()
    {
        if ($this->isClient()) {

            $clients = Client::where(
                'id',
                $this->clientId()
            )->get();

            $barcodes = Barcode::with('client')
                ->where('client_id', $this->clientId())
                ->orderBy('is_used', 'asc')
                ->orderBy('created_at', 'desc')
                ->get();
        } else {

            $clients = Client::orderBy('client_name')->get();

            $barcodes = Barcode::with('client')
                ->orderBy('is_used', 'asc')
                ->orderBy('created_at', 'desc')
                ->get();
        }

        return view(
            'barcodes.import',
            compact('clients', 'barcodes')
        );
    }
    public function indexbarcode()
    {
        $barcodes = [];

        for ($i = 0; $i < 11000; $i++) {

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
        ini_set('memory_limit', '2048M');
        ini_set('max_execution_time', 0);

        $barcodes = printbar_codes::latest()
            ->take(11000)
            ->get();

        $pdf = Pdf::loadView('barcodes.pdf', compact('barcodes'))
            ->setPaper([0, 0, 864, 1296], 'portrait');

        return $pdf->stream('barcodes.pdf');
    }


    public function import(Request $request)
    {
        $request->validate([
            'file'         => 'required|mimes:xlsx,csv',
            'client_id'    => 'required|exists:clients,id',
            'barcode_type' => 'required|in:VPP,COD',
        ]);

        // Client Security
        if (
            $this->isClient()
            && $request->client_id != $this->clientId()
        ) {
            abort(403);
        }

        $import = new BarcodeImport(
            $request->client_id,
            $request->barcode_type
        );

        Excel::import(
            $import,
            $request->file('file')
        );

        return back()->with(
            'success',
            "Imported: {$import->imported}, Skipped: {$import->skipped}"
        );
    }
}
