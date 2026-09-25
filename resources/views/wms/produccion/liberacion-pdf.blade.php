<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>RG-CB-36 - Liberación de Producción</title>
    <style>
        @page { margin: 28px 30px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #111827; }
        .header { border: 1px solid #111827; margin-bottom: 12px; }
        .header table, .data table, .detail { width: 100%; border-collapse: collapse; }
        .header td { border-right: 1px solid #111827; padding: 7px; vertical-align: middle; }
        .header td:last-child { border-right: 0; }
        .title { font-size: 15px; font-weight: bold; text-align: center; }
        .code { font-size: 11px; font-weight: bold; text-align: center; }
        .section { background: #e5e7eb; border: 1px solid #9ca3af; border-bottom: 0; padding: 6px 8px; font-weight: bold; }
        .data { margin-bottom: 12px; }
        .data td { border: 1px solid #9ca3af; padding: 6px; }
        .label { font-size: 7px; color: #4b5563; text-transform: uppercase; }
        .value { font-size: 10px; font-weight: bold; margin-top: 2px; }
        .detail th { background: #e5e7eb; border: 1px solid #9ca3af; padding: 6px; font-size: 8px; }
        .detail td { border: 1px solid #9ca3af; padding: 6px; }
        .center { text-align: center; }
        .right { text-align: right; }
        .total { font-weight: bold; background: #f3f4f6; }
        .obs { min-height: 45px; border: 1px solid #9ca3af; padding: 7px; }
        .signatures { margin-top: 48px; }
        .signatures td { width: 33.33%; text-align: center; padding: 0 12px; }
        .line { border-top: 1px solid #111827; padding-top: 5px; margin-top: 28px; }
        .footer { margin-top: 18px; color: #6b7280; font-size: 7px; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <table>
            <tr>
                <td style="width:20%;">
                    <div class="label">Código de formulario</div>
                    <div class="code">RG-CB-36</div>
                </td>
                <td style="width:60%;">
                    <div class="title">LIBERACIÓN DE PRODUCCIÓN</div>
                    <div style="text-align:center; margin-top:3px;">Registro de entrega de producto terminado a Almacén</div>
                </td>
                <td style="width:20%;">
                    <div class="label">Documento WMS</div>
                    <div class="code">{{ $entrega->documento?->id_documento ?? '—' }}</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="section">DATOS DE LA LIBERACIÓN</div>
    <div class="data">
        <table>
            <tr>
                <td style="width:25%;">
                    <div class="label">Almacén operativo</div>
                    <div class="value">{{ $entrega->almacen?->codigo }} - {{ $entrega->almacen?->nombre }}</div>
                </td>
                <td style="width:18%;">
                    <div class="label">Fecha de entrega</div>
                    <div class="value">{{ optional($entrega->fecha_entrega)->format('d/m/Y') }}</div>
                </td>
                <td style="width:18%;">
                    <div class="label">Turno / Hora</div>
                    <div class="value">{{ $entrega->turno_hora ?: '—' }}</div>
                </td>
                <td style="width:19%;">
                    <div class="label">Folio físico RG-CB-36</div>
                    <div class="value">{{ $entrega->folio_fisico ?: '—' }}</div>
                </td>
                <td style="width:20%;">
                    <div class="label">Formato</div>
                    <div class="value">{{ $entrega->formato ?: '—' }}</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="section">DETALLE DE PRODUCCIÓN</div>
    <table class="detail">
        <thead>
            <tr>
                <th style="width:4%;">N°</th>
                <th style="width:29%;">PRODUCTO / MODELO</th>
                <th style="width:13%;">CÓDIGO</th>
                <th style="width:10%;">CALIDAD</th>
                <th style="width:11%;">CANTIDAD</th>
                <th style="width:10%;">TONO</th>
                <th style="width:9%;">CALIBRE</th>
                <th style="width:14%;">OBSERVACIÓN</th>
            </tr>
        </thead>
        <tbody>
            @foreach($entrega->detalles as $detalle)
                <tr>
                    <td class="center">{{ $detalle->orden }}</td>
                    <td>{{ $detalle->descripcion }}</td>
                    <td style="font-size:7px;">{{ $detalle->codigo }}</td>
                    <td class="center">{{ $detalle->calidad }}</td>
                    <td class="right">{{ number_format($detalle->cantidad_declarada, 0, ',', '.') }}</td>
                    <td class="center">{{ $detalle->tono ?? '—' }}</td>
                    <td class="center">{{ $detalle->calibre ?? '—' }}</td>
                    <td>{{ $detalle->observacion ?: '—' }}</td>
                </tr>
            @endforeach
            <tr class="total">
                <td colspan="4" class="right">TOTAL GENERAL DECLARADO</td>
                <td class="right">{{ number_format($entrega->total_declarado, 0, ',', '.') }}</td>
                <td colspan="3"></td>
            </tr>
        </tbody>
    </table>

    <div class="section" style="margin-top:12px;">OBSERVACIONES</div>
    <div class="obs">{{ $entrega->observaciones ?: 'Sin observaciones.' }}</div>

    <table class="signatures">
        <tr>
            <td><div class="line">PRODUCCIÓN / CALIDAD</div></td>
            <td><div class="line">ENTREGA</div></td>
            <td><div class="line">RECEPCIÓN ALMACÉN</div></td>
        </tr>
    </table>

    <div class="footer">
        Documento generado desde WMS · {{ now()->format('d/m/Y H:i') }} · Estado: {{ $entrega->estado }}
    </div>
</body>
</html>
