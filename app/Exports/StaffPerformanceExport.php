<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithMapping;
//use Illuminate\Http\Request;

class StaffPerformanceExport implements
    FromCollection,
    WithHeadings,
    WithMapping,
    ShouldAutoSize
{
    protected $staffSummary;

    //protected $request;

    public function __construct(Collection $staffSummary)
    {
        $this->staffSummary = $staffSummary;
    }

    public function collection()
    {
        $collection = $this->staffSummary;

        $collection->push((object)[

            'staff' => 'Grand Total',

            'web_orders' => $collection->sum('web_orders'),

            'web_delivered' => $collection->sum('web_delivered'),

            'web_percentage' => $collection->sum('web_orders') > 0
                ? round(($collection->sum('web_delivered') * 100) / $collection->sum('web_orders'), 2)
                : 0,

            'web_rto' => $collection->sum('web_rto'),

            'web_rto_received' => $collection->sum('web_rto_received'),

            'web_transit' => $collection->sum('web_transit'),

            'whatsapp_orders' => $collection->sum('whatsapp_orders'),

            'whatsapp_delivered' => $collection->sum('whatsapp_delivered'),

            'whatsapp_percentage' => $collection->sum('whatsapp_orders') > 0
                ? round(($collection->sum('whatsapp_delivered') * 100) / $collection->sum('whatsapp_orders'), 2)
                : 0,

            'whatsapp_rto' => $collection->sum('whatsapp_rto'),

            'whatsapp_rto_received' => $collection->sum('whatsapp_rto_received'),

            'whatsapp_transit' => $collection->sum('whatsapp_transit'),

        ]);

        return $collection;
    }

    public function headings(): array
    {
        return [

            'Staff',

            'Web Orders',

            'Web Delivered',

            'Web Delivery %',

            'Web RTO',

            'Web RTO Received',

            'Web Transit',

            'WhatsApp Orders',

            'WhatsApp Delivered',

            'WhatsApp Delivery %',

            'WhatsApp RTO',

            'WhatsApp RTO Received',

            'WhatsApp Transit',

        ];
    }

    public function map($row): array
    {
        return [

            $row->staff,

            $row->web_orders,

            $row->web_delivered,

            $row->web_percentage . '%',

            $row->web_rto,

            $row->web_rto_received,

            $row->web_transit,

            $row->whatsapp_orders,

            $row->whatsapp_delivered,

            $row->whatsapp_percentage . '%',

            $row->whatsapp_rto,

            $row->whatsapp_rto_received,

            $row->whatsapp_transit,

        ];
    }
}
