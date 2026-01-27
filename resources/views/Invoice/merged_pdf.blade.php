<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
        }

        .title {
            text-align: center;
            font-size: 20px;
            color: #154360;
            margin-bottom: 10px;
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
            background: #D6EAF8;
        }

        .no-border td {
            border: none;
        }

        .right {
            text-align: right;
        }

        .bold {
            font-weight: bold;
        }

        .bg {
            background: #EBF5FB;
        }

        .page-break {
            page-break-after: always;
        }
    </style>
</head>

<body>

    @foreach ($invoices as $inv)
        <div class="title">Tax Invoice</div>

        <table class="no-border">
            <tr>
                <td width="15%" class="bold">Sold By:</td>
                <td width="85%">{{ $seller->seller_name }}</td>
            </tr>
            <tr>
                <td></td>
                <td>{{ $seller->address }}</td>
            </tr>
            <tr>
                <td class="bold">GSTIN:</td>
                <td>{{ $seller->gst_no }}</td>
            </tr>
        </table>

        <br>

        <table>
            <tr class="bg">
                <td width="20%" class="bold">Billing Address:</td>
                <td>
                    {{ $inv['customer'] }}<br>
                    {{ $inv['address'] }} - {{ $inv['pincode'] }}<br>
                    Mobile: {{ $inv['phone'] }}
                </td>
            </tr>
        </table>

        <br>

        <table>
            <tr>
                <td class="bold">Order No</td>
                <td>{{ $inv['order_id'] }}</td>
                <td class="bold">Invoice No</td>
                <td>{{ $inv['invoice_no'] }}</td>
                <td class="bold">Invoice Date</td>
                <td>{{ $inv['date'] }}</td>
            </tr>
        </table>

        <br>

        <table>
            <tr>
                <th>S.No</th>
                <th>Description</th>
                <th>Qty</th>
                <th>Unit Price (₹)</th>
                <th>Net Amount (₹)</th>
                <th>GST %</th>
                <th>GST Amt (₹)</th>
                <th>Total Amt (₹)</th>
            </tr>
            <tr>
                <td>1</td>
                <td>{{ $inv['product'] }} ({{ $inv['weight'] }} GM)</td>
                <td>{{ $inv['qty'] }}</td>
                <td class="right">₹{{ number_format($inv['net'], 2) }}</td>
                <td class="right">₹{{ number_format($inv['net'], 2) }}</td>
                <td>5%</td>
                <td class="right">₹{{ number_format($inv['gst'], 2) }}</td>
                <td class="right">₹{{ number_format($inv['amount'], 2) }}</td>
            </tr>
        </table>

        <br>

        <table>
            <tr>
                <td class="bold">Net Amount</td>
                <td class="right">₹{{ number_format($inv['net'], 2) }}</td>
            </tr>
            <tr>
                <td class="bold">GST (5%)</td>
                <td class="right">₹{{ number_format($inv['gst'], 2) }}</td>
            </tr>
            <tr style="background:#AED6F1">
                <td class="bold">Total Amount</td>
                <td class="right bold">₹{{ number_format($inv['amount'], 2) }}</td>
            </tr>
        </table>

        <br>

        <p><b>Amount in Words:</b>
            {{ $inv['amount_words'] }}

        </p>

        <p class="bold">Whether tax is payable under reverse charge: No</p>
        <p>For <b>{{ $seller->seller_name }}</b>: Authorized Signatory</p>
        <h4 style="color:#154360">Thank you for your purchase!</h4>

        <div class="page-break"></div>
    @endforeach

</body>

</html>
