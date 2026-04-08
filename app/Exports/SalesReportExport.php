<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;

class SalesReportExport implements FromArray
{
    protected $salesreport;

    public function __construct($salesreport)
    {
        $this->salesreport = $salesreport;
    }

    public function array(): array
    {
        $data = [];


        $data[] = [
            '#',
            'Date',
            'Invoice No',
            'Product',
            'Quantity',
            'Price (₹)',
            'Subtotal (₹)'
        ];

        $grandTotal = 0;

        foreach ($this->salesreport as $key => $item) {

            $grandTotal += $item->subtotal;

            $data[] = [
                $key + 1,
                $item->created_at->format('d-m-Y'),
                $item->sale->invoice_no ?? '-',
                $item->product->name ?? '-',
                $item->quantity,
                '₹ ' . number_format($item->price, 2),
                '₹ ' . number_format($item->subtotal, 2)
            ];
        }


        $data[] = [
            '',
            '',
            '',
            '',
            '',
            'Grand Total',
            '₹ ' . number_format($grandTotal, 2)
        ];

        return $data;
    }
}
