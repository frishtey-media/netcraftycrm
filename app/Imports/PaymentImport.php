<?php

namespace App\Imports;

use App\Models\Order;
use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Support\Collection;

class PaymentImport implements ToCollection
{
    public function collection(Collection $rows)
    {

        $rows->shift();

        foreach ($rows as $row) {

            $trackingNo = trim($row[0]);

            if (!$trackingNo) {
                continue;
            }

            Order::where('barcode', $trackingNo)
                ->update([
                    'recivedpaysts' => 1
                ]);
        }
    }
}
