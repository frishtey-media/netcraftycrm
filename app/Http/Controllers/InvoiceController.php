<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Seller;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

class InvoiceController extends Controller
{

    public function InvoiceIndex()
    {
        $sellers = Seller::all();
        return view('Invoice.index', compact('sellers'));
    }


    public function storeSeller(Request $request)
    {
        $request->validate([
            'seller_name' => 'required',
            'gst_no' => 'required',
            'address' => 'required',
        ]);

        Seller::create($request->all());

        return back()->with('success', 'Seller saved successfully');
    }


    private function amountToWords($number)
    {
        $words = [
            0 => '',
            1 => 'One',
            2 => 'Two',
            3 => 'Three',
            4 => 'Four',
            5 => 'Five',
            6 => 'Six',
            7 => 'Seven',
            8 => 'Eight',
            9 => 'Nine',
            10 => 'Ten',
            11 => 'Eleven',
            12 => 'Twelve',
            13 => 'Thirteen',
            14 => 'Fourteen',
            15 => 'Fifteen',
            16 => 'Sixteen',
            17 => 'Seventeen',
            18 => 'Eighteen',
            19 => 'Nineteen',
            20 => 'Twenty',
            30 => 'Thirty',
            40 => 'Forty',
            50 => 'Fifty',
            60 => 'Sixty',
            70 => 'Seventy',
            80 => 'Eighty',
            90 => 'Ninety'
        ];

        $digits = ['', 'Hundred', 'Thousand', 'Lakh', 'Crore'];

        $number = round($number);
        $result = '';
        $i = 0;

        while ($number > 0) {
            $divider = ($i == 1) ? 10 : 100;
            $part = $number % $divider;
            $number = floor($number / $divider);

            if ($part) {
                if ($part < 21) {
                    $str = $words[$part];
                } else {
                    $str = $words[floor($part / 10) * 10] . ' ' . $words[$part % 10];
                }
                $result = $str . ' ' . $digits[$i] . ' ' . $result;
            }
            $i++;
        }

        return trim($result) . ' Only';
    }


    public function importExcel(Request $request)
    {
        $request->validate([
            'seller_id' => 'required',
            'excel' => 'required|mimes:xls,xlsx'
        ]);

        $seller = Seller::findOrFail($request->seller_id);

        $rows = Excel::toCollection(null, $request->file('excel'))[0];
        $rows = $rows->skip(1);

        $invoices = [];

        foreach ($rows as $row) {

            if (empty($row[1])) {
                continue;
            }

            $gross = (float) $row[5];
            $net   = round($gross / 1.05, 2);
            $gst   = round($gross - $net, 2);

            $invoices[] = [
                'invoice_no'   => $row[0],
                'order_id'     => $row[1],
                'date'         => $row[2],
                'amount'       => $gross,
                'net'          => $net,
                'gst'          => $gst,
                'amount_words' => $this->amountToWords($gross),
                'customer'     => $row[6],
                'phone'        => $row[7] ?: 'NA',
                'address'      => $row[8],
                'city'         => $row[9],
                'state'        => $row[10],
                'pincode'      => ltrim($row[11], "'"),
                'product'      => $row[12],
                'qty'          => $row[13] ?: 1,
                'weight'       => $row[14],
            ];
        }

        $pdf = Pdf::loadView(
            'Invoice.merged_pdf',
            compact('seller', 'invoices')
        )->setPaper('A4', 'landscape');

        return $pdf->download('all_invoices.pdf');
    }
}
