<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 35px 35px 45px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color:#222; }
        h1 { font-size:16px; margin:0 0 3px; }
        h2 { font-size:12px; margin:18px 0 7px; color:#1f4e79; }
        .meta { color:#666; font-size:8px; }
        table { width:100%; border-collapse:collapse; margin-top:6px; }
        th,td { border:1px solid #ccc; padding:5px; vertical-align:top; }
        th { background:#e5e7eb; text-align:left; }
        .json { white-space:pre-wrap; word-wrap:break-word; font-family:DejaVu Sans Mono, monospace; font-size:7px; }
        .audit { page-break-inside:avoid; margin-bottom:14px; }
    </style>
</head>
<body>
    <h1>AUDITORÍA POR DOCUMENTO — AUD X DOCTO</h1>
    <div class="meta">
        Tipo: {{ $tipo }} · Documento: {{ $documento }}<br>
        Origen: {{ $tabla_cabecera }} · {{ $tabla_detalle }}<br>
        Generado: {{ $generado_at }}
    </div>

    <h2>Datos de cabecera</h2>
    <table>
        <tbody>
            <tr>
                <th width="18%">Fecha</th>
                <td>{{ $fecha_campo ? ($header[$fecha_campo] ?? '—') : '—' }}</td>
                <th width="18%">Documento</th>
                <td>{{ $documento }}</td>
            </tr>
            <tr>
                <th>Glosa</th>
                <td colspan="3">{{ $glosa_campo ? ($header[$glosa_campo] ?? '—') : '—' }}</td>
            </tr>
            <tr>
                <th>Creado</th>
                <td>{{ $usuarios['created_id']['name'] ?? '—' }} ({{ $header['created_at'] ?? '—' }})</td>
                <th>Modificado</th>
                <td>{{ $usuarios['updated_id']['name'] ?? '—' }} ({{ $header['updated_at'] ?? '—' }})</td>
            </tr>
            <tr>
                <th>Eliminado</th>
                <td colspan="3">{{ $usuarios['deleted_id']['name'] ?? '—' }} ({{ $header['deleted_at'] ?? '—' }})</td>
            </tr>
        </tbody>
    </table>

    <h2>Registros de auditoría</h2>
    @forelse ($audits as $audit)
        <div class="audit">
            <table>
                <tr>
                    <th width="18%">Evento</th>
                    <td>{{ $audit['event'] ?? '—' }}</td>
                    <th width="18%">Usuario</th>
                    <td>{{ $audit['user_name'] ?? '—' }}</td>
                </tr>
                <tr>
                    <th>Fecha</th>
                    <td colspan="3">{{ $audit['created_at'] ?? '—' }}</td>
                </tr>
            </table>
            <table>
                <tr>
                    <th width="50%">OLD VALUES</th>
                    <th width="50%">NEW VALUES</th>
                </tr>
                <tr>
                    <td><div class="json">{{ $audit['old_values_json'] }}</div></td>
                    <td><div class="json">{{ $audit['new_values_json'] }}</div></td>
                </tr>
            </table>
        </div>
    @empty
        <p>No se encontraron registros en faboce2026.audits para el documento.</p>
    @endforelse
</body>
</html>
