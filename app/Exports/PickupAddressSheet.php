<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class PickupAddressSheet implements FromCollection, WithHeadings, WithTitle
{
    public function title(): string
    {
        return 'PickupAddress';
    }

    public function headings(): array
    {
        return [

            'serial_no',
            'addressee_name',
            'company_name',
            'address_line1',
            'address_line2',
            'address_line3',
            'city',
            'state',
            'pincode',
            'email_id',
            'alt_contact_no',
            'mobile_no',
            'pickup_schedule_slot',
            'pickup_schedule_date',
        ];
    }

    public function collection()
    {
        return collect([

            [

                1,

                'AMAZON',

                '',

                'XYZ',

                '',

                '',

                'Chennai',

                'Tamilnadu',

                '600001',

                '',

                '',

                '1234567890',

                '13:00-16:00',

                now()->format('d-m-Y'),
            ]

        ]);
    }
}
