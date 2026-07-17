<?php

namespace App\Imports;

use App\Models\ShopifyOrder;
use App\Models\ClientProduct;
use App\Models\Barcode;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ShopifyOrdersImport implements ToCollection, WithHeadingRow
{
    protected $clientId;
    protected $importedCount = 0;
    protected $availableBarcodes = 0;

    public function __construct($clientId)
    {
        $this->clientId = $clientId;
    }

    /* ================= WEIGHT LOGIC ================= */
    private function getClientFiveWeight(string $productName, int $quantity): int
    {
        $name = strtolower($productName);

        if (str_contains($name, 'hair oil') && str_contains($name, 'shampoo')) {
            return match (true) {
                $quantity == 1 => 450,
                $quantity == 2 => 900,
                $quantity == 3 => 1350,
                $quantity == 4 => 1800,
                default => 450 * $quantity,
            };
        }

        if (str_contains($name, 'hair oil')) {
            return match (true) {
                $quantity == 1 => 300,
                $quantity == 2 => 500,
                $quantity == 3 => 700,
                $quantity == 4 => 800,
                default => 300 * $quantity,
            };
        }

        return 300 * $quantity;
    }

    public function collection(Collection $rows)
    {
        // dd($rows->first()->toArray());
        $this->availableBarcodes = Barcode::where('client_id', $this->clientId)
            ->where('is_used', 0)
            ->count();

        if ($this->availableBarcodes <= 0) {
            throw new \Exception('No unused barcode available.');
        }

        foreach ($rows as $index => $row) {
            // dd($row->toArray());
            if ($this->importedCount >= $this->availableBarcodes) break;

            DB::beginTransaction();
            //  Log::info($row->keys()->toArray());
            try {

                /* ================= CORRECT COLUMN READ ================= */
                $orderId     = ltrim(trim($row['name'] ?? ''), '#');
                $quantityRaw = $row['lineitem_quantity'] ?? 1;
                $productName = trim($row['lineitem_name'] ?? '');
                $orderDateRaw = $row['created_at'] ?? null;

                // ✅ FIX: clean quantity
                $quantity = (int) preg_replace('/[^0-9]/', '', $quantityRaw);
                if ($quantity <= 0 || $quantity > 10) {
                    $quantity = 1;
                }

                if (!$orderId || !$productName) {
                    continue;
                }

                /* ================= DUPLICATE CHECK ================= */
                if (
                    ShopifyOrder::where('client_id', $this->clientId)
                    ->where('order_id', $orderId)
                    ->exists()
                ) {
                    continue;
                }

                /* ================= WEIGHT ================= */
                $totalWeight   = $this->getClientFiveWeight($productName, $quantity);
                $weightPerUnit = $totalWeight / $quantity;

                /* ================= BARCODE ================= */
                /* ================= PAYMENT MODE ================= */
                $paymentMode = strtoupper(trim($row['payment_mode'] ?? 'COD'));

                /* ================= BARCODE TYPE ================= */
                $barcodeType = ($paymentMode == 'VPP') ? 'VPP' : 'COD';

                /* ================= BARCODE ================= */
                $barcode = Barcode::where('client_id', $this->clientId)
                    ->where('barcode_type', $barcodeType)
                    ->where('is_used', 0)
                    ->lockForUpdate()
                    ->first();

                if (!$barcode) {
                    throw new \Exception("No unused {$barcodeType} barcode available");
                }

                /* ================= DATE ================= */
                try {
                    $orderDate = Carbon::parse($orderDateRaw)->format('Y-m-d H:i:s');
                } catch (\Exception $e) {
                    $orderDate = now();
                }

                /* ================= PHONE ================= */
                $customerPhone = $row['shipping_phone'] ?? null;
                if ($customerPhone) {
                    $customerPhone = preg_replace('/[^0-9]/', '', $customerPhone);
                    if (str_starts_with($customerPhone, '91')) {
                        $customerPhone = substr($customerPhone, 2);
                    }
                }
                // dd($row->toArray());
                /* ================= INSERT ================= */
                ShopifyOrder::create([
                    'client_id'            => $this->clientId,
                    'order_id'             => $orderId,
                    'shopify_order_id'     => $row['shopify_orderid'] ?? null,
                    'order_date'           => $orderDate,
                    'shopify_product_name' => $productName,
                    'quantity'             => $quantity,
                    'weight_per_unit'      => $weightPerUnit,
                    'total_weight'         => $totalWeight,
                    'barcode'              => $barcode->barcode,
                    'customer_name'        => $row['billing_name'] ?? null,
                    'age'        => $row['age'] ?? null,
                    'customer_phone'       => $customerPhone,
                    'shipping_address'     => $row['Shipping_Street'] ?? null,
                    'city'                 => $row['shipping_city'] ?? null,
                    'state'                => $row['shipping_province'] ?? null,
                    'pincode'              => $row['shipping_zip'] ?? null,
                    'payment_mode'         => $paymentMode,
                    'amount'               => $row['total'] ?? 0,
                ]);

                $barcode->update(['is_used' => 1]);

                DB::commit();
                $this->importedCount++;
            } catch (\Exception $e) {
                DB::rollBack();

                Log::error("Import Error", [
                    'row' => $index,
                    'error' => $e->getMessage()
                ]);

                continue;
            }
        }

        session()->flash('success', "{$this->importedCount} orders imported successfully.");
    }
}
