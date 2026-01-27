<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 6px;
        }

        th {
            background: #f2f2f2;
        }

        .right {
            text-align: right;
        }

        .center {
            text-align: center;
        }
    </style>
</head>

<body>

    <h3 class="center">TAX INVOICE</h3>

    <p><b>Sold By:</b> {{ $seller->seller_name }}</p>
    <p>{{ $seller->address }}</p>
    <p><b>GSTIN:</b> {{ $seller->gst_no }}</p>

    <hr>

    <p>
        <b>Invoice No:</b> {{ $data['invoice_no'] }} <br>
        <b>Order ID:</b> {{ $data['order_id'] }} <br>
        <b>Date:</b> {{ $data['date'] }}
    </p>

    <hr>

    <p>
        <b>Bill To:</b><br>
        {{ $data['customer'] }}<br>
        {{ $data['address'] }} - {{ $data['pincode'] }}<br>
        Mobile: {{ $data['phone'] }}
    </p>

    <table>
        <tr>
            <th>#</th>
            <th>Description</th>
            <th>Qty</th>
            <th class="right">Net</th>
            <th class="right">GST 5%</th>
            <th class="right">Total</th>
        </tr>

        @php
            $net = round($data['amount'] / 1.05, 2);
            $gst = round($data['amount'] - $net, 2);
        @endphp

        <tr>
            <td class="center">1</td>
            <td>{{ $data['product'] }} ({{ $data['weight'] }} GM)</td>
            <td class="center">{{ $data['qty'] }}</td>
            <td class="right">₹{{ number_format($net, 2) }}</td>
            <td class="right">₹{{ number_format($gst, 2) }}</td>
            <td class="right">₹{{ number_format($data['amount'], 2) }}</td>
        </tr>
    </table>

    <p class="right"><b>Total Amount: ₹{{ number_format($data['amount'], 2) }}</b></p>

    <p>
        <b>Authorized Signatory</b><br>
        For {{ $seller->seller_name }}
    </p>
