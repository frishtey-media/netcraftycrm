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
            'client_id' => 'required',
            'file'      => 'required|mimes:xls,xlsx',
        ]);

        $import = new WhatsAppOrdersImport($request->client_id);

        Excel::import($import, $request->file('file'));

        return back()->with([
            'success'  => 'Import completed',
            'imported' => $import->imported,
            'skipped' => $import->skipped,
            'errors'  => $import->errors,
        ]);
    }
}
