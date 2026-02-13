<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\AmazonToTallyExport;

class convertAmazonToTally extends Controller
{

    public function amazonToTally(Request $request)
    {
        $request->validate([
            'excelfile' => 'required|file|mimes:xlsx,csv'
        ]);

        return Excel::download(
            new AmazonToTallyExport($request->file('excelfile')),
            'tally_import.xlsx'
        );
    }
}
