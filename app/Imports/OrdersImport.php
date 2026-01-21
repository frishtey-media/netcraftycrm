<?php

namespace App\Imports;

use App\Models\Order;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class OrdersImport implements ToModel, WithHeadingRow
{
    public $imported = 0;
    public $duplicates = [];

    public function model(array $row)
    {
        if (empty($row['order_id'])) {
            return null;
        }


        if (Order::where('order_id', $row['order_id'])->exists()) {
            $this->duplicates[] = $row['order_id'];
            return null;
        }


        $date = null;
        if (!empty($row['date'])) {
            if (is_numeric($row['date'])) {
                $date = Carbon::instance(
                    ExcelDate::excelToDateTimeObject($row['date'])
                )->format('Y-m-d');
            } else {
                $date = Carbon::createFromFormat('d-m-Y', $row['date'])->format('Y-m-d');
            }
        }

        $this->imported++;

        return new Order([
            'order_id'         => $row['order_id'],
            'date'             => $date,
            'barcode'          => $row['barcode'] ?? null,
            'payment_mode'     => $row['payment_mode'] ?? null,
            'amount'           => $row['amount'] ?? null,
            'customer_name'    => $row['customer_name'] ?? null,
            'customer_phone'   => $row['customer_phone'] ?? null,
            'shipping_address' => $row['shipping_address_line1'] ?? null,
            'city'             => $row['city'] ?? null,
            'state'            => $row['state'] ?? null,
            'pincode'          => $row['shipping_pincode'] ?? null,
            'product'          => $row['product'] ?? null,
            'quantity'         => $row['quantity'] ?? null,
            'weight'           => $row['weight_in_gm'] ?? null,
        ]);
    }
}
