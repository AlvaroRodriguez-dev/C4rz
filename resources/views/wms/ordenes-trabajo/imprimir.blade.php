<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: 'Helvetica', sans-serif; font-size: 12px; color: #222; }
        .encabezado { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 16px; }
        .logo { max-height: 20px; }
        h1 { font-size: 18px; margin-bottom: 2px; }
        .subtitulo { color: #555; margin-bottom: 4px; font-size: 12px; }
        .meta { color: #777; font-size: 11px; margin-bottom: 18px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ccc; padding: 6px 8px; text-align: left; font-size: 11px; vertical-align: top; }
        th { background-color: #1F4E79; color: white; }
        tr:nth-child(even) { background-color: #f7f7f7; }
        .estado-verificado { color: #2E7D32; font-weight: bold; }
        .estado-pendiente { color: #C0392B; font-weight: bold; }
        .fecha-check { color: #555; font-size: 10px; display: block; }
        .firma { margin-top: 50px; display: flex; justify-content: space-between; }
        .firma div { width: 45%; text-align: center; border-top: 1px solid #333; padding-top: 6px; font-size: 11px; }
        .total-row { font-weight: bold; background-color: #e8e8e8 !important; }
        .total-row td { border-top: 2px solid #1F4E79; }
        .firma-izquierda { text-align: left !important; }
        .firma-derecha { text-align: right !important; }
    </style>
</head>
<body>
    <div class="encabezado">
        <img src="{{ public_path('images/faboce2.png') }}" class="logo" alt="Faboce">
        <div style="text-align: center;">
            <h1>ORDEN DE TRABAJO N° {{ $ordenTrabajo->id }}</h1>
            <p class="subtitulo">Nota Tipo {{ $ordenTrabajo->tipo_registro }} · N° {{ $ordenTrabajo->id_registro }}
                @if ($ordenTrabajo->glosa) · {{ $ordenTrabajo->glosa }} @endif
            <br>
                Generada: {{ $ordenTrabajo->created_at->format('d/m/Y H:i') }}
                · Estado: {{ $ordenTrabajo->estado === 'completada' ? 'COMPLETADA' : 'PENDIENTE' }}
                · Impreso: {{ now()->format('d/m/Y H:i') }}
            </p>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Pallet</th>
                <th>Producto</th>
                <th>Galpón</th>
                <th>Ubicación</th>
                <th>Lote</th>
                <th>Cantidad</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($detalles as $d)
                <tr>
                    <td>{{ $d->pallet }}</td>
                    <td>{{ trim("{$d->descrip} {$d->descrip1}") }}</td>
                    <td>{{ $d->galpon_origen }}</td>
                    <td>{{ $d->ubicacion_origen }}</td>
                    <td>{{ $d->clote ?? 'S/L' }}</td>
                    <td>{{ $d->cantidad }}</td>
                    <td>
                        @if ($d->chequeado)
                            <span class="estado-verificado">VERIFICADO</span>
                            <span class="fecha-check">{{ optional($d->chequeado_at)->format('d/m/Y H:i') }}</span>
                            <span class="fecha-check">{{ $d->chequeadoPor->name ?? '' }}</span>
                        @else
                            <span class="estado-pendiente">PENDIENTE</span>
                        @endif
                    </td>
                </tr>
            @endforeach
            <!-- Fila de total -->
            <tr class="total-row">
                <td colspan="5" style="text-align: right; font-weight: bold;">TOTAL CANTIDAD:</td>
                <td style="font-weight: bold;">{{ $detalles->sum('cantidad') }}</td>
                <td></td>
            </tr>
        </tbody>
    </table>

    <!-- Firmas en la misma línea: izquierda y derecha -->
    <div style="margin-top: 50px; display: flex; justify-content: space-between; align-items: center;">
        <div style="flex: 1; border-top: 1px solid #333; padding-top: 6px; text-align: left;">
            Entregado por (Almacén)
        </div>
        <div style="flex: 1; border-top: 1px solid #333; padding-top: 6px; text-align: right;">
            Recibido/Verificado por (Montacarguista)
        </div>
    </div>
</body>
</html>