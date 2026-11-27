<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8">
<style>
body{font-family:Arial,sans-serif;color:#1f2937;font-size:11px}
table{border-collapse:collapse;width:100%;margin:10px 0}
th{background:#e5e7eb;border:1px solid #cbd5e1;padding:6px;text-align:left}
td{border:1px solid #d1d5db;padding:5px}
.cards td{background:#f1f5f9;border:0;padding:8px}
.num{font-size:16px;font-weight:bold}
.right{text-align:right}
h1{font-size:20px;margin:0}
.subtitle{color:#64748b;font-size:11px}
</style>
</head>
<body>
<table style="border:0"><tr>
<td style="border:0;width:160px">@if($logoBase64)<img src="{{ $logoBase64 }}" width="140">@endif</td>
<td style="border:0"><h1>Inventario físico WMS</h1><div class="subtitle">FABOCE · Generado: {{ $generado ?? '' }} · Usuario: {{ $usuario ?? '' }}</div></td>
</tr></table>
<table class="cards"><tr>
<td><div>Total cajas</div><div class="num">{{ $total_cajas ?? 0 }}</div></td>
<td><div>Total pallets</div><div class="num">{{ $total_pallets ?? 0 }}</div></td>
<td><div>Total ubicaciones</div><div class="num">{{ $total_ubicaciones ?? 0 }}</div></td>
</tr></table>
@if(!empty($filtros))
<p><b>Filtros:</b> @foreach($filtros as $k=>$v) @if($v) {{ ucfirst($k) }}: {{ $v }} · @endif @endforeach</p>
@endif
@if(!empty($resumen_almacenes))
<h3>Resumen por almacén</h3>
<table><thead><tr><th>Almacén</th><th class="right">Cajas</th><th class="right">Pallets</th><th class="right">Ubicaciones</th></tr></thead><tbody>
@foreach($resumen_almacenes as $a)<tr><td>{{ $a['almacen'] }}</td><td class="right">{{ $a['cajas'] }}</td><td class="right">{{ $a['pallets'] }}</td><td class="right">{{ $a['ubicaciones'] }}</td></tr>@endforeach
</tbody></table>
@endif
<h3>Detalle de inventario ({{ count($detalle ?? []) }} registros)</h3>
<table><thead><tr><th>Almacén</th><th>Galpón</th><th>Ubicación</th><th>Pallet</th><th>Código / Producto</th><th>Lote</th><th class="right">Saldo</th></tr></thead><tbody>
@forelse($detalle ?? [] as $d)
<tr><td>{{ $d['almacen'] }}</td><td>{{ $d['galpon'] }}</td><td>{{ $d['ubicacion'] }}</td><td>{{ $d['pallet'] }}</td><td>{{ $d['codigo'] }} · {{ $d['descrip'] }}</td><td>{{ $d['lote'] }}</td><td class="right">{{ $d['saldo'] }}</td></tr>
@empty
<tr><td colspan="7">No se encontraron registros.</td></tr>
@endforelse
</tbody></table>
<p class="subtitle">Reporte generado desde el módulo WMS · Inventario físico</p>
</body></html>