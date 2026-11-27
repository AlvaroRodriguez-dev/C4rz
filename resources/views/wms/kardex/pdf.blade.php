<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
@page { margin: 24px 28px; }
body { font-family: DejaVu Sans, sans-serif; color:#1f2937; font-size:8.5pt; }
h1 { font-size:18px; margin:0 0 4px; }
.sub { color:#64748b; font-size:9px; margin-bottom:12px; }
.meta { margin-bottom:12px; font-size:9pt; }
table { width:100%; border-collapse:collapse; margin-top:10px; }
th { background:#e5e7eb; text-align:center; padding:6px; border:1px solid #cbd5e1; }
td { padding:5px; border:1px solid #d1d5db; }
tr:nth-child(even) { background:#f8fafc; }
.num { text-align:right; }
thead { display:table-header-group; }
tr { page-break-inside:avoid; }
.footer { margin-top:12px; color:#64748b; font-size:8px; }
</style>
</head>
<body>
<table style="width:100%;border:0;margin:0 0 8px"><tr>
<td style="width:150px;border:0;vertical-align:top"><img src="{{ public_path('images/faboce2.png') }}" style="width:125px"></td>
<td style="border:0;vertical-align:top"><h1>Kardex de inventario WMS</h1><div class="sub">FABOCE · Generado: {{ $generado ?? '' }} · Usuario: {{ $usuario ?? '' }}</div></td>
</tr></table>
<div class="meta"><strong>Almacén:</strong> {{ $nombre_almacen }} &nbsp; <strong>Producto:</strong> {{ $codigo }} &nbsp; <strong>Periodo:</strong> {{ $desde }} al {{ $hasta }}</div>
<table>
<thead><tr><th>Fecha</th><th>Documento</th><th>Lote</th><th>Ubicación</th><th>Entrada</th><th>Salida</th><th>Saldo</th></tr></thead>
<tbody>
@foreach($filas as $f)
<tr><td>{{ $f['fecha'] }}</td><td>{{ $f['documento'] }}</td><td>{{ $f['clote'] }}</td><td>{{ $f['detalle_ubicacion'] }}</td><td class="num">{{ $f['entrada'] ?? '' }}</td><td class="num">{{ $f['salida'] ?? '' }}</td><td class="num">{{ $f['saldo'] }}</td></tr>
@endforeach
</tbody>
</table>
<div class="footer">Reporte generado desde el módulo WMS · Kardex de inventario</div>
</body>
</html>