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

    /* =========================================================
       COLLECTION
    ========================================================= */

    public function collection()
    {
        if ($this->orders instanceof Collection) {
            return $this->orders;
        }

        return ShopifyOrder::where('client_id', $this->clientId)
            ->orderBy('id')
            ->get();
    }

    /* =========================================================
       SHEET TITLE
    ========================================================= */

    public function title(): string
    {
        return 'ArticleDetails';
    }

    /* =========================================================
       HEADINGS
    ========================================================= */

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
            'DELIVERY SLOT',
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

    /* =========================================================
       ADDRESS SPLITTER
    ========================================================= */

    private function splitAddress(string $address, int $limit = 50): array
    {
        $address = trim(preg_replace('/\s+/', ' ', $address));

        if ($address === '') {
            return ['NA', ''];
        }

        if (strlen($address) <= $limit) {
            return [$address, ''];
        }

        $line1 = substr($address, 0, $limit);
        $line2 = substr($address, $limit);

        return [
            trim($line1),
            trim($line2),
        ];
    }

    /* =========================================================
       MAP DATA
    ========================================================= */

    public function map($order): array
    {
        $client = $this->client;

        /* ================= MOBILE CLEAN ================= */

        $receiverMobile = preg_replace('/\D/', '', $order->customer_phone ?? '');

        if (strlen($receiverMobile) > 10) {
            $receiverMobile = substr($receiverMobile, -10);
        }

        $senderMobile = preg_replace('/\D/', '', $client->mobile ?? '');

        if (strlen($senderMobile) > 10) {
            $senderMobile = substr($senderMobile, -10);
        }

        /* ================= ADDRESS ================= */

        if ($this->clientId === 5) {

            $senderLine1 = 'Sco 51 Phase 3';
            $senderLine2 = 'Model Town';
        } else {

            [$senderLine1, $senderLine2] = $this->splitAddress(
                ($client->address_line1 ?? '') . ' ' . ($client->address_line2 ?? '')
            );
        }

        [$receiverLine1, $receiverLine2] = $this->splitAddress(
            $order->shipping_address ?? ''
        );

        /* ================= PAYMENT MODE ================= */

        $paymentMode = strtoupper(trim($order->payment_mode ?? 'COD'));

        $isCod = in_array($paymentMode, ['COD', 'VPP', 'CODR']);

        $indiaPostPaymentType = '';

        if ($paymentMode == 'COD') {
            $indiaPostPaymentType = 'COD';
        } elseif ($paymentMode == 'VPP') {
            $indiaPostPaymentType = 'CODR';
        }

        /* ================= DROP PINCODE ================= */

        $dropOffPincode = trim((string)($client->pincode ?? ''));

        /* ================= WEIGHT ================= */

        $weight = (int)($order->total_weight ?? 500);

        if ($weight <= 0) {
            $weight = 500;
        }

        /* ================= COD VALUE ================= */

        $codAmount = (int)($order->amount ?? 0);

        if ($codAmount <= 0) {
            $codAmount = 1;
        }

        return [

            ++$this->i,

            $order->barcode ?? '',

            $weight,

            'NROL',

            15,

            10,

            5,

            'TRUE',

            'ND',

            '',

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

            '',

            '',

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

            '', // DROPOFF/PICKUP OFFICE ID

            $senderMobile,

            $receiverMobile,

            $isCod ? '' : 'PREPAID',

            $isCod ? '' : $codAmount,

            $indiaPostPaymentType,

            $isCod ? $codAmount : '',

            '',

            '',

            'FALSE', // ACK

            'FALSE', // REGISTRATION

            'FALSE', // OTP BASED DELIVERY

            '#' . ($order->order_id ?? ''),
        ];
    }
}
