<?php

namespace App\Exports;

use App\Models\ShopifyOrder;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use App\Models\Client;

class PostOfficeExport implements FromCollection, WithHeadings, WithMapping
{
    private $i = 0;

    public function collection()
    {
        return ShopifyOrder::orderBy('id')->get();
    }

    public function headings(): array
    {
        return [
            'SERIAL NUMBER',
            'BARCODE NO',
            'PHYSICAL WEIGHT',
            'RECEIVER CITY',
            'RECEIVER PINCODE',
            'RECEIVER NAME',
            'RECEIVER ADD LINE 1',
            'RECEIVER ADD LINE 2',
            'RECEIVER ADD LINE 3',
            'ACK',
            'SENDER MOBILE NO',
            'RECEIVER MOBILE NO',
            'PREPAYMENT CODE',
            'VALUE OF PREPAYMENT',
            'CODR/COD',
            'VALUE FOR CODR/COD',
            'INSURANCE TYPE',
            'VALUE OF INSURANCE',
            'SHAPE OF ARTICLE',
            'LENGTH',
            'BREADTH/DIAMETER',
            'HEIGHT',
            'PRIORITY FLAG',
            'DELIVERY INSTRUCTION',
            'DELIVERY SLOT',
            'INSTRUCTION RTS',
            'SENDER NAME',
            'SENDER COMPANY NAME',
            'SENDER CITY',
            'SENDER STATE/UT',
            'SENDER PINCODE',
            'SENDER EMAILID',
            'SENDER ALT CONTACT',
            'SENDER KYC',
            'SENDER TAX',
            'RECEIVER COMPANY NAME',
            'RECEIVER STATE/UT',
            'RECEIVER EMAILID',
            'RECEIVER ALT CONTACT',
            'RECEIVER KYC',
            'RECEIVER TAX REF',
            'ALT ADDRESS FLAG',
            'BULK REFERENCE',
            'SENDER ADD LINE 1',
            'SENDER ADD LINE 2',
            'SENDER ADD LINE 3',
        ];
    }

    public function map($order): array
    {
        // 🔹 Default Sender (fallback)
        $senderName       = 'BHANGU AYURVEDIC CLINIC';
        $senderCompany    = 'BHANGU AYURVEDIC CLINIC';
        $senderMobile     = '7009184421';
        $senderCity       = 'SANGHOL';
        $senderState      = 'PUNJAB';
        $senderPincode    = '140802';
        $senderAdd1       = 'SANGHOL BASSI ROAD';
        $senderAdd2       = 'PUNJAB';
        $senderAdd3       = '';

        // 🔹 Fetch client dynamically
        if (!empty($order->client_id)) {
            $client = Client::find($order->client_id);

            if ($client) {
                $senderName    = strtoupper($client->client_name ?? $senderName);
                $senderCompany = strtoupper($client->company_name ?? $senderCompany);
                $senderMobile  = $client->mobile ?? $senderMobile;
                $senderCity    = strtoupper($client->city ?? $senderCity);
                $senderState   = strtoupper($client->state ?? $senderState);
                $senderPincode = $client->pincode ?? $senderPincode;

                // Split address safely
                if (!empty($client->address)) {
                    $senderAdd1 = $client->address;
                    $senderAdd2 = '';
                    $senderAdd3 = '';
                }
            }
        }

        return [
            ++$this->i,
            $order->barcode,
            $order->total_weight ?? 700,
            $order->city,
            ltrim($order->pincode, "'"),
            trim($order->customer_name),
            $order->shipping_address,
            '',
            '',
            'FALSE',

            // ✅ Sender Mobile (Dynamic)
            $senderMobile,

            // Receiver Mobile
            $order->customer_phone,

            '',
            '',
            strtoupper($order->payment_mode) === 'COD' ? 'COD' : '',
            strtoupper($order->payment_mode) === 'COD' ? $order->amount : '',
            '',
            '',
            'NROL',
            22,
            22,
            8,
            '',
            'ND',
            '',
            '',

            // ✅ Sender Details (Dynamic)
            $senderName,
            $senderCompany,
            $senderCity,
            $senderState,
            $senderPincode,
            $client->email ?? '',
            '',
            '',
            '',
            '',

            // Receiver State
            $order->state,
            '',
            '',
            '',
            '',
            'FALSE',
            '',
            $senderAdd1,
            $senderAdd2,
            $senderAdd3,
        ];
    }
}
