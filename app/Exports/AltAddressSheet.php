<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class AltAddressSheet implements FromCollection, WithHeadings, WithTitle
{
    public function title(): string
    {
        return 'AltAddress';
    }

    public function headings(): array
    {
        return [

            'SERIAL NO',
            'ADDRESSEE NAME',
            'COMPANY NAME',
            'ADDRESS LINE 1',
            'ADDRESS LINE 2',
            'ADDRESS LINE 3',
            'CITY',
            'STATE',
            'PINCODE',
            'EMAIL ID',
            'ALT CONTACT NO',
            'MOBILE NO',
        ];
    }

    public function collection()
    {
        return new Collection([]);
    }
}
