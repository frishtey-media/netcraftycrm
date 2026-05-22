<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">

    <style>
        * {
            box-sizing: border-box;
        }

        @page {
            margin: 0.2in;
        }

        body {
            margin: 0;
            padding: 0;

            font-family: Arial, sans-serif;
        }

        table {
            width: 100%;

            border-collapse: separate;

            border-spacing: 0.05in;
        }

        td {

            width: 2.28in;

            height: 0.72in;

            padding: 0;

            text-align: center;

            vertical-align: middle;
        }

        .label {

            width: 2.2in;

            height: 0.65in;
            margin: 5px;
            padding: 5px;
            border: 1px solid #cfcfcf;

            padding: 0.03in;

            overflow: hidden;

            margin: auto;
        }

        .barcode-img {

            width: 1.9in;

            height: 0.30in;
            margin: 5px;
            padding: 5px;
            display: block;

            margin: 0 auto;
        }

        .barcode-text {

            font-size: 10px;

            font-weight: bold;

            margin-top: 0.03in;

            letter-spacing: 1px;

            text-align: center;

            line-height: 10px;
        }
    </style>

</head>

<body>

    <table>

        @foreach ($barcodes->chunk(5) as $chunk)
            <tr>

                @foreach ($chunk as $barcode)
                    <td>

                        <div class="label">

                            <img class="barcode-img"
                                src="data:image/png;base64,{{ DNS1D::getBarcodePNG($barcode->barcode, 'C128') }}">

                            <div class="barcode-text">
                                {{ $barcode->barcode }}
                            </div>

                        </div>

                    </td>
                @endforeach

            </tr>
        @endforeach

    </table>

</body>

</html>
