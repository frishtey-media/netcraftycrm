<?php

namespace App\Imports;

use App\Models\callingorder;
use App\Models\CallingUser;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class BulkOrderImport implements ToCollection
{
    protected $clientId;
    protected $staffId;
    protected $createdAt;
    public function __construct(
        $clientId,
        $staffId,
        $createdAt
    ) {
        $this->clientId = $clientId;

        $this->staffId = $staffId;

        $this->createdAt = \Carbon\Carbon::parse($createdAt);
    }

    public function collection(Collection $rows)
    {
        // Remove Header Row
        $rows->shift();

        $staff = CallingUser::find($this->staffId);

        if (!$staff) {
            return;
        }

        foreach ($rows as $row) {

            if (empty($row[0])) {
                continue;
            }

            // Order ID Generate

            $name = trim($staff->name);

            $shortName =
                strtoupper(substr($name, 0, 1))
                . strtolower(substr($name, -1));

            // $date = now()->format('d-m-y');

            $date = $this->createdAt->format('d-m-y');

            $todayCount = CallingOrder::whereDate(
                'created_at',
                $this->createdAt
            )
                ->where(
                    'assigned_to',
                    $staff->id
                )
                ->count() + 1;

            $orderId = $shortName . '-' . $date . '-' . $todayCount;

            /*
            Excel Format

            0 Customer Name
            1 Father Name
            2 Age
            3 Phone
            4 Product
            5 Qty
            6 Weight
            7 Amount
            8 Payment Mode
            9 City
            10 State
            11 Pincode
            12 Address
            */

            $qty    = (int)($row[5] ?? 1);
            $weight = (float)($row[6] ?? 0);

            CallingOrder::create([

                'client_id' => $this->clientId,

                'assigned_to' => $staff->id,

                'order_id' => $orderId,

                'order_date' => $this->createdAt,

                'product_name' => $row[4] ?? '',

                'shopify_product_name' => $row[4] ?? '',

                'quantity' => $qty,

                'weight' => $weight,

                'total_weight' => ($qty * $weight),

                'customer_name' => $row[0] ?? '',

                'father_name' => $row[1] ?? '',

                'age' => $row[2] ?? null,

                'customer_phone' => $row[3] ?? '',

                'shipping_address' => $row[12] ?? '',

                'city' => $row[9] ?? '',

                'state' => $row[10] ?? '',

                'pincode' => $row[11] ?? '',

                'payment_mode' => $row[8] ?? '',

                'amount' => $row[7] ?? 0,

                'status' => 'verified',

                'order_source' => 'whatsapp',
                'created_at' => $this->createdAt,

                'updated_at' => $this->createdAt,
            ]);
        }
    }
}
