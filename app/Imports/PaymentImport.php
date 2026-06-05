<?php

namespace App\Imports;

use App\Models\Order;
use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Support\Collection;

class PaymentImport implements ToCollection
{
    public function collection(Collection $rows)
    {
        // Remove Header
        $rows->shift();

        foreach ($rows as $row) {

            $trackingNo = trim($row[0] ?? '');

            // COD Value Column
            $codAmount = $row[4] ?? 0;

            if (empty($trackingNo)) {
                continue;
            }

            Order::where('barcode', $trackingNo)
                ->update([
                    'recivedpaysts' => 1,
                    'receivedcodamt' => $codAmount
                ]);
        }
    }
}
