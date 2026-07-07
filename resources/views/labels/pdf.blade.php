@php
    use Picqer\Barcode\BarcodeGeneratorPNG;
    $generator = new BarcodeGeneratorPNG();
@endphp

<style>
    @page {
        margin: 0;
    }

    body {
        margin: 0;
        padding: 0;
        font-family: Helvetica, Arial, sans-serif;
        font-size: 11px;
        line-height: 16px;
    }


    .label {
        width: 340px;
        /* 4 inch */
        height: 524px;
        /* 6 inch */
        margin: 10px;
        padding: 10px;

        border: 1px solid #000;
        box-sizing: border-box;
        overflow: hidden;

    }

    .section {
        margin-bottom: 10px;

    }

    hr {
        border: none;
        border-top: 1px solid #000;
        margin: 10px 0;
    }

    .barcode {
        text-align: center;

        margin-bottom: 10px;
    }

    .bold {
        font-weight: bold;
    }


    .order-info {
        position: absolute;
        right: 10px;
        top: 105px;
        text-align: right;
    }

    .info-table td {
        vertical-align: top;
        line-height: 1.1;
        padding: 0;
    }

    .info-table .right {
        text-align: right;
    }
</style>


@foreach ($orders as $index => $order)
    <div class="label">


        @if ($order->barcode)
            <div class="barcode">
                <img
                    src="data:image/png;base64,{{ base64_encode($generator->getBarcode($order->barcode, $generator::TYPE_CODE_128, 2, 45)) }}">
                <div class="bold">{{ $order->barcode }}</div>

            </div>
        @endif


        <!--@if (strtolower($order->payment_mode) !== 'prepaid')
<div class="section">
                <div class="bold">Tracking No: {{ $order->barcode }}</div>

                <span class="bold">Payment Mode:</span> {{ $order->payment_mode }}<br>
                <span class="bold">Amount:</span> Rs. {{ $order->amount }}
            </div>
@endif-->

        <table width="100%" style="margin-top:10px;">
            <tr>
                <td width="50%" valign="top" style="font-size:12px;">
                    <strong>Tracking No:</strong><br>
                    {{ $order->barcode }}<br>

                    <strong>Payment Mode:</strong> {{ $order->payment_mode }}<br>

                    @if (strtolower($order->payment_mode) !== 'prepaid')
                        <strong>Amount:</strong> Rs. {{ number_format($order->amount, 2) }}
                    @endif
                </td>

                <td width="50%" valign="top" style="text-align:right;font-size:12px;">
                    @if (strtolower(trim($sender->customer_name)) === 'dr bhangu ayurveda')
                        <strong>Biller ID:</strong><br>
                        60883<br>
                    @else
                        <strong>Customer ID:</strong><br>
                        1745048970<br>
                    @endif

                    <strong>Order ID:</strong>
                    {{ $order->order_id }}<br>

                    <strong>Date:</strong>
                    {{ \Carbon\Carbon::parse($order->order_date)->format('d-m-y') }}
                </td>
            </tr>
        </table>




        <hr>


        <div class="section">
            <span class="bold">Name:</span> {{ $order->customer_name }}<br>
            <span class="bold">Father Name :</span> {{ $order->father_name }}<br>
            <span class="bold">Address:</span>
            {{ $order->shipping_address }}<br>
            <span class="bold">City:</span>
            {{ $order->city }}<br>
            <span class="bold">State:</span>
            {{ $order->state }}<br>
            <span class="bold">Pincode:</span> {{ ltrim($order->pincode, "'") }}<br>

            @php
                $mobile = trim($order->customer_phone);

                $numbers = preg_split('/[\/,]+/', $mobile);

                $numbers = array_map(function ($num) {
                    $num = preg_replace('/[^0-9]/', '', trim($num));

                    // Remove country code 91 only
                    if (strlen($num) == 12 && substr($num, 0, 2) == '91') {
                        $num = substr($num, 2);
                    }

                    return $num;
                }, $numbers);

                $mobile = implode(' / ', array_filter($numbers));
            @endphp

            <span class="bold">Mobile No:</span> {{ $mobile }}
        </div>


        <div class="section">
            <span class="bold">Product Name:</span>
            {{ $order->shopify_product_name ?? $order->product }}<br>
            <span class="bold">Quantity:</span> {{ $order->quantity }}<br>
            <span class="bold">Weight:</span> {{ $order->total_weight ?? $order->weight }} GMs
        </div>

        <hr>


        <div class="section">
            <span class="bold">From:</span>
            {{ $sender->customer_name }}<br>

            <span class="bold">Communication Address:</span><br>
            {!! nl2br(e($sender->customer_phone)) !!}
        </div>
        @php
            $senderName = strtolower(trim($sender->customer_name));

            $bhanguLogo = public_path('images/Bhangu_Logo_1.png');
            $vivaeliLogo = public_path('images/Viveali_Logo_1.png');
        @endphp

        @if (str_contains($senderName, 'bhangu') && file_exists($bhanguLogo))
            <div style="text-align:right;">
                <img src="data:image/png;base64,{{ base64_encode(file_get_contents($bhanguLogo)) }}" width="40">
            </div>
        @endif

        @if (str_contains($senderName, 'viva') && file_exists($vivaeliLogo))
            <div style="text-align:right;margin-top:20px;">
                <img src="data:image/png;base64,{{ base64_encode(file_get_contents($vivaeliLogo)) }}" width="100">
            </div>
        @endif
    </div>


    @if (!$loop->last)
        <div style="page-break-after: always;"></div>
    @endif
@endforeach
