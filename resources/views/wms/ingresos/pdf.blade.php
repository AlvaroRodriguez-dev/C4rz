<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 28px 30px 35px; }
        body { font-family: Helvetica, sans-serif; color: #222; font-size: 10px; }
        .header, .meta, .footer { width: 100%; border-collapse: collapse; }
        .header { margin-bottom: 18px; }
        .header td, .footer td { border: 0; vertical-align: top; padding: 0; }
        .logo { max-height: 48px; }
        h1 { font-size: 17px; margin: 0 0 5px; text-align: right; }
        .subtitle { text-align: right; color: #555; font-size: 10px; }
        .meta { margin-top: 12px; }
        .meta td { border: 1px solid #bbb; padding: 7px; }
        .label { font-size: 8px; color: #666; text-transform: uppercase; }
        h2 { color: #1F4E79; font-size: 12px; margin: 18px 0 7px; }
        table.detail { width: 100%; border-collapse: collapse; margin-top: 5px; }
        .detail th, .detail td { border: 1px solid #bbb; padding: 5px; font-size: 8.5px; vertical-align: top; }
        .detail th { background: #1F4E79; color: #fff; text-align: left; }
        .detail tr:nth-child(even) { background: #f5f7f9; }
        .total { text-align: right; font-weight: bold; font-size: 11px; margin-top: 10px; }
        .footer { margin-top: 35px; }
        .footer td { width: 50%; text-align: center; padding-top: 28px; }
        .line { border-top: 1px solid #555; padding-top: 5px; margin: 0 25px; }
        .generated { color: #777; font-size: 8px; text-align: right; margin-top: 20px; }
    </style>
</head>
<body>
    <table class="header">
        <tr>
            <td><img src="{{ public_path('images/faboce2.png') }}" class="logo" alt="FABOCE"></td>
            <td>
                <h1>NOTA DE INGRESO A ALMACÉN</h1>
                <div class="subtitle">Documento consolidado de ingreso WMS</div>
            </td>
        </tr>
    </table>

    <table class="meta">
        <tr>
            <td><div class="label">Documento de referencia</div><strong>{{ $docOrigen }}</strong></td>
            <td><div class="label">Tipo de ingreso</div><strong>{{ strtoupper($tipoIngreso ?? '—') }}</strong></td>
            <td><div class="label">Almacén</div><strong>{{ $almacen?->codigo ?? '—' }} · {{ $almacen?->nombre ?? '—' }}</strong></td>
            <td><div class="label">Fecha de emisión</div><strong>{{ $generado }}</strong></td>
        </tr>
    </table>

    <h2>Detalle de productos ingresados</h2>
    <table class="detail">
        <thead>
            <tr>
                <th>Código</th><th>Descripción</th><th>Descripción adicional</th>
                <th>Lote</th><th>Galpón</th><th>Ubicación</th><th>Fecha / hora de registro</th><th>Registrado por</th><th>Cantidad</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($ingresos as $ingreso)
                <tr>
                    <td>{{ $ingreso->codigo }}</td>
                    <td>{{ $ingreso->descrip }}</td>
                    <td>{{ $ingreso->descrip1 }}</td>
                    <td>{{ $ingreso->clote ?: '—' }}</td>
                    <td>{{ $ingreso->galpon }}</td>
                    <td>{{ $ingreso->ubicacion }}</td>
                    <td>{{ optional($ingreso->created_at)->format('d/m/Y H:i:s') }}</td>
                    <td>{{ $ingreso->creador->name ?? 'N/D' }}</td>
                    <td style="text-align:right">{{ number_format((int) $ingreso->cantidad, 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <p class="total">Total ingresado: {{ number_format((int) $totalCantidad, 0, ',', '.') }} cajas</p>

    <table class="footer">
        <tr><td><div class="line">Responsable de almacén</div></td><td><div class="line">Conformidad / recepción</div></td></tr>
    </table>
    <p class="generated">Documento generado por WMS · {{ $generado }}</p>
</body>
</html>
