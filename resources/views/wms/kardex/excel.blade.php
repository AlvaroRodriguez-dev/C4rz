<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"><style>
body{font-family:Arial,sans-serif;font-size:10pt}h2,h3{text-align:center}table{border-collapse:collapse;width:100%}th,td{border:1px solid #333;padding:5px}th{background:#e5e7eb;font-weight:bold;text-align:center}.num{text-align:right}.meta{margin-bottom:12px}
</style></head><body>
<h2>KARDEX DE INVENTARIO</h2>
<div class="meta"><strong>Almacén:</strong> {{ $nombre_almacen }} &nbsp; <strong>Producto:</strong> {{ $codigo }} &nbsp; <strong>Periodo:</strong> {{ $desde }} al {{ $hasta }}</div>
<table><thead><tr><th>Fecha</th><th>Documento</th><th>Lote</th><th>Ubicación</th><th>Entrada</th><th>Salida</th><th>Saldo</th></tr></thead><tbody>
@foreach($filas as $f)<tr><td>{{ $f['fecha'] }}</td><td>{{ $f['documento'] }}</td><td>{{ $f['clote'] }}</td><td>{{ $f['detalle_ubicacion'] }}</td><td class="num">{{ $f['entrada'] ?? '' }}</td><td class="num">{{ $f['salida'] ?? '' }}</td><td class="num">{{ $f['saldo'] }}</td></tr>@endforeach
</tbody></table>
</body></html>