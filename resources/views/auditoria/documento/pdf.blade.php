<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 30px 25px 40px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 8px; color:#222; }
        h1 { font-size:15px; margin:0 0 3px; }
        h2 { font-size:11px; margin:16px 0 6px; color:#1f4e79; }
        .meta { color:#666; font-size:7px; }
        table { width:100%; border-collapse:collapse; margin-top:5px; }
        th,td { border:1px solid #ccc; padding:4px; vertical-align:top; }
        th { background:#e5e7eb; text-align:left; }
        .detail th, .detail td { font-size:6.5px; }
        .json { white-space:pre-wrap; word-wrap:break-word; font-family:DejaVu Sans Mono, monospace; font-size:6px; }
        .audit { page-break-inside:avoid; margin-bottom:12px; }
    </style>
</head>
<body>
    <h1>AUDITORÍA POR DOCUMENTO — AUD X DOCTO</h1>
    <div class="meta">Tipo: {{ $tipo }} · Documento: {{ $documento }}<br>Origen: {{ $tabla_cabecera }} · {{ $tabla_detalle }}<br>Generado: {{ $generado_at }}</div>
    <h2>Datos de cabecera</h2>
    <table><tbody>
        <tr><th width="18%">Fecha</th><td>{{ $fecha_campo ? ($header[$fecha_campo] ?? '—') : '—' }}</td><th width="18%">Documento</th><td>{{ $documento }}</td></tr>
        <tr><th>Glosa</th><td colspan="3">{{ $glosa_campo ? ($header[$glosa_campo] ?? '—') : '—' }}</td></tr>
        <tr><th>Creado</th><td>{{ $usuarios['created_id']['name'] ?? '—' }} ({{ $header['created_at'] ?? '—' }})</td><th>Modificado</th><td>{{ $usuarios['updated_id']['name'] ?? '—' }} ({{ $header['updated_at'] ?? '—' }})</td></tr>
        <tr><th>Eliminado</th><td colspan="3">{{ $usuarios['deleted_id']['name'] ?? '—' }} ({{ $header['deleted_at'] ?? '—' }})</td></tr>
    </tbody></table>
    <h2>Registros de detalle</h2>
    @php($esLogistica = in_array($tipo, ['Despachos', 'Programación', 'Tránsitos', 'Recepción'], true))
    @if ($detalle->isEmpty())
        <p>No existen registros de detalle para este documento.</p>
    @else
        <table class="detail"><thead><tr><th>Código</th><th>Producto</th>@if($esLogistica)<th>Cantidad factura</th><th>Cantidad despacho</th>@else<th>Cantidad</th>@endif<th>Creado por</th><th>Created at</th><th>Modificado por</th><th>Updated at</th><th>Eliminado por</th><th>Deleted at</th></tr></thead><tbody>
            @foreach ($detalle as $item)
                <tr>
                    <td>{{ $item['codigo'] ?? '—' }}</td><td>{{ $item['producto'] ?? '—' }}</td>
                    @if($esLogistica)<td>{{ $item['cantidad_factura'] ?? '—' }}</td><td>{{ $item['cantidad_despacho'] ?? '—' }}</td>@else<td>{{ $item['cantidad'] ?? '—' }}</td>@endif
                    <td>{{ $item['created_id_usuario'] ?? '—' }}@if(isset($item['created_id']) && $item['created_id'] !== null) <span>({{ $item['created_id'] }})</span>@endif</td>
                    <td>{{ $item['created_at'] ?? '—' }}</td><td>{{ $item['updated_id_usuario'] ?? '—' }}@if(isset($item['updated_id']) && $item['updated_id'] !== null) <span>({{ $item['updated_id'] }})</span>@endif</td>
                    <td>{{ $item['updated_at'] ?? '—' }}</td><td>{{ $item['deleted_id_usuario'] ?? '—' }}@if(isset($item['deleted_id']) && $item['deleted_id'] !== null) <span>({{ $item['deleted_id'] }})</span>@endif</td>
                    <td>{{ $item['deleted_at'] ?? '—' }}</td>
                </tr>
            @endforeach
        </tbody></table>
    @endif
    <h2>Registros de auditoría</h2>
    @forelse ($audits as $audit)
        <div class="audit"><table><tr><th width="18%">Evento</th><td>{{ $audit['event'] ?? '—' }}</td><th width="18%">Usuario</th><td>{{ $audit['user_name'] ?? '—' }}</td></tr><tr><th>Auditable Type</th><td colspan="3">{{ $audit['auditable_type'] ?? '—' }}</td></tr><tr><th>Fecha</th><td colspan="3">{{ $audit['created_at'] ?? '—' }}</td></tr></table><table><tr><th width="50%">OLD VALUES</th><th width="50%">NEW VALUES</th></tr><tr><td><div class="json">{{ $audit['old_values_json'] }}</div></td><td><div class="json">{{ $audit['new_values_json'] }}</div></td></tr></table></div>
    @empty
        <p>No se encontraron registros en faboce2026.audits para el documento.</p>
    @endforelse
</body>
</html>
