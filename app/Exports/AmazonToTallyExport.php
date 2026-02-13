<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;

class AmazonToTallyExport implements FromArray, WithHeadings
{
    protected $data = [];

    public function __construct($file)
    {
        $collection = Excel::toCollection(null, $file)[0];

        // Get header row
        $headers = $collection->first()->toArray();
        $headerMap = array_flip($headers);

        foreach ($collection as $index => $row) {

            if ($index == 0) continue; // skip header row

            $row = $row->toArray();

            // Safe column fetching
            $voucherNumber = $row[$headerMap['Invoice Number']] ?? '';
            $address       = $row[$headerMap['Ship To City']] ?? '';
            $pincode       = $row[$headerMap['Ship To Postal Code']] ?? '';
            $itemName      = $row[$headerMap['Item Description']] ?? '';
            $itemRate      = (float)($row[$headerMap['Invoice Amount']] ?? 0);
            $ratePer       = (float)($row[$headerMap['Tax Exclusive Gross']] ?? 0);
            $itemAmount    = (float)($row[$headerMap['Principal Amount']] ?? 0);

            $voucherDate = date('d-m-Y'); // or map Invoice Date if needed

            $this->data[] = [
                $voucherDate,
                'sale',
                $voucherNumber,
                $address,
                $pincode,
                'Cash',
                '',                 // Ledger Amount empty
                'Dr.',
                $itemName,
                1,
                $itemRate,
                $ratePer,
                $itemAmount,
                'Accounting Voucher'
            ];
        }
    }

    public function headings(): array
    {
        return [
            'Voucher Date',
            'Voucher Type Name',
            'Voucher Number',
            'Buyer/Supplier - Address',
            'Buyer/Supplier - Pincode',
            'Ledger Name',
            'Ledger Amount',
            'Ledger Amount Dr/Cr',
            'Item Name',
            'Billed Quantity',
            'Item Rate',
            'Item Rate per',
            'Item Amount',
            'Change Mode'
        ];
    }

    public function array(): array
    {
        return $this->data;
    }
}
