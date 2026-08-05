<?php

namespace App\Exports;

use App\Models\CallingOrder;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class SelectedStaffExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    protected $staffIds;
    protected $from;
    protected $to;
    protected $clientId;

    public function __construct($staffIds, $from, $to, $clientId = null)
    {
        $this->staffIds = $staffIds;
        $this->from = $from;
        $this->to = $to;
        $this->clientId = $clientId;
    }

    public function headings(): array
    {
        return [

            'Order ID',
            'Date',
            'Shopify Order id',
            'Payment Mode',
            'Amount',
            'Customer Name',
            'Father Name',
            'Customer Phone',
            'Shipping address',
            'City',
            'State',
            'Shipping Pincode',
            'Product',
            'Quantity',
            'Weight (in GM)',
            'Age',
            'Client Name',
            'Assigned Staff',
            'Created At',
            'Updated At',

        ];
    }

    public function collection()
    {

        $orders = CallingOrder::with(['client', 'staff'])
            ->whereIn('assigned_to', $this->staffIds)
            ->where('status', 'verified')
            ->whereBetween('updated_at', [
                Carbon::parse($this->from)->startOfDay(),
                Carbon::parse($this->to)->endOfDay()
            ])
            ->get()

            ->sortBy(function ($row) {
                return strtolower(optional($row->staff)->name);
            });

        return $orders->map(function ($o) {

            return [

                $o->order_id,
                $o->order_date,
                $o->shopify_order_id,
                $o->payment_mode,
                $o->amount,
                $o->customer_name,
                $o->father_name,
                $o->customer_phone,
                $o->shipping_address,
                $o->city,
                $o->state,
                $o->pincode,
                $o->product_name,
                $o->quantity,
                $o->total_weight,
                $o->age,
                optional($o->client)->client_name,
                optional($o->staff)->name,
                $o->created_at,
                $o->updated_at,

            ];
        });
    }
}
