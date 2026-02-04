<?php

namespace App\Imports;

use App\Models\ShopifyOrder;
use App\Models\Barcode;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;


class WhatsAppOrdersImport implements ToCollection, WithHeadingRow
{
    protected $clientId;
    public $imported = 0;
    public $skipped  = 0;

    public function __construct($clientId)
    {
        $this->clientId = $clientId;
    }

    public function collection(Collection $rows)
    {
        DB::transaction(function () use ($rows) {


            $barcodes = Barcode::where('is_used', 0)
                ->limit($rows->count())
                ->lockForUpdate()
                ->get();

            foreach ($rows as $index => $row) {


                if (!isset($barcodes[$index])) {
                    $this->skipped++;
                    continue;
                }

                $barcode = $barcodes[$index];

                $quantity     = $this->parseQuantity($row['quantity']);
                $totalWeight  = $this->parseWeight($row['weight_in_gm']);
                // dd($this->parseDate($row['date']));
                ShopifyOrder::create([
                    'client_id'             => $this->clientId,
                    'order_id'              => $row['order_id'],
                    'order_date'            => $this->parseDate($row['date']),
                    'barcode'               => $barcode->barcode,

                    'product_name'          => $row['product'],
                    'shopify_product_name'  => $row['product'],

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


                $barcode->update(['is_used' => 1]);

                $this->imported++;
            }
        });
    }

    /* ---------------- HELPERS ---------------- */

    private function parseQuantity($value)
    {
        if (is_numeric($value)) return (int) $value;

        if (str_contains($value, '+')) {
            return collect(explode('+', $value))
                ->map(fn($v) => (int) trim($v))
                ->sum();
        }

        return 1;
    }

    private function parseWeight($value)
    {
        return (int) preg_replace('/[^0-9]/', '', $value);
    }

    private function parseDate($value)
    {
        try {
            return Carbon::createFromFormat('d-M-y', $value)->format('Y-m-d');
        } catch (\Exception $e) {
            return now()->format('Y-m-d');
        }
    }
}
