<?php

namespace App\Imports;

use App\Models\ShopifyImportOrder;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ShopifyExcelImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        return new ShopifyImportOrder([
            'order_id' => str_replace('#', '', $row['name']),
            'order_date' => Carbon::parse($row['created_at']),
            'payment_mode' => 'VPP',
            'amount' => $row['total'],
            'customer_name' => $row['shipping_name'],
            'customer_father_name' => $row['shipping_company'],
            'customer_phone' => preg_replace('/^91/', '', $row['shipping_phone']),
            'shipping_address' => $row['shipping_address1'],
            'city' => $row['shipping_city'],
            'state' => $row['shipping_province_name'],
            'pincode' => $row['shipping_zip'],
            'product' => $row['lineitem_name'],
            'quantity' => $row['lineitem_quantity'],
        ]);
    }
}
