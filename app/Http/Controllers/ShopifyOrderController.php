<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Imports\WhatsAppOrdersImport;
use Maatwebsite\Excel\Facades\Excel;

class ShopifyOrderController extends Controller
{
    public function whatsappExcelImport(Request $request)
    {
        $request->validate([
            'client_id'   => 'required|exists:clients,id',
            'import_date' => 'required|date',
            'file'        => 'required|mimes:xls,xlsx',
        ]);

        $import = new WhatsAppOrdersImport(
            $request->client_id,
            $request->import_date
        );

        Excel::import($import, $request->file('file'));

        return back()->with([
            'success' => "Imported: {$import->imported}, Skipped: {$import->skipped}",
            'errors'  => $import->errors,
        ]);
    }
}
