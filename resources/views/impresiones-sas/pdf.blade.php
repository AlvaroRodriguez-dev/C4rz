<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Transito-{{ $cabecera['id'] ?? 'documento' }}</title>
    <style>
        @page {
            margin: 10mm;
        }

        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 7px;
            color: #111;
            margin: 0;
        }

        .header {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 3mm;
        }

        .header td {
            vertical-align: top;
        }

        .logo {
            width: 24%;
            padding-top: 1mm;
        }

        .logo-img {
            max-width: 34mm;
            max-height: 16mm;
        }

        .title {
            width: 52%;
            text-align: center;
        }

        .title-main {
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .title-sub {
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .document {
            width: 24%;
            text-align: right;
            font-size: 12px;
            font-weight: bold;
        }

        .info {
            width: 100%;
            border: 0.5px solid #444;
            border-collapse: collapse;
            margin-bottom: 3mm;
            font-size: 7px;
        }

        .info td {
            padding: 1.2mm 1.5mm;
            border: 0;
        }

        .label {
            font-weight: bold;
            white-space: nowrap;
        }

        .details {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            font-size: 6px;
        }

        .details th,
        .details td {
            border: 0.4px solid #333;
            padding: 1.15mm 0.8mm;
            vertical-align: middle;
        }

        .details th {
            text-align: center;
            font-weight: bold;
            background: #eeeeee;
        }

        .center {
            text-align: center;
        }

        .right {
            text-align: right;
        }

        .product {
            text-align: left;
            word-wrap: break-word;
        }

        .totals td {
            font-weight: bold;
        }

        .usd td {
            font-weight: bold;
        }

        .signatures {
            width: 100%;
            border-collapse: collapse;
            margin-top: 17mm;
            font-size: 7px;
        }

        .signatures td {
            width: 33.33%;
            text-align: center;
            vertical-align: top;
            padding: 0 3mm;
        }

        .line {
            border-top: 0.5px solid #333;
            padding-top: 1mm;
        }

        .conformidad {
            margin-top: 6mm;
            font-size: 8px;
            line-height: 1.6;
        }

        .qq {
            font-size: 10px;
            font-weight: bold;
        }

        .observacion {
            width: 100%;
            margin-top: 2mm;
            border-collapse: collapse;
            font-size: 7px;
        }

        .observacion td {
            border: 0.5px solid #333;
            height: 18mm;
            padding: 1.5mm;
            vertical-align: top;
        }

        tr {
            page-break-inside: avoid;
        }
    </style>
</head>
<body>

@php
    $fmt = function ($value) {
        if ($value === null || $value === '') {
            return '';
        }

        return number_format((float) str_replace(',', '', $value), 2, '.', ',');
    };

    $productoCompleto = function ($fila) {
        return trim(
            ($fila['producto'] ?? '') .
            ' - ' .
            ($fila['descrip1'] ?? '') .
            ' - ' .
            ($fila['lote'] ?? '')
        );
    };
@endphp

<table class="header">
    <tr>
        <td class="logo"><img src="{{ public_path('images/faboce2.png') }}" class="logo-img" alt="Faboce"></td>

        <td class="title">
            <div class="title-main">{{ $cabecera['titulo'] ?? 'DESPACHO DE PRODUCTO TERMINADO' }}</div>
            <div class="title-sub">{{ $cabecera['subtitulo'] ?? '' }}</div>
        </td>

        <td class="document">{{ $cabecera['id'] ?? '' }}</td>
    </tr>
</table>

<table class="info">
    <tr>
        <td width="10%" class="label">Lugar y fecha:</td>
        <td width="40%">{{ $cabecera['fechad'] ?? '' }}</td>
        <td width="10%" class="label">Ag. Origen:</td>
        <td width="40%">{{ $cabecera['origen'] ?? '' }}</td>
    </tr>
    <tr>
        <td class="label">Empresa:</td>
        <td>{{ $cabecera['empresa'] ?? trim(($cabecera['nit'] ?? '') . ' - ' . ($cabecera['razon_social'] ?? '')) }}</td>
        <td class="label">Ag. Destino:</td>
        <td>{{ $cabecera['destino'] ?? '' }}</td>
    </tr>
    <tr>
        <td class="label">Transportista:</td>
        <td>{{ $cabecera['transportista'] ?? ($cabecera['nombre'] ?? '') }}</td>
        <td class="label">Cel.:</td>
        <td>{{ $cabecera['telefono'] ?? '' }}</td>
    </tr>
    <tr>
        <td class="label">Detalle Camion:</td>
        <td colspan="3">{{ $cabecera['camion'] ?? trim(($cabecera['placa'] ?? '') . ' - ' . ($cabecera['descripcionc'] ?? '')) }}</td>
    </tr>
</table>

<table class="details">
    <thead>
        <tr>
            <th width="9%">NOTA</th>
            <th width="5%">VJE</th>
            <th width="37%">PRODUCTO</th>
            <th width="8%">CJAS.</th>
            <th width="8%">ACUM.</th>
            <th width="8%">ENTR.</th>
            <th width="8%">SALDO</th>
            <th width="8%">M2</th>
            <th width="9%">COSTO $US</th>
        </tr>
    </thead>

    <tbody>
        @foreach($detalles as $fila)
            <tr>
                <td class="center">{{ $fila['factnota'] ?? '' }}</td>
                <td class="center">{{ $fila['viaje'] ?? '' }}</td>
                <td class="product">{{ $productoCompleto($fila) }}</td>
                <td class="right">{{ $fmt($fila['facturada'] ?? '') }}</td>
                <td class="right">{{ $fmt($fila['acumulada'] ?? '') }}</td>
                <td class="right">{{ $fmt($fila['entregada'] ?? '') }}</td>
                <td class="right">{{ $fmt($fila['saldo'] ?? '') }}</td>
                <td class="right">{{ $fmt($fila['metros'] ?? '') }}</td>
                <td class="right">{{ $fmt($fila['impbs'] ?? '') }}</td>
            </tr>
        @endforeach

        <tr class="totals">
            <td colspan="3" class="right">TOTALES</td>
            <td class="right">{{ $fmt($totales['facturada'] ?? 0) }}</td>
            <td class="right">{{ $fmt($totales['acumulada'] ?? 0) }}</td>
            <td class="right">{{ $fmt($totales['entregada'] ?? 0) }}</td>
            <td class="right">{{ $fmt($totales['saldo'] ?? 0) }}</td>
            <td class="right">{{ $fmt($totales['metros'] ?? 0) }}</td>
            <td class="right">{{ $fmt($totales['impbs'] ?? 0) }}</td>
        </tr>
    </tbody>
</table>

<table class="signatures">
    <tr>
        <td>
            <div class="line">CHOFER: {{ $cabecera['nombre'] ?? '' }}</div>
            <div>PLACA: {{ $cabecera['placa'] ?? '' }}</div>
            <div>EMPRESA TRANSPORTE: {{ $cabecera['nit'] ?? '' }}</div>
            <div>{{ $cabecera['razon_social'] ?? 'FABOCE S.R.L' }}</div>
        </td>

        <td>
            <div class="line">ENCARGADO DE ALMACEN</div>
            <div>{{ strtoupper($cabecera['usuario'] ?? '') }}</div>
        </td>

        <td>
            <div class="line">SELLO Y FIRMA DE LA EMPRESA</div>
            <div>NOMBRE(S) Y APELLIDO(S): {{ $cabecera['nombre_receptor'] ?? '____________________' }}</div>
            <div>EMPRESA TRANSPORTE: {{ $cabecera['nit'] ?? '' }}</div>
            <div>
                C.I.: {{ $cabecera['carnet_identidad'] ?? '_________________' }}
                &nbsp;&nbsp;
                TELEFONO: {{ $cabecera['telefono_firma'] ?? ($cabecera['telefono'] ?? '______________') }}
            </div>
        </td>
    </tr>
</table>

<div class="conformidad">
    RECIBI LA MERCADERIA DESCRITA DE
    <span class="qq">{{ $cabecera['quintales'] ?? '' }} QQ.</span>
    COMPROMETIENDOME A ENTREGARLA EN PERFECTO ESTADO AL DESTINO
    <br>
    EN _______ DIAS, ASUMIENDO LA RESPONSABILIDAD POR PERDIDA O ROTURA QUE SERAN DEDUCIBLES DEL FLETE.
</div>

<div style="margin-top: 2mm; font-size: 7px;">
    <strong>OBSERVACION:</strong>
    Una vez firmada la conformidad no se aceptan reclamos
</div>

<table class="observacion">
    <tr>
        <td>{{ strtoupper($cabecera['observacion'] ?? ($cabecera['glosa'] ?? '')) }}</td>
    </tr>
</table>

</body>
</html>
