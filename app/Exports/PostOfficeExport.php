<?php

namespace App\Exports;

use App\Models\ShopifyOrder;
use App\Models\Client;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class PostOfficeExport implements FromCollection, WithHeadings, WithMapping, WithTitle
{
    private int $i = 0;
    private ?int $clientId = null;
    private ?Client $client = null;
    private ?Collection $orders = null;

    public function __construct(int|Collection $data)
    {
        if ($data instanceof Collection) {
            $this->orders   = $data;
            $this->clientId = $data->first()?->client_id;
        } else {
            $this->clientId = $data;
        }

        if ($this->clientId) {
            $this->client = Client::find($this->clientId);
        }
    }

    /* ================= COLLECTION ================= */
    public function collection()
    {
        if ($this->orders instanceof Collection) {
            return $this->orders;
        }

        return ShopifyOrder::where('client_id', $this->clientId)
            ->orderBy('id')
            ->get();
    }

    /* ================= SHEET NAME ================= */
    public function title(): string
    {
        return 'ArticleDetails';
    }

    /* ================= HEADINGS ================= */
    public function headings(): array
    {
        return [
            'SERIAL NUMBER',
            'BARCODE NO',
            'PHYSICAL WEIGHT',
            'SHAPE OF ARTICLE',
            'LENGTH',
            'BREADTH/DIAMETER',
            'HEIGHT',
            'PRIORITY FLAG',
            'DELIVERY INSTRUCTION',
            'INSTRUCTION RTS',
            'SENDER NAME',
            'SENDER COMPANY',
            'SENDER ADD LINE 1',
            'SENDER ADD LINE 2',
            'SENDER CITY',
            'SENDER STATE',
            'SENDER PINCODE',
            'SENDER EMAILID',
            'SENDER ALT CONTACT',
            'SENDER KYC',
            'SENDER TAX REFERENCE',
            'RECEIVER NAME',
            'RECEIVER COMPANY',
            'RECEIVER ADD LINE 1',
            'RECEIVER ADD LINE 2',
            'RECEIVER CITY',
            'RECEIVER STATE',
            'RECEIVER PINCODE',
            'RECEIVER EMAILID',
            'RECEIVER ALT CONTACT',
            'RECEIVER KYC',
            'RECEIVER TAX REFERENCE',
            'ALT ADDRESS FLAG',
            'PICKUP ADDRESS FLAG',
            'DROP OFF PINCODE',
            'DROPOFF/PICKUP OFFICE ID',
            'SENDER MOBILE NO',
            'RECEIVER MOBILE NO',
            'PREPAYMENT CODE',
            'VALUE OF PREPAYMENT',
            'CODR/COD',
            'VALUE FOR CODR/COD',
            'INSURANCE TYPE',
            'VALUE OF INSURANCE',
            'ACK',
            'REGISTRATION',
            'OTP BASED DELIVERY',
            'BULK REFERENCE',
        ];
    }

    /* ================= ADDRESS SPLITTER ================= */
    private function splitAddress(string $address, int $limit = 50): array
    {
        $address = trim(preg_replace('/\s+/', ' ', $address));

        if ($address === '') {
            return ['NA', 'NA'];
        }

        $line1 = substr($address, 0, $limit) ?: 'NA';

        return [
            $line1,
            $line1, // ✅ copy line1 to line2 (CEPT safe)
        ];
    }

    /* ================= MAP ================= */
    public function map($order): array
    {
        $client = $this->client;

        $receiverMobile = preg_replace('/\D/', '', $order->customer_phone ?? '');
        $receiverMobile = strlen($receiverMobile) > 10
            ? substr($receiverMobile, -10)
            : $receiverMobile;

        [$senderLine1, $senderLine2] = $this->splitAddress(
            ($client->address_line1 ?? 'Main Office') . ' ' . ($client->address_line2 ?? '')
        );

        [$receiverLine1, $receiverLine2] = $this->splitAddress(
            $order->shipping_address ?? 'NA'
        );

        $isCod = strtolower($order->payment_mode ?? '') === 'cod';

        $dropOffPincode = trim((string)($order->pincode ?? ''));
        if ($dropOffPincode === '') {
            $dropOffPincode = (string)($client->pincode ?? '');
        }

        /* =====================================================
           CLIENT ID = 5 (SPECIAL LOGIC)
        ====================================================== */
        if ($this->clientId === 5) {

            return [
                ++$this->i,
                $order->barcode ?? '',
                (int)($order->total_weight ?? 1000),
                'PARCEL', // ✅ safer than NROL
                30,
                20,
                10,
                'TRUE',
                'ND',
                'RTS',

                strtoupper($client->client_name ?? ''),
                strtoupper($client->company_name ?? ''),
                $senderLine1,
                $senderLine2,
                strtoupper($client->city ?? ''),
                strtoupper($client->state ?? ''),
                (string)($client->pincode ?? ''),
                $client->email ?? '',
                '',
                $client->kyc_no ?? '',
                $client->gst_no ?? '',

                strtoupper($order->customer_name ?? ''),
                '',
                $receiverLine1,
                $receiverLine2,
                strtoupper($order->city ?? ''),
                strtoupper($order->state ?? ''),
                (string)($order->pincode ?? ''),
                $order->customer_email ?? '',
                '',
                '',
                '',

                'FALSE',
                'FALSE',
                $dropOffPincode,
                '',
                $client->mobile ?? '',
                $receiverMobile,
                '',
                0,
                'COD',
                max((int)($order->amount ?? 0), 100),
                'FALSE',
                0,
                'TRUE',
                'TRUE',
                'TRUE',
                $order->order_id ?? '',
            ];
        }

        /* =====================================================
           OTHER CLIENTS
        ====================================================== */

        return [
            ++$this->i,
            $order->barcode ?? '',
            (int)($order->total_weight ?? 1000),
            'PARCEL',
            30,
            20,
            10,
            'TRUE',
            'ND',
            'RTS',

            strtoupper($client->client_name ?? ''),
            strtoupper($client->company_name ?? ''),
            $senderLine1,
            $senderLine2,
            strtoupper($client->city ?? ''),
            strtoupper($client->state ?? ''),
            (string)($client->pincode ?? ''),
            $client->email ?? '',
            '',
            $client->kyc_no ?? '',
            $client->gst_no ?? '',

            strtoupper($order->customer_name ?? ''),
            '',
            $receiverLine1,
            $receiverLine2,
            strtoupper($order->city ?? ''),
            strtoupper($order->state ?? ''),
            (string)($order->pincode ?? ''),
            $order->customer_email ?? '',
            '',
            '',
            '',

            'FALSE',
            'FALSE',
            $dropOffPincode,
            '',
            $client->mobile ?? '',
            $receiverMobile,
            $isCod ? '' : 'PREPAID',
            $isCod ? 0 : (int)$order->amount,
            $isCod ? 'COD' : '',
            $isCod ? (int)$order->amount : 0,
            'FALSE',
            0,
            'TRUE',
            'TRUE',
            'TRUE',
            $order->order_id ?? '',
        ];
    }
}
