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
        /*
        |--------------------------------------------------------------------------
        | Remove Header
        |--------------------------------------------------------------------------
        */

        $rows->shift();

        foreach ($rows as $row) {

            /*
            |--------------------------------------------------------------------------
            | Payment Excel Columns
            |--------------------------------------------------------------------------
            |
            | 0  = Article Number
            | 1  = Article Count
            | 2  = COD Invoice Number
            | 3  = Delivered Date
            | 4  = COD Value
            | 5  = COD Commission
            | 6  = Office ID
            | 7  = Office Name
            | 8  = Customer ID
            | 9  = Customer Name
            | 10 = Bill Date
            | 11 = Contract ID
            | 12 = Contract Mode
            |
            */

            $article = trim(
                (string) ($row[0] ?? '')
            );

            if (!$article) {
                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | Normalize Article Number
            |--------------------------------------------------------------------------
            */

            $article = strtoupper(
                preg_replace('/\s+/', '', $article)
            );

            /*
            |--------------------------------------------------------------------------
            | Find Order
            |--------------------------------------------------------------------------
            */

            $order = Order::whereRaw(
                'TRIM(UPPER(barcode)) = ?',
                [$article]
            )
                ->latest('id')
                ->first();


            /*
            |--------------------------------------------------------------------------
            | Delivered Date
            |--------------------------------------------------------------------------
            */

            $deliveredDate = null;

            if (!empty($row[3])) {

                try {

                    $deliveredDate =
                        Carbon::createFromFormat(
                            'd-m-Y',
                            trim((string) $row[3])
                        )->format('Y-m-d');
                } catch (\Exception $e) {

                    try {

                        $deliveredDate =
                            Carbon::parse(
                                $row[3]
                            )->format('Y-m-d');
                    } catch (\Exception $e) {

                        $deliveredDate = null;
                    }
                }
            }


            /*
            |--------------------------------------------------------------------------
            | Bill Date
            |--------------------------------------------------------------------------
            */

            $billDate = null;

            if (!empty($row[10])) {

                try {

                    $billDate =
                        Carbon::createFromFormat(
                            'd-m-Y',
                            trim((string) $row[10])
                        )->format('Y-m-d');
                } catch (\Exception $e) {

                    try {

                        $billDate =
                            Carbon::parse(
                                $row[10]
                            )->format('Y-m-d');
                    } catch (\Exception $e) {

                        $billDate = null;
                    }
                }
            }


            /*
            |--------------------------------------------------------------------------
            | COD Amount
            |--------------------------------------------------------------------------
            */

            $codValue = $row[4] ?? 0;

            if (is_string($codValue)) {

                $codValue = str_replace(
                    [',', '₹', 'Rs.', 'Rs', 'INR'],
                    '',
                    $codValue
                );
            }

            $codValue = is_numeric($codValue)
                ? $codValue
                : 0;


            /*
            |--------------------------------------------------------------------------
            | Find Existing Payment
            |--------------------------------------------------------------------------
            |
            | DON'T SKIP DUPLICATE ARTICLE.
            |
            | Update existing payment record.
            |
            */

            $payment = Payment::whereRaw(
                'TRIM(UPPER(article_number)) = ?',
                [$article]
            )
                ->latest('id')
                ->first();


            /*
            |--------------------------------------------------------------------------
            | Payment Data
            |--------------------------------------------------------------------------
            */

            $paymentData = [

                'order_id' =>
                optional($order)->id,

                'article_number' =>
                $article,

                'article_count' =>
                $row[1] ?? 0,

                'cod_invoice_number' =>
                $row[2] ?? '',

                'delivered_date' =>
                $deliveredDate,

                'cod_value' =>
                $codValue,

                'cod_commission' =>
                $row[5] ?? 0,

                'office_id' =>
                $row[6] ?? '',

                'office_name' =>
                $row[7] ?? '',

                'customer_id' =>
                $row[8] ?? '',

                'customer_name' =>
                $row[9] ?? '',

                'bill_date' =>
                $billDate,

                'contract_id' =>
                $row[11] ?? '',

                'contract_mode' =>
                $row[12] ?? '',
            ];


            /*
            |--------------------------------------------------------------------------
            | Save / Update Payment
            |--------------------------------------------------------------------------
            */

            if ($payment) {

                $payment->update(
                    $paymentData
                );
            } else {

                $payment = Payment::create(
                    $paymentData
                );
            }


            /*
            |--------------------------------------------------------------------------
            | IMPORTANT:
            | Payment Received ≠ Delivery Automatically
            |--------------------------------------------------------------------------
            |
            | Payment is counted as received only when the
            | corresponding order is Delivered.
            |
            */

            if ($order) {

                $orderStatus = strtolower(
                    trim(
                        (string)
                        $order->delivery_status
                    )
                );


                if ($orderStatus === 'delivered') {

                    /*
                    |--------------------------------------------------------------------------
                    | Payment Received
                    |--------------------------------------------------------------------------
                    */

                    $order->update([

                        'recivedpaysts' =>
                        1,

                        'receivedcodamt' =>
                        $codValue,

                        'pay_bill_date' =>
                        $billDate,

                    ]);


                    /*
                    |--------------------------------------------------------------------------
                    | Use actual Order Delivery Date
                    |--------------------------------------------------------------------------
                    */

                    if (
                        !empty($order->delivery_date)
                    ) {

                        $payment->update([

                            'order_id' =>
                            $order->id,

                            'delivered_date' =>
                            $order->delivery_date,

                        ]);
                    }
                } else {


                    if (
                        (int)
                        $order->recivedpaysts !== 1
                    ) {

                        $order->update([
                            'recivedpaysts' => 0
                        ]);
                    }
                }
            }
        }
    }
}
