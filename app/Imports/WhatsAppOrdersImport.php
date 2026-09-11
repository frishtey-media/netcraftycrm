<?php

namespace App\Imports;

use App\Models\ShopifyOrder;
use App\Models\Barcode;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

use Carbon\Carbon;


class WhatsAppOrdersImport implements ToCollection, WithHeadingRow
{
    /*
    |--------------------------------------------------------------------------
    | COUNTERS
    |--------------------------------------------------------------------------
    */

    public $totalRows = 0;

    public $imported = 0;

    public $skipped = 0;

    public $errors = [];


    /*
    |--------------------------------------------------------------------------
    | DUPLICATE ORDER IDS
    |--------------------------------------------------------------------------
    */

    protected $seenOrderIds = [];


    /*
    |--------------------------------------------------------------------------
    | CLIENT
    |--------------------------------------------------------------------------
    */

    protected $clientId;

    protected $importDate;


    /*
    |--------------------------------------------------------------------------
    | CONSTRUCTOR
    |--------------------------------------------------------------------------
    */

    public function __construct($clientId, $importDate)
    {
        $this->clientId = $clientId;

        $this->importDate =
            Carbon::parse($importDate)->startOfDay();
    }


    /*
    |--------------------------------------------------------------------------
    | COLLECTION
    |--------------------------------------------------------------------------
    */

    public function collection(Collection $rows)
    {
        $this->totalRows = $rows->count();


        DB::transaction(function () use ($rows) {

            foreach ($rows as $index => $row) {

                $rowNumber = $index + 2;


                /*
                |--------------------------------------------------------------------------
                | REQUIRED FIELDS
                |--------------------------------------------------------------------------
                */

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

                    if (
                        !isset($row[$field]) ||
                        trim((string) $row[$field]) === ''
                    ) {

                        $this->addError(
                            $rowNumber,
                            "Column '{$field}' is empty"
                        );

                        $this->skipped++;

                        continue 2;
                    }
                }


                /*
                |--------------------------------------------------------------------------
                | DUPLICATE IN EXCEL
                |--------------------------------------------------------------------------
                */

                if (
                    in_array(
                        $row['order_id'],
                        $this->seenOrderIds
                    )
                ) {

                    $this->addError(
                        $rowNumber,
                        'Duplicate order_id in Excel file'
                    );

                    $this->skipped++;

                    continue;
                }


                $this->seenOrderIds[] =
                    $row['order_id'];


                /*
                |--------------------------------------------------------------------------
                | DUPLICATE IN DATABASE
                |--------------------------------------------------------------------------
                */

                if (
                    ShopifyOrder::where(
                        'order_id',
                        $row['order_id']
                    )->exists()
                ) {

                    $this->addError(
                        $rowNumber,
                        'order_id already exists in database'
                    );

                    $this->skipped++;

                    continue;
                }


                /*
                |--------------------------------------------------------------------------
                | QUANTITY
                |--------------------------------------------------------------------------
                */

                $quantity =
                    $this->parseQuantity(
                        $row['quantity']
                    );


                if ($quantity <= 0) {

                    $this->addError(
                        $rowNumber,
                        'Invalid quantity'
                    );

                    $this->skipped++;

                    continue;
                }


                /*
                |--------------------------------------------------------------------------
                | WEIGHT
                |--------------------------------------------------------------------------
                */

                $totalWeight =
                    $this->parseWeight(
                        $row['weight_in_gm']
                    );


                if ($totalWeight <= 0) {

                    $this->addError(
                        $rowNumber,
                        'Invalid weight'
                    );

                    $this->skipped++;

                    continue;
                }


                /*
                |--------------------------------------------------------------------------
                | PAYMENT MODE
                |--------------------------------------------------------------------------
                */

                $paymentMode = strtoupper(
                    trim(
                        $row['payment_mode'] ?? 'COD'
                    )
                );


                /*
                |--------------------------------------------------------------------------
                | BARCODE TYPE
                |--------------------------------------------------------------------------
                */

                $barcodeType =
                    $paymentMode === 'VPP'
                    ? 'VPP'
                    : 'COD';


                /*
                |--------------------------------------------------------------------------
                | BARCODE
                |--------------------------------------------------------------------------
                */

                $barcode =
                    Barcode::where(
                        'client_id',
                        $this->clientId
                    )
                    ->where(
                        'barcode_type',
                        $barcodeType
                    )
                    ->where(
                        'is_used',
                        0
                    )
                    ->orderBy('id', 'asc')
                    ->lockForUpdate()
                    ->first();


                if (!$barcode) {

                    $this->addError(
                        $rowNumber,
                        strtoupper($barcodeType) .
                            ' barcode not available'
                    );

                    $this->skipped++;

                    continue;
                }


                /*
                |--------------------------------------------------------------------------
                | CREATE ORDER
                |--------------------------------------------------------------------------
                |
                | IMPORTANT:
                | Stock is NOT deducted here.
                |
                */

                ShopifyOrder::create([

                    'client_id' =>
                    $this->clientId,

                    'order_id' =>
                    $row['order_id'],

                    'shopify_order_id' =>
                    $row['shopify_order_id'] ?? null,

                    'order_date' =>
                    $this->importDate,

                    'barcode' =>
                    $barcode->barcode,

                    'product_name' =>
                    $row['product'],

                    'shopify_product_name' =>
                    $row['product'],

                    'quantity' =>
                    $quantity,

                    'weight' =>
                    $totalWeight / $quantity,

                    'total_weight' =>
                    $totalWeight,

                    'payment_mode' =>
                    $paymentMode,

                    'amount' =>
                    $row['amount'] ?? 0,

                    'customer_name' =>
                    $row['customer_name'],

                    'age' =>
                    $row['age'] ?? null,

                    'father_name' =>
                    $row['father_name'] ?? null,

                    'customer_phone' =>
                    $row['customer_phone'],

                    'shipping_address' =>
                    $row['shipping_address'],

                    'city' =>
                    $row['city'],

                    'state' =>
                    $row['state'],

                    'pincode' =>
                    $row['shipping_pincode'],

                    'created_at' =>
                    $this->importDate,

                    'updated_at' =>
                    $this->importDate,
                ]);


                /*
                |--------------------------------------------------------------------------
                | MARK BARCODE USED
                |--------------------------------------------------------------------------
                */

                $barcode->update([
                    'is_used' => 1
                ]);


                $this->imported++;
            }
        });
    }


    /*
    |--------------------------------------------------------------------------
    | ERROR
    |--------------------------------------------------------------------------
    */

    private function addError($row, $message)
    {
        $this->errors[] =
            "Row {$row}: {$message}";
    }


    /*
    |--------------------------------------------------------------------------
    | QUANTITY
    |--------------------------------------------------------------------------
    */

    private function parseQuantity($value)
    {
        if (is_numeric($value)) {
            return (int) $value;
        }


        if (
            is_string($value) &&
            str_contains($value, '+')
        ) {

            return collect(
                explode('+', $value)
            )
                ->map(
                    fn($v) => (int) trim($v)
                )
                ->sum();
        }


        return 0;
    }


    /*
    |--------------------------------------------------------------------------
    | WEIGHT
    |--------------------------------------------------------------------------
    */

    private function parseWeight($value)
    {
        return (int) preg_replace(
            '/[^0-9]/',
            '',
            $value
        );
    }


    /*
    |--------------------------------------------------------------------------
    | DATE
    |--------------------------------------------------------------------------
    */

    private function parseDate($value)
    {
        if (empty($value)) {
            return now()->format('Y-m-d');
        }


        try {

            return Carbon::parse(
                $value
            )->format('Y-m-d');
        } catch (\Exception $e) {

            try {

                return Carbon::createFromFormat(
                    'd-M-y',
                    trim($value)
                )->format('Y-m-d');
            } catch (\Exception $e) {

                return now()->format('Y-m-d');
            }
        }
    }
}
