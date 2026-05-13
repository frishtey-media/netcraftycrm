<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class PostOfficeMultiSheetExport implements WithMultipleSheets
{
    protected $orders;

    public function __construct($orders)
    {
        $this->orders = $orders;
    }

    public function sheets(): array
    {
        return [

            new PostOfficeExport($this->orders),

            new PickupAddressSheet(),

            new AltAddressSheet(),

            new InformationSheet(),
        ];
    }
}
