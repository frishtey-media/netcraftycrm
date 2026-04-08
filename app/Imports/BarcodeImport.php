<?php

namespace App\Imports;

use App\Models\Barcode;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class BarcodeImport implements ToCollection
{
    public int $imported = 0;
    public int $skipped = 0;

    protected int $clientId;


    public function __construct(int $clientId)
    {
        $this->clientId = $clientId;
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {


            if ($index === 0) {
                continue;
            }

            $barcode = trim($row[0] ?? '');


            if ($barcode === '') {
                continue;
            }


            $exists = Barcode::where('barcode', $barcode)
                ->where('client_id', $this->clientId)
                ->exists();

            if ($exists) {
                $this->skipped++;
            } else {
                Barcode::create([
                    'barcode'   => $barcode,
                    'client_id' => $this->clientId,
                ]);

                $this->imported++;
            }
        }
    }
}
