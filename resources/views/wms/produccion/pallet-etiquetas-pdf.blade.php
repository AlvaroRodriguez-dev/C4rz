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
            overflow: hidden;
        }

        /*
         * DomPDF puede interpretar un page-break-after junto con un
         * elemento que ya ocupa exactamente una página como una página
         * adicional en blanco. Por eso el salto se coloca antes de cada
         * etiqueta, excepto la primera.
         */
        .etiqueta + .etiqueta {
            page-break-before: always;
            break-before: page;
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
