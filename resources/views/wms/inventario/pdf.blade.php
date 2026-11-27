<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
@page { margin: 24px 28px; }
body { font-family: DejaVu Sans, sans-serif; color:#1f2937; font-size:9px; }
h1 { font-size:18px; margin:0 0 4px; } .sub { color:#64748b; font-size:10px; margin-bottom:14px; }
table { width:100%; border-collapse:collapse; margin-top:12px; }
th { background:#e5e7eb; text-align:left; padding:6px; border:1px solid #cbd5e1; }
td { padding:5px; border:1px solid #d1d5db; }
tr:nth-child(even) { background:#f8fafc; }
.cards { width:100%; margin-bottom:10px; } .cards td { border:0; background:#f1f5f9; padding:8px; width:33%; }
.num { font-size:14px; font-weight:bold; } .right{text-align:right;} .footer { margin-top:12px; color:#64748b; }
</style>
</head>
<body>
<table style="width:100%;border:0;margin:0 0 8px"><tr><td style="width:150px;border:0;vertical-align:top"><img src="{{ public_path('images/faboce2.png') }}" style="width:125px"></td><td style="border:0;vertical-align:top"><h1>Inventario físico WMS</h1><div class="sub">FABOCE · Generado: {{ $generado ?? '' }} · Usuario: {{ $usuario ?? '' }}</div></td></tr></table>
<table class="cards"><tr>
<td><div>Total cajas</div><div class="num">{{ $total_cajas ?? 0 }}</div></td>
<td><div>Total pallets</div><div class="num">{{ $total_pallets ?? 0 }}</div></td>
<td><div>Total ubicaciones</div><div class="num">{{ $total_ubicaciones ?? 0 }}</div></td>
</tr></table>
@if(!empty($filtros))
<div><b>Filtros:</b>
@foreach($filtros as $k=>$v) @if($v) {{ ucfirst($k) }}: {{ $v }} · @endif @endforeach
</div>
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
<div class="footer">Reporte generado desde el módulo WMS · Inventario físico</div>
</body></html>