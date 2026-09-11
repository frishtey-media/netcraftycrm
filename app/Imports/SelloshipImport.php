<?php

namespace App\Imports;

use App\Models\CallingOrder;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class SelloshipImport implements ToCollection, WithHeadingRow
{
    protected $clientId;

    public function __construct($clientId)
    {
        $this->clientId = $clientId;
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {

            // Selloship OrderID
            $orderId = $this->clean($row['orderid'] ?? null);

            if (!$orderId) {
                continue;
            }

            /*
             * Duplicate check
             *
             * Selloship records are stored as WhatsApp orders,
             * and identified using remarks = Selloship Import.
             */
            $exists = CallingOrder::where('client_id', $this->clientId)
                ->where('order_id', $orderId)
                ->where('order_source', 'whatsapp')
                ->where('remarks', 'Selloship Import')
                ->exists();

            if ($exists) {
                continue;
            }

            // Payment mode
            $paymentStatus = $this->clean(
                $row['paymentstatus'] ?? null
            );

            $paymentMode = 'COD';

            if (
                stripos((string) $paymentStatus, 'prepaid') !== false
            ) {
                $paymentMode = 'Prepaid';
            }

            // Order date
            $orderDate = null;

            if (!empty($row['orderdate'])) {
                try {
                    $orderDate = Carbon::parse($row['orderdate']);
                } catch (\Throwable $e) {
                    $orderDate = null;
                }
            }

            // Weight
            $weight = $this->clean(
                $row['weightperunit'] ?? null
            );

            // callingorder.weight is NOT NULL
            if ($weight === null || $weight === '') {
                $weight = 0;
            }

            // Quantity
            $quantity = (int) ($row['qty'] ?? 1);

            if ($quantity <= 0) {
                $quantity = 1;
            }

            // Create calling order
            CallingOrder::create([

                'client_id' => $this->clientId,

                'order_id' => $orderId,

                'order_date' => $orderDate,

                'product_name' => $this->clean(
                    $row['productname'] ?? null
                ),

                'quantity' => $quantity,

                'weight' => $weight,

                'customer_name' => $this->clean(
                    $row['customername'] ?? null
                ),

                'father_name' => null,

                'age' => null,

                'city' => $this->clean(
                    $row['city'] ?? null
                ),

                'state' => $this->clean(
                    $row['state'] ?? null
                ),

                'pincode' => $this->clean(
                    $row['pincode'] ?? null
                ),

                'customer_phone' => $this->clean(
                    $row['customermobileno'] ?? null
                ),

                'shipping_address' => $this->clean(
                    $row['fulladdress'] ?? null
                ),

                'payment_mode' => $paymentMode,

                'amount' => $this->amount(
                    $row['amount'] ?? 0
                ),

                'assigned_to' => null,

                'status' => 'pending',

                /*
                 * IMPORTANT:
                 * Selloship import is counted as WhatsApp
                 */
                'order_source' => 'whatsapp',

                /*
                 * Used to identify Selloship imported records
                 */
                'remarks' => 'Selloship Import',
            ]);
        }
    }

    /**
     * Clean Excel values
     */
    private function clean($value)
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        if (
            $value === '' ||
            strtoupper($value) === 'NULL'
        ) {
            return null;
        }

        return $value;
    }

    /**
     * Convert amount
     */
    private function amount($value)
    {
        if ($value === null || $value === '') {
            return 0;
        }

        return (float) str_replace(',', '', $value);
    }
}
