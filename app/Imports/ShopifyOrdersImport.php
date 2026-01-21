<?php

namespace App\Imports;

use App\Models\ShopifyOrder;
use App\Models\ClientProduct;
use App\Models\Barcode;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ShopifyOrdersImport implements ToCollection
{
    protected $clientId;
    protected $importedCount = 0;
    protected $availableBarcodes = 0;

    public function __construct($clientId)
    {
        $this->clientId = $clientId;
    }


    private function getClientFiveWeight(string $productName, int $quantity): ?int
    {
        $name = strtolower($productName);


        if (
            str_contains($name, 'hair oil') &&
            str_contains($name, 'shampoo')
        ) {
            return match ($quantity) {
                1 => 450,
                2 => 900,
                3 => 1350,
                4 => 1800,
                default => null,
            };
        }


        if (str_contains($name, 'hair oil')) {
            return match ($quantity) {
                1 => 300,
                2 => 500,
                3 => 700,
                4 => 800,
                default => null,
            };
        }

        return null;
    }


    public function collection(Collection $rows)
    {
        // Count unused barcodes once
        $this->availableBarcodes = Barcode::where('is_used', 0)->count();

        if ($this->availableBarcodes <= 0) {
            throw new \Exception('No unused barcode available.');
        }

        foreach ($rows as $index => $row) {

            // Skip header row
            if ($index === 0) {
                continue;
            }

            // Stop if barcodes exhausted
            if ($this->importedCount >= $this->availableBarcodes) {
                break;
            }

            DB::beginTransaction();

            try {

                /* ---------- Basic Order Data ---------- */
                $orderId     = ltrim(trim($row[0] ?? ''), '#');
                $quantity    = (int) ($row[16] ?? 0);
                $productName = trim($row[17] ?? '');

                if (!$orderId || !$productName || $quantity <= 0) {
                    throw new \Exception('Invalid order data');
                }

                /* ---------- Duplicate Order Check ---------- */
                if (
                    ShopifyOrder::where('client_id', $this->clientId)
                    ->where('order_id', $orderId)
                    ->exists()
                ) {
                    throw new \Exception("Duplicate order ID: {$orderId}");
                }

                /* ---------- Product ---------- */
                $product = ClientProduct::where('client_id', $this->clientId)
                    ->where('shopify_product_name', $productName)
                    ->first();

                /* ---------- Weight Calculation ---------- */
                if ($this->clientId == 5) {

                    $specialWeight = $this->getClientFiveWeight($productName, $quantity);

                    if (!$specialWeight) {
                        throw new \Exception(
                            "Weight rule not defined for {$productName} with qty {$quantity}"
                        );
                    }

                    $totalWeight   = $specialWeight;
                    $weightPerUnit = $specialWeight / $quantity;
                } else {

                    if (!$product || $product->weight_per_unit <= 0) {
                        throw new \Exception("Weight not defined for {$productName}");
                    }

                    $weightPerUnit = $product->weight_per_unit;
                    $totalWeight   = $weightPerUnit * $quantity;
                }

                /* ---------- Get Unused Barcode ---------- */
                $barcode = Barcode::where('is_used', 0)
                    ->lockForUpdate()
                    ->first();

                if (!$barcode) {
                    throw new \Exception('No unused barcode available');
                }

                /* ---------- Order Date ---------- */
                try {
                    $orderDate = Carbon::parse($row[15])->format('Y-m-d H:i:s');
                } catch (\Exception $e) {
                    $orderDate = now();
                }

                /* ---------- Phone Cleanup ---------- */
                $customerPhone = $row[33] ?? null;
                if ($customerPhone && str_starts_with($customerPhone, '91')) {
                    $customerPhone = substr($customerPhone, 2);
                }

                /* ---------- Create Order ---------- */
                ShopifyOrder::create([
                    'client_id'            => $this->clientId,
                    'order_id'             => $orderId,
                    'order_date'           => $orderDate,
                    'shopify_product_name' => $productName,
                    'quantity'             => $quantity,
                    'weight_per_unit'      => $weightPerUnit,
                    'total_weight'         => $totalWeight,
                    'barcode'              => $barcode->barcode,
                    'customer_name'        => $row[24] ?? null,
                    'customer_phone'       => $customerPhone,
                    'father_name'          => $row[28] ?? null,
                    'shipping_address'     => $row[25] ?? null,
                    'city'                 => $row[29] ?? null,
                    'state'                => $row[31] ?? null,
                    'pincode'              => $row[30] ?? null,
                    'payment_mode'         => $row[47] ?? 'COD',
                    'amount'               => $row[11] ?? 0,
                ]);

                /* ---------- Mark Barcode Used ---------- */
                $barcode->update(['is_used' => 1]);

                DB::commit();
                $this->importedCount++;
            } catch (\Exception $e) {
                DB::rollBack();
                throw new \Exception("Row {$index}: " . $e->getMessage());
            }
        }

        /* ---------- Flash Warning (No Exception) ---------- */
        if ($this->importedCount < ($rows->count() - 1)) {
            session()->flash(
                'import_warning',
                "Only {$this->importedCount} orders imported because only {$this->availableBarcodes} barcodes were available."
            );
        }
    }
}
