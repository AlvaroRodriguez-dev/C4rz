<!DOCTYPE html>
<html lang="es"><head><meta charset="UTF-8"><style>
@page{margin:22px}body{font-family:DejaVu Sans,sans-serif;font-size:8.5pt;color:#111}h2{text-align:center;margin-bottom:8px}.meta{margin-bottom:12px;font-size:9pt}table{width:100%;border-collapse:collapse}th,td{border:1px solid #555;padding:4px}th{background:#e5e7eb;text-align:center;font-weight:bold}.num{text-align:right}thead{display:table-header-group}tr{page-break-inside:avoid}
</style></head><body>
<h2>KARDEX DE INVENTARIO</h2>
<div class="meta"><strong>Almacén:</strong> {{ $nombre_almacen }} &nbsp; <strong>Producto:</strong> {{ $codigo }} &nbsp; <strong>Periodo:</strong> {{ $desde }} al {{ $hasta }}</div>
<table><thead><tr><th>Fecha</th><th>Documento</th><th>Lote</th><th>Ubicación</th><th>Entrada</th><th>Salida</th><th>Saldo</th></tr></thead><tbody>
@foreach($filas as $f)<tr><td>{{ $f['fecha'] }}</td><td>{{ $f['documento'] }}</td><td>{{ $f['clote'] }}</td><td>{{ $f['detalle_ubicacion'] }}</td><td class="num">{{ $f['entrada'] ?? '' }}</td><td class="num">{{ $f['salida'] ?? '' }}</td><td class="num">{{ $f['saldo'] }}</td></tr>@endforeach
</tbody></table>
</body></html>