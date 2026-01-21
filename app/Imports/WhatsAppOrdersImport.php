<?php

namespace App\Imports;

use App\Models\ShopifyOrder;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Carbon\Carbon;

class WhatsAppOrdersImport implements ToCollection, WithHeadingRow
{
    protected $clientId;

    public function __construct($clientId)
    {
        $this->clientId = $clientId;
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {

            $quantity = $this->parseQuantity($row['quantity']);
            $totalWeight = $this->parseWeight($row['weight_in_gm']);

            ShopifyOrder::create([
                'client_id'             => $this->clientId,
                'order_id'              => $row['order_id'],
                'order_date'            => $this->parseDate($row['date']),
                'barcode'               => $row['barcode'],

                // ✅ PRODUCT
                'product_name'          => $row['product'], // optional if you want both
                'shopify_product_name'  => $row['product'],

                // ✅ WEIGHT LOGIC
                'quantity'              => $quantity,
                'weight'                => $quantity > 0 ? $totalWeight / $quantity : $totalWeight,
                'total_weight'          => $totalWeight,

                'payment_mode'          => $row['payment_mode'],
                'amount'                => $row['amount'],
                'customer_name'         => $row['customer_name'],
                'father_name'           => $row['father_name'],
                'customer_phone'        => $row['customer_phone'],
                'shipping_address'      => $row['shipping_address'],
                'city'                  => $row['city'],
                'state'                 => $row['state'],
                'pincode'               => $row['shipping_pincode'],
            ]);
        }
    }

    /* ---------------- HELPERS ---------------- */

    private function parseQuantity($value)
    {
        if (is_numeric($value)) {
            return (int) $value;
        }

        if (str_contains($value, '+')) {
            return collect(explode('+', $value))
                ->map(fn($v) => (int) trim($v))
                ->sum();
        }

        return 1;
    }

    private function parseWeight($value)
    {
        // "200gm" → 200
        return (int) preg_replace('/[^0-9]/', '', $value);
    }

    private function parseDate($value)
    {
        try {
            // Excel text date like: 12-Jan-26
            return Carbon::createFromFormat('d-M-y', $value)->format('Y-m-d');
        } catch (\Exception $e) {
            return now()->format('Y-m-d');
        }
    }
}
