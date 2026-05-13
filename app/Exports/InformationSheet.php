<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithTitle;

class InformationSheet implements FromCollection, WithTitle
{
    public function title(): string
    {
        return 'Information';
    }

    public function collection()
    {
        return collect([

            [
                '',
                'SHAPE OF ARTICLE',
                '',
                'PRIORITY FLAG',
                '',
                '',
                'DELIVERY INSTRUCTION',
                '',
                '',
                'INSTRUCTION RTS',
                '',
                'CODR/COD',
                '',
                'INSURANCE TYPE',
                '',
                'PREPAYMENT',
                '',
                'PICKUP SCHEDULE SLOT',
            ],

            [
                'Code',
                'Description',
                'TRUE',
                '',
                '',
                'Code',
                'Description',
                'Code',
                'Description',
                'Code',
                'Description',
                'DOP',
                '',
                'Code',
                'Description',
                '10:00-13:00',
            ],

            [
                'ROLL',
                'Roll form',
                'FALSE',
                '',
                '',
                'ND',
                'Normal Delivery',
                'RTS',
                'Returned to Sender',
                'codr',
                'Cash On Delivery Retail(VP)',
                '',
                'PS',
                'Postage Stamps',
                '13:00-16:00',
            ],

            [
                'NROL',
                'Non Roll Form',
                '',
                '',
                '',
                'OD',
                'Open Delivery',
                'RTA',
                'Returned to Alternate Address',
                'cod',
                'Cash on Delivery',
                '',
                'FM',
                'Franking Machine',
                '',
            ],

            [
                'DOC',
                'Document',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                'SS',
                'Service Stamps',
                '',
            ],

            [],

            [
                '',
                'BOOLEAN(TRUE or FALSE)',
            ],

            [
                '',
                'PRIORITY FLAG',
            ],

            [
                '',
                'ALT ADDRESS FLAG',
            ],

            [
                '',
                'PICKUP ADDRESS FLAG',
            ],

            [
                '',
                'ACK',
            ],

            [
                '',
                'OTP BASED DELIVERY',
            ],

            [
                '',
                'REGISTRATION',
            ],

            [],

            [
                '',
                '',
                '',
                '',
                '',
                'INSTRUCTIONS',
            ],

            [
                '1',
                'If ALT ADDRESS FLAG is True, provide the address details in the AltAddress tab',
            ],

            [
                '2',
                'If PICK UP ADDRESS FLAG is True, provide the pickup details in the PickupAddress tab',
            ],

            [
                '3',
                'IF PICK UP ADDRESS FLAG is False,DropOff Pincode is mandatory in ArticleDetails tab',
            ],

            [
                '4',
                'Do not change the field names or their positions in the first row',
            ],

            [
                '5',
                'Please provide absolute values for physical weight, insurance amount, cod amount etc. Decimal values are not permitted',
            ],

            [
                '6',
                'Date format should be in DD-MM-YYYY and format in date',
            ],

            [
                '7',
                'Please use the specified codes as mentioned',
            ],

        ]);
    }
}
