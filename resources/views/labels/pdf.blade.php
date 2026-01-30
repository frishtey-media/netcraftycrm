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

        <div class="section">
            <div class="bold">Tracking No: {{ $order->barcode }}</div>

            <span class="bold">Payment Mode:</span> {{ $order->payment_mode }}<br>
            @if (strtolower($order->payment_mode) !== 'prepaid')
                <span class="bold">Amount:</span> Rs. {{ $order->amount }}
            @endif
        </div>


        <div class="section text-right" style="text-align: right">
            @if (strtolower(trim($sender->customer_name)) === 'dr bhangu ayurveda')
                <span class="bold">Biller ID:</span> 60883<br>
            @endif

            <span class="bold">Order ID:</span> {{ $order->order_id }}<br>
            <span class="bold">Date:</span>
            {{ \Carbon\Carbon::parse($order->order_date)->format('d-m-y') }}
        </div>

        <hr>


        <div class="section">
            <span class="bold">Name:</span> {{ $order->customer_name }}<br>
            <span class="bold">Father Name :</span> {{ $order->father_name }}<br>
            <span class="bold">Address:</span>
            {{ $order->shipping_address }}<br>
            <span class="bold">Pincode:</span> {{ ltrim($order->pincode, "'") }}<br>

            <span class="bold">Mobile No:</span> {{ $order->customer_phone }}
        </div>


        <div class="section">
            <span class="bold">Product Name:</span>
            {{ $order->shopify_product_name }}<br>
            <span class="bold">Quantity:</span> {{ $order->quantity }}<br>
            <span class="bold">Weight:</span> {{ $order->total_weight }} GMs
        </div>

        <hr>


        <div class="section">
            <span class="bold">From:</span><br>
            {{ $sender->customer_name }}<br>

            <span class="bold">Communication Address:</span><br>
            {!! nl2br(e($sender->customer_phone)) !!}
        </div>
        @if (strtolower(trim($sender->customer_name)) === 'dr bhangu ayurveda')
            <div style="text-align: right;">
                <img src="{{ public_path('images/Bhangu_Logo_1.png') }}" width="80">



            </div>
        @endif


    </div>


    @if (!$loop->last)
        <div style="page-break-after: always;"></div>
    @endif
@endforeach
