<?php

namespace App\Imports;

use App\Models\Barcode;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class BarcodeImport implements ToCollection
{
    public int $imported = 0;
    public int $skipped = 0;

    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {

            // Skip header row
            if ($index === 0) {
                continue;
            }

            $barcode = trim($row[0] ?? '');

            // Skip empty barcode rows
            if ($barcode === '') {
                continue;
            }

            $exists = Barcode::where('barcode', $barcode)->exists();

            if ($exists) {
                $this->skipped++;
            } else {
                Barcode::create([
                    'barcode' => $barcode,
                ]);
                $this->imported++;
            }
        }
    }
}
