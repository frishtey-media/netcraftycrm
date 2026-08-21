<?php

namespace App\Imports;

use App\Models\DelhiveryImport;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

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

                $payment = strtoupper(
                    trim(
                        (string) (
                            $row['payment_mode'] ?? ''
                        )
                    )
                );

                $phone = trim(
                    (string) (
                        $row['customer_phone'] ?? ''
                    )
                );

                $pincode = trim(
                    (string) (
                        $row['shipping_pincode'] ?? ''
                    )
                );

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

                    'order_date' =>
                    $row['date']
                        ?? $this->importDate,

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

                /*
                |--------------------------------------------------------------------------
                | Excel Error
                |--------------------------------------------------------------------------
                */

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
