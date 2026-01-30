<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToArray;

class RTOBarcodeImport implements ToArray
{
    public function array(array $rows)
    {
        $barcodes = [];

        foreach ($rows as $index => $row) {
            if ($index === 0) continue;
            if (!empty($row[0])) {
                $barcodes[] = trim($row[0]);
            }
        }

        return $barcodes;
    }
}
