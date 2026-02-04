@php
    $receiver = [
        'name' => 'Ram Singh',
        'surname' => 'Gill',
        'address' => 'SCO-51, 2nd Floor Phase-3 Model Town',
        'city' => 'Bathinda',
        'state' => 'Punjab',
        'pincode' => '151001',
        'mobile' => '9780100226',
    ];
@endphp

<!DOCTYPE html>
<html>

<head>
    <style>
        @page {
            size: A4;
            margin: 0;
        }

        body {
            margin: 0;
            font-family: DejaVu Sans;
            font-size: 10px;
        }

        .page {
            position: relative;
            width: 210mm;
            height: 297mm;
            overflow: hidden;
            /* ⬅ very important */
        }

        .bg {
            position: absolute;
            top: 0;
            left: 0;
            width: 210mm;
            height: 297mm;
        }

        .text {
            position: absolute;
            z-index: 1;
            line-height: 16px;
            font-weight: bold;
            white-space: nowrap;
        }
    </style>
</head>

<body>

    @foreach ($orders as $order)
        <div class="page">

            {{-- Background --}}
            <img src="{{ public_path('moneyorder/combined_mo.png') }}" class="bg">

            {{-- ================= SENDER ================= --}}
            {{-- ================= SENDER ================= --}}
            <div class="text" style="top:103px; left:250px;">{{ $order->customer_name }}</div>
            <div class="text" style="top:110px; left:250px;">{{ $order->sender_surname }}</div>
            <div class="text" style="top:139px; left:250px;">{{ $order->shipping_address }}</div>
            <div class="text" style="top:158px; left:250px;">{{ $order->city }}</div>
            <div class="text" style="top:176px; left:250px;">{{ $order->state }}</div>
            <div class="text" style="top:194px; left:250px;">{{ $order->pincode }}</div>
            <div class="text" style="top:213px; left:250px;">{{ $order->customer_phone }}</div>



            {{-- ================= RECEIVER ================= --}}
            <div class="text" style="top:250px; left:250px;">{{ $receiver['name'] }}</div>
            <div class="text" style="top:270px; left:250px;">{{ $receiver['surname'] }}</div>
            <div class="text" style="top:288px; left:250px;">{{ $receiver['address'] }}</div>
            <div class="text" style="top:305px; left:250px;">{{ $receiver['city'] }}</div>
            <div class="text" style="top:324px; left:250px;">{{ $receiver['state'] }}</div>
            <div class="text" style="top:342px; left:250px;">{{ $receiver['pincode'] }}</div>
            <div class="text" style="top:361px; left:250px;">{{ $receiver['mobile'] }}</div>


            {{-- ================= AMOUNT ================= --}}
            <div class="text" style="top:378px; left:282px;">
                {{ number_format($order->amount, 2) }}
            </div>

            <div class="text" style="top:378px; left:430px;">
                {{ \App\Helpers\AmountHelper::toWords($order->amount) }}
            </div>


        </div>
    @endforeach

</body>

</html>
