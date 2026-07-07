<?php

namespace App\Imports;

use App\Models\Order;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class PaymentImport implements ToCollection
{
    public function collection(Collection $rows)
    {
        $rows->shift();

        foreach ($rows as $row) {

            $article = trim($row[0] ?? '');

            if (!$article) {
                continue;
            }

            $order = Order::where(
                'barcode',
                $article
            )->first();

            // Update Orders Table
            if ($order) {

                $order->update([

                    'recivedpaysts' => 1,

                    'receivedcodamt' => $row[4] ?? 0,

                    'pay_bill_date' => !empty($row[10])
                        ? Carbon::createFromFormat('d-m-Y', $row[10])->format('Y-m-d')
                        : null

                ]);
            }

            // Save Payment History
            Payment::updateOrCreate(

                [
                    'article_number' => $article
                ],

                [

                    'order_id' => optional($order)->id,

                    'article_count' => $row[1] ?? 0,

                    'cod_invoice_number' => $row[2] ?? '',

                    'delivered_date' => !empty($row[3])
                        ? Carbon::createFromFormat('d-m-Y', $row[3])->format('Y-m-d')
                        : null,

                    'cod_value' => $row[4] ?? 0,

                    'cod_commission' => $row[5] ?? 0,

                    'office_id' => $row[6] ?? '',

                    'office_name' => $row[7] ?? '',

                    'customer_id' => $row[8] ?? '',

                    'customer_name' => $row[9] ?? '',

                    'bill_date' => !empty($row[10])
                        ? Carbon::createFromFormat('d-m-Y', $row[10])->format('Y-m-d')
                        : null,

                    'contract_id' => $row[11] ?? '',

                    'contract_mode' => $row[12] ?? ''

                ]

            );
        }
    }
}
