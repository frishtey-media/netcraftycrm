<?php

namespace App\Imports;

use App\Models\DelhiveryImport;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class DelhiveryExcelImport implements ToCollection, WithHeadingRow
{
    public int $imported = 0;

    public int $skipped = 0;

    public int $totalRows = 0;

    public array $errors = [];

    /**
     * IDs imported during THIS Excel upload.
     */
    public array $importedIds = [];

    public function __construct(
        protected int $clientId,
        protected string $importDate
    ) {}

    /**
     * Parse Excel date correctly.
     *
     * Excel can provide dates as:
     * 1. Excel serial number
     * 2. d-m-Y
     * 3. d/m/Y
     * 4. Y-m-d
     * 5. DateTime / Carbon
     */
    private function parseOrderDate($value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        /*
        |--------------------------------------------------------------------------
        | Carbon / DateTime
        |--------------------------------------------------------------------------
        */
        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance(
                \DateTime::createFromInterface($value)
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Excel Numeric Date
        |--------------------------------------------------------------------------
        */
        if (is_numeric($value)) {
            try {
                $date = ExcelDate::excelToDateTimeObject(
                    (float) $value
                );

                return Carbon::instance($date);
            } catch (\Throwable $e) {
                return null;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | String Date
        |--------------------------------------------------------------------------
        */
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        $formats = [
            'd-m-Y H:i:s',
            'd-m-Y H:i',
            'd-m-Y',

            'd/m/Y H:i:s',
            'd/m/Y H:i',
            'd/m/Y',

            'd.m.Y H:i:s',
            'd.m.Y H:i',
            'd.m.Y',

            'd-m-y H:i:s',
            'd-m-y H:i',
            'd-m-y',

            'd/m/y H:i:s',
            'd/m/y H:i',
            'd/m/y',

            'Y-m-d H:i:s',
            'Y-m-d H:i',
            'Y-m-d',
        ];

        foreach ($formats as $format) {
            try {
                $date = Carbon::createFromFormat(
                    $format,
                    $value
                );

                if ($date !== false && $date->year >= 2000) {
                    return $date;
                }
            } catch (\Throwable $e) {
                // Try next format
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Last fallback
        |--------------------------------------------------------------------------
        */
        try {
            $date = Carbon::parse($value);

            if ($date->year >= 2000) {
                return $date;
            }
        } catch (\Throwable $e) {
            // Invalid date
        }

        return null;
    }

    public function collection(Collection $rows)
    {
        $this->totalRows = $rows->count();

        foreach ($rows as $index => $row) {

            /*
             * Excel heading row = row 1
             * Actual data starts = row 2
             */
            $excelRow = $index + 2;

            try {

                /*
                |--------------------------------------------------------------------------
                | Basic Fields
                |--------------------------------------------------------------------------
                */

                $orderId = trim(
                    (string) (
                        $row['order_id'] ?? ''
                    )
                );

                /*
                |--------------------------------------------------------------------------
                | IMPORTANT DATE FIX
                |--------------------------------------------------------------------------
                |
                | Your Excel column is "Date".
                |
                | WithHeadingRow:
                | "Date"       => $row['date']
                | "Order Date" => $row['order_date']
                |
                | So we support both.
                |
                */
                $excelOrderDate =
                    $row['date']
                    ?? $row['order_date']
                    ?? null;

                /*
                |--------------------------------------------------------------------------
                | If Excel date is empty, use selected Import Date
                |--------------------------------------------------------------------------
                */
                $orderDate = $this->parseOrderDate(
                    $excelOrderDate
                );

                if (!$orderDate && !empty($this->importDate)) {
                    $orderDate = $this->parseOrderDate(
                        $this->importDate
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | Payment
                |--------------------------------------------------------------------------
                */

                $payment = strtoupper(
                    trim(
                        (string) (
                            $row['payment_mode'] ?? ''
                        )
                    )
                );

                /*
                |--------------------------------------------------------------------------
                | Phone
                |--------------------------------------------------------------------------
                */

                $phone = trim(
                    (string) (
                        $row['customer_phone'] ?? ''
                    )
                );

                /*
                |--------------------------------------------------------------------------
                | Pincode
                |--------------------------------------------------------------------------
                */

                $pincode = trim(
                    (string) (
                        $row['shipping_pincode'] ?? ''
                    )
                );

                /*
                |--------------------------------------------------------------------------
                | Weight
                |--------------------------------------------------------------------------
                */

                $weight = $row['weight_in_gm'] ?? null;

                /*
                |--------------------------------------------------------------------------
                | Validation
                |--------------------------------------------------------------------------
                */

                if (!$orderId) {
                    throw new \Exception(
                        'Order ID missing'
                    );
                }

                if (strlen($orderId) > 50) {
                    throw new \Exception(
                        'Order ID cannot exceed 50 characters'
                    );
                }

                if (!in_array(
                    $payment,
                    ['COD', 'PREPAID'],
                    true
                )) {
                    throw new \Exception(
                        'Payment Mode must be COD or PREPAID'
                    );
                }

                if (!$phone) {
                    throw new \Exception(
                        'Customer Phone missing'
                    );
                }

                if (!preg_match(
                    '/^\d{6}$/',
                    $pincode
                )) {
                    throw new \Exception(
                        'Invalid Shipping Pincode'
                    );
                }

                if (
                    $weight === null ||
                    $weight === '' ||
                    (float) $weight <= 0
                ) {
                    throw new \Exception(
                        'Weight (in GM) is required'
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | Duplicate Check
                |--------------------------------------------------------------------------
                */

                if (
                    DelhiveryImport::where(
                        'order_id',
                        $orderId
                    )
                    ->where(
                        'client_id',
                        $this->clientId
                    )
                    ->exists()
                ) {
                    throw new \Exception(
                        'Duplicate Delhivery Order ID'
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | Create Import Record
                |--------------------------------------------------------------------------
                */

                $record = DelhiveryImport::create([

                    'client_id' =>
                    $this->clientId,

                    'order_id' =>
                    $orderId,

                    /*
                    |--------------------------------------------------------------------------
                    | FIXED ORDER DATE
                    |--------------------------------------------------------------------------
                    */
                    'order_date' =>
                    $orderDate
                        ? $orderDate->format('Y-m-d H:i:s')
                        : null,

                    'shopify_order_id' =>
                    $row['shopify_order_id']
                        ?? null,

                    'payment_mode' =>
                    $payment,

                    'amount' =>
                    (float) (
                        $row['amount'] ?? 0
                    ),

                    'customer_name' =>
                    trim(
                        (string) (
                            $row['customer_name']
                            ?? ''
                        )
                    ),

                    'father_name' =>
                    $row['father_name']
                        ?? null,

                    'customer_phone' =>
                    $phone,

                    'shipping_address' =>
                    trim(
                        (string) (
                            $row['shipping_address']
                            ?? ''
                        )
                    ),

                    'city' =>
                    $row['city']
                        ?? null,

                    'state' =>
                    $row['state']
                        ?? null,

                    'pincode' =>
                    $pincode,

                    'product' =>
                    $row['product']
                        ?? null,

                    'quantity' =>
                    (int) (
                        $row['quantity']
                        ?? 1
                    ),

                    'weight' =>
                    (float) $weight,

                    'age' =>
                    $row['age']
                        ?? null,

                    'assigned_staff' =>
                    $row['assigned_staff']
                        ?? null,

                    'status' =>
                    'pending',

                    'awb' =>
                    null,

                    'error_message' =>
                    null,

                    'serviceability_response' =>
                    null,

                    'booking_response' =>
                    null,
                ]);

                /*
                |--------------------------------------------------------------------------
                | Current Import ID
                |--------------------------------------------------------------------------
                */

                $this->importedIds[] =
                    $record->id;

                $this->imported++;
            } catch (\Throwable $e) {

                $this->skipped++;

                $this->errors[] = [

                    'type' =>
                    'excel',

                    'row' =>
                    $excelRow,

                    'order_id' =>
                    $row['order_id']
                        ?? null,

                    'error' =>
                    $e->getMessage(),
                ];
            }
        }
    }
}
