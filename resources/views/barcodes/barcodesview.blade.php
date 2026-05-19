<!DOCTYPE html>
<html>

<head>

    <meta charset="utf-8">

    <title>Barcodes</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }

        .sheet {
            width: 100%;
            overflow: hidden;
        }

        /* 4 Columns */
        .label {
            width: 23%;
            height: 73px;

            border: 1px solid #dcdcdc;

            float: left;

            margin: 5px;
            padding: 5px;

            text-align: center;

            box-sizing: border-box;
        }

        .barcode-text {
            font-size: 12px;
            margin-top: 2px;
            letter-spacing: 1px;
            font-weight: bold;
        }

        img {
            width: 230px;
            height: 42px;
        }

        .clearfix {
            clear: both;
        }
    </style>

</head>

<body>

    <div class="top-btn">

        <a href="{{ route('download.barcodes') }}" class="download-btn">
            Download Barcodes PDF
        </a>

    </div>

    <div class="sheet">

        @foreach ($barcodes as $barcode)
            <div class="label">

                <img src="data:image/png;base64,{{ DNS1D::getBarcodePNG($barcode->barcode, 'C128') }}">

                <div class="barcode-text">
                    {{ $barcode->barcode }}
                </div>

            </div>
        @endforeach

        <div class="clearfix"></div>

    </div>

</body>

</html>
