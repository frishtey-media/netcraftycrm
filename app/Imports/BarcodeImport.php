<?php

namespace App\Imports;

use App\Models\Barcode;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class BarcodeImport implements ToCollection
{
    public int $imported = 0;
    public int $skipped = 0;
    protected string $barcodeType;
    protected int $clientId;


    public function __construct(int $clientId, string $barcodeType)
    {
        $this->clientId = $clientId;
        $this->barcodeType = $barcodeType;
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {

            if ($index == 0) {
                continue;
            }

            $barcode = strtoupper(trim((string) ($row[0] ?? '')));

            if (empty($barcode)) {
                continue;
            }

            $exists = Barcode::where('barcode', $barcode)
                ->where('client_id', $this->clientId)
                ->where('barcode_type', $this->barcodeType)
                ->exists();

            if ($exists) {

                $this->skipped++;
            } else {

                try {

                    Barcode::create([
                        'barcode'      => $barcode,
                        'client_id'    => $this->clientId,
                        'barcode_type' => $this->barcodeType,
                        'is_used'      => 0,
                    ]);
                } catch (\Exception $e) {

                    dd($e->getMessage());
                }

                $this->imported++;
            }
        }
    }
}
