<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        @page {
            size: 80mm 80mm;
            margin: 0;
        }

        html, body {
            width: 80mm;
            margin: 0;
            padding: 0;
            background: #fff;
        }

        .etiqueta {
            width: 80mm;
            height: 80mm;
            box-sizing: border-box;
            padding: 5mm;
            text-align: center;
            page-break-after: always;
            break-after: page;
            overflow: hidden;
        }

        .etiqueta:last-child {
            page-break-after: auto;
            break-after: auto;
        }

        .qr {
            width: 54mm;
            height: 54mm;
            margin: 0 auto 4mm auto;
        }

        .numero {
            font-family: DejaVu Sans, sans-serif;
            font-size: 15pt;
            font-weight: bold;
            line-height: 1.1;
            white-space: nowrap;
        }
    </style>
</head>
<body>
@foreach($entrega->hu as $hu)
    @php
        $qrUrl = 'https://quickchart.io/qr?size=500&margin=0&ecLevel=M&text=' . urlencode($hu->numero);
    @endphp

    <div class="etiqueta">
        <img class="qr" src="{{ $qrUrl }}" alt="QR {{ $hu->numero }}">
        <div class="numero">{{ $hu->numero }}</div>
    </div>
@endforeach
</body>
</html>
