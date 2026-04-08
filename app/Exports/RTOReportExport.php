<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;

class RTOReportExport implements FromArray
{
    protected $rtoReports;

    public function __construct($rtoReports)
    {
        $this->rtoReports = $rtoReports;
    }

    public function array(): array
    {
        $rows = [];

        $rows[] = [
            '#',
            'Order ID',
            'Tracking No',
            'Customer Name',
            'Phone',
            'Product',
            'Qty',
            'Amount',
            'Order Date'
        ];

        $sr = 1;
        $grandQty = 0;
        $grandAmount = 0;

        foreach ($this->rtoReports as $product => $items) {

            $rows[] = ['Product: ' . $product];

            $productQty = 0;
            $productAmount = 0;

            foreach ($items as $rto) {

                $rows[] = [
                    $sr++,
                    $rto->order_id,
                    $rto->tracking_no,
                    $rto->customer_name,
                    $rto->customer_phone,
                    $rto->product,
                    $rto->quantity,
                    '₹ ' . number_format($rto->amount, 2),
                    date('d-m-Y', strtotime($rto->order_date))
                ];

                $productQty += $rto->quantity;
                $productAmount += $rto->amount;
            }

            $rows[] = [
                '',
                '',
                '',
                '',
                '',
                'Product Total',
                $productQty,
                '₹ ' . number_format($productAmount, 2),
                ''
            ];

            $grandQty += $productQty;
            $grandAmount += $productAmount;
        }

        $rows[] = [
            '',
            '',
            '',
            '',
            '',
            'Grand Total',
            $grandQty,
            '₹ ' . number_format($grandAmount, 2),
            ''
        ];

        return $rows;
    }
}
