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
    public $errors   = [];

    protected $seenOrderIds = [];

    public function __construct($clientId)
    {
        $this->clientId = $clientId;
    }
    private function autoParseDate($value)
    {
        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    public function collection(Collection $rows)
    {
        DB::transaction(function () use ($rows) {

            $barcodes = Barcode::where('client_id', $this->clientId)
                ->where('is_used', 0)
                ->orderBy('id', 'asc')
                ->lockForUpdate()
                ->limit($rows->count())
                ->get()
                ->values();

            // dd($barcodes);
            foreach ($rows as $index => $row) {

                $rowNumber = $index + 2; // Excel row number

                /* ================= REQUIRED COLUMNS ================= */
                $required = [
                    'order_id',
                    'date',
                    'product',
                    'quantity',
                    'weight_in_gm',
                    'customer_name',
                    'customer_phone',
                    'shipping_address',
                    'city',
                    'state',
                    'shipping_pincode',
                ];

                foreach ($required as $field) {
                    if (!isset($row[$field]) || trim($row[$field]) === '') {
                        $this->addError($rowNumber, "Column '{$field}' is empty");
                        $this->skipped++;
                        continue 2;
                    }
                }

                /* ================= DUPLICATE ORDER ID (FILE) ================= */
                if (in_array($row['order_id'], $this->seenOrderIds)) {
                    $this->addError($rowNumber, "Duplicate order_id in Excel file");
                    $this->skipped++;
                    continue;
                }

                $this->seenOrderIds[] = $row['order_id'];

                /* ================= DUPLICATE ORDER ID (DB) ================= */
                if (ShopifyOrder::where('order_id', $row['order_id'])->exists()) {
                    $this->addError($rowNumber, "order_id already exists in database");
                    $this->skipped++;
                    continue;
                }

                /* ================= BARCODE ================= */
                if (!isset($barcodes[$index]) || $barcodes[$index]->client_id != $this->clientId) {

                    $this->addError($rowNumber, "Barcode not available");
                    $this->skipped++;
                    continue;
                }

                $barcode = $barcodes[$index];


                /* ================= DATE ================= */
                // $orderDate = $this->parseDate($row['date']);
                //if (!$orderDate) {
                //    $this->addError($rowNumber, "Invalid date format (expected d-M-y)");
                //    $this->skipped++;
                ////    continue;
                //}

                /* ================= QUANTITY ================= */
                $quantity = $this->parseQuantity($row['quantity']);
                if ($quantity <= 0) {
                    $this->addError($rowNumber, "Invalid quantity");
                    $this->skipped++;
                    continue;
                }

                /* ================= WEIGHT ================= */
                $totalWeight = $this->parseWeight($row['weight_in_gm']);
                if ($totalWeight <= 0) {
                    $this->addError($rowNumber, "Invalid weight");
                    $this->skipped++;
                    continue;
                }

                /* ================= INSERT ================= */
                ShopifyOrder::create([
                    'client_id' => $this->clientId,
                    'order_id'  => $row['order_id'],
                    'order_date'            => $this->parseDate($row['date']),


                    'barcode' => $barcode?->barcode,


                    'product_name'         => $row['product'],
                    'shopify_product_name' => $row['product'],

                    'quantity'      => $quantity,
                    'weight'        => $totalWeight / $quantity,
                    'total_weight'  => $totalWeight,

                    'payment_mode'    => $row['payment_mode'] ?? 'COD',
                    'amount'          => $row['amount'] ?? 0,
                    'customer_name'   => $row['customer_name'],
                    'father_name'     => $row['father_name'] ?? null,
                    'customer_phone'  => $row['customer_phone'],
                    'shipping_address' => $row['shipping_address'],
                    'city'            => $row['city'],
                    'state'           => $row['state'],
                    'pincode'         => $row['shipping_pincode'],
                ]);

                $barcode->update(['is_used' => 1]);

                $this->imported++;
            }
        });
    }

    /* ================= HELPERS ================= */

    private function addError($row, $message)
    {
        $this->errors[] = "Row {$row}: {$message}";
    }

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

        return 0;
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
