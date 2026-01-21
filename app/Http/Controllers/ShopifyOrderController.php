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
            'file'      => 'required|mimes:xls,xlsx'
        ]);

        Excel::import(
            new WhatsAppOrdersImport($request->client_id),
            $request->file
        );

        return back()->with('success', 'Excel imported successfully');
    }
}
