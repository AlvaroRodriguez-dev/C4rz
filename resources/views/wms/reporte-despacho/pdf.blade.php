<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <style>
        body {
            font-family: 'Helvetica', sans-serif;
            font-size: 11px;
            color: #222;
        }

        .encabezado {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            margin-bottom: 14px;
        }

        .logo {
            max-height: 50px;
        }

        h1 {
            font-size: 16px;
            margin-bottom: 2px;
        }

        .subtitulo {
            color: #555;
            font-size: 11px;
        }

        .meta {
            color: #777;
            font-size: 10px;
            margin-top: 2px;
        }

        h2 {
            font-size: 13px;
            color: #1F4E79;
            margin-top: 18px;
            margin-bottom: 6px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4px;
        }

        th,
        td {
            border: 1px solid #ccc;
            padding: 5px 7px;
            text-align: left;
            font-size: 10px;
            vertical-align: top;
        }

        th {
            background-color: #1F4E79;
            color: white;
        }

        tr:nth-child(even) {
            background-color: #f7f7f7;
        }

        .completo {
            color: #2E7D32;
            font-weight: bold;
        }

        .parcial {
            color: #C55A11;
            font-weight: bold;
        }

        .excepcion {
            color: #1F4E79;
            font-weight: bold;
            font-size: 9px;
        }

        .total-final {
            text-align: right;
            font-size: 12px;
            font-weight: bold;
            margin-top: 10px;
        }
    </style>
</head>

<body>
    <div class="encabezado">
        <img src="{{ public_path('images/faboce2.png') }}" class="logo" alt="Faboce">
        <div style="text-align: right;">
            <h1>REPORTE DE NOTA DESPACHADA</h1>
            <p class="subtitulo">Nota Tipo {{ $tipo_registro }} · N° {{ $id_registro }}
                @if ($glosa)
                    · {{ $glosa }}
                @endif
            </p>
            <p class="meta">Generado: {{ $generado }}</p>
        </div>
    </div>

    <h2>Resumen por Producto</h2>
    <table>
        <thead>
            <tr>
                <th>Código</th>
                <th>Descripción</th>
                <th>Despachado</th>
                <th>Autorizado</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($totales_por_producto as $t)
                <tr>
                    <td>{{ $t['codigo'] }}</td>
                    <td>{{ $t['descripcion'] }}{{ $t['tuvo_excepcion'] ? ' — Cambio de Lote' : '' }}</td>
                    <td>{{ $t['total_despachado'] }}</td>
                    <td>{{ $t['total_autorizado'] }}</td>
                    <td class="{{ $t['completo'] ? 'completo' : 'parcial' }}">
                        {{ $t['completo'] ? 'COMPLETO' : 'PARCIAL' }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <h2>Detalle de Despachos Realizados</h2>
    <table>
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Código</th>
                <th>Lote Solicitado</th>
                <th>Lote Aplicado</th>
                <th>Pallet</th>
                <th>Galpón</th>
                <th>Ubicación</th>
                <th>Cantidad</th>
                <th>Usuario</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($lineas as $l)
                <tr>
                    <td>{{ $l['fecha'] }}</td>
                    <td>{{ $l['codigo'] }}</td>
                    <td>{{ $l['lote_solicitado'] }}</td>
                    <td>
                        {{ $l['lote_aplicado'] }}
                        @if ($l['es_excepcion'])
                            <br><span class="excepcion">CAMBIO DE LOTE</span>
                        @endif
                    </td>
                    <td>{{ $l['pallet'] }}</td>
                    <td>{{ $l['galpon'] }}</td>
                    <td>{{ $l['ubicacion'] }}</td>
                    <td>{{ $l['cantidad'] }}</td>
                    <td>{{ $l['usuario'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <p class="total-final">Total General Despachado: {{ $total_general }} cajas</p>

    @if (count($lineas_en_camino) > 0)
        <h2>O.T. — Órdenes de Trabajo Pendientes de Verificación</h2>
        <table>
            <thead>
                <tr>
                    <th>OT</th>
                    <th>Código</th>
                    <th>Lote Solicitado</th>
                    <th>Lote Aplicado</th>
                    <th>Pallet</th>
                    <th>Galpón</th>
                    <th>Ubicación</th>
                    <th>Cantidad</th>
                    <th>Generada</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($lineas_en_camino as $l)
                    <tr>
                        <td>#{{ $l['orden_trabajo_id'] }}</td>
                        <td>{{ $l['codigo'] }}</td>
                        <td>{{ $l['lote_solicitado'] }}</td>
                        <td>
                            {{ $l['lote_aplicado'] }}
                            @if ($l['es_excepcion'])
                                <br><span class="excepcion">CAMBIO DE LOTE</span>
                            @endif
                        </td>
                        <td>{{ $l['pallet'] }}</td>
                        <td>{{ $l['galpon'] }}</td>
                        <td>{{ $l['ubicacion'] }}</td>
                        <td>{{ $l['cantidad'] }}</td>
                        <td>{{ $l['fecha'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <p style="font-size: 9px; color: #C55A11; margin-top: 4px;">
            ** Estas cantidades aún NO descontaron stock real: están reservadas en Órdenes de Trabajo pendientes de
            verificación física por el montacarguista.
        </p>
    @endif

    @if ($total_en_camino > 0)
        <p class="total-final" style="color: #C55A11;">Total En OT (sin verificar): {{ $total_en_camino }} cajas
        </p>
    @endif

</body>

</html>
