<?php

namespace App\Exports;

use App\Models\ShopifyOrder;
use App\Models\Client;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class PostOfficeExport implements FromCollection, WithHeadings, WithMapping, WithTitle
{
    private int $i = 0;
    private int $clientId;
    private ?Client $client = null;

    public function __construct(int $clientId)
    {
        $this->clientId = $clientId;
        $this->client   = Client::find($clientId);
    }

    /* ================= COLLECTION ================= */
    public function collection()
    {
        return ShopifyOrder::where('client_id', $this->clientId)
            ->orderBy('id')
            ->get();
    }

    /* ================= SHEET NAME (MANDATORY) ================= */
    public function title(): string
    {
        return 'BulkBooking';
    }

    /* ================= HEADINGS (SAME FOR ALL CLIENTS) ================= */
    public function headings(): array
    {
        return [
            'SERIAL NUMBER',
            'BARCODE NO',
            'PHYSICAL WEIGHT',
            'REG',
            'OTP',
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
            'BREADTH',
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

    /* ================= MAP ================= */
    public function map($order): array
    {
        $client = $this->client;

        $receiverMobile = preg_replace('/\D/', '', $order->customer_phone ?? '');
        if (strlen($receiverMobile) > 10) {
            $receiverMobile = substr($receiverMobile, -10);
        }

        $isCod     = strtolower($order->payment_mode) === 'cod';
        $codAmount = max((int) $order->amount, 100);

        /* ---------- CLIENT ID = 5 ---------- */
        if ($this->clientId === 5) {
            return [
                ++$this->i,
                $order->barcode,
                $order->total_weight ?? 300,

                'Y',        // REG
                'N',        // OTP

                strtoupper($order->city),
                (string) $order->pincode,
                strtoupper($order->customer_name),

                $order->shipping_address,
                '',
                '',

                'N',        // ACK

                $client->mobile ?? '',
                $receiverMobile,

                'NA',
                0,

                'COD',
                $codAmount,

                'N',
                0,

                'R',

                15,
                10,
                10,

                'N',
                'ND',
                '',
                '',

                strtoupper($client->client_name ?? ''),
                strtoupper($client->company_name ?? $client->client_name ?? ''),
                strtoupper($client->city ?? ''),
                strtoupper($client->state ?? ''),
                (string) $client->pincode,

                $client->email ?? '',
                '',
                '',
                '',

                '',
                strtoupper($order->state ?? ''),
                '',
                '',
                '',
                '',

                'N',
                '88/1',

                $client->address ?? '',
                '',
                '',
            ];
        }

        /* ---------- OTHER CLIENTS ---------- */
        return [
            ++$this->i,
            $order->barcode,
            $order->total_weight ?? 300,

            'Y',
            'N',

            strtoupper($order->city),
            (string) $order->pincode,
            strtoupper($order->customer_name),

            $order->shipping_address,
            '',
            '',

            'N',

            $client->mobile ?? '',
            $receiverMobile,

            'NA',
            0,

            $isCod ? 'COD' : '',
            $isCod ? $codAmount : 0,

            'N',
            0,

            'R',

            22,
            22,
            8,

            'N',
            'ND',
            '',
            '',

            strtoupper($client->client_name ?? ''),
            strtoupper($client->company_name ?? ''),
            strtoupper($client->city ?? ''),
            strtoupper($client->state ?? ''),
            (string) $client->pincode,

            $client->email ?? '',
            '',
            '',
            '',

            '',
            strtoupper($order->state ?? ''),
            '',
            '',
            '',
            '',

            'N',
            '',

            $client->address ?? '',
            '',
            '',
        ];
    }
}
