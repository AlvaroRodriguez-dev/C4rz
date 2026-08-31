<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: 'Helvetica', sans-serif; font-size: 12px; color: #222; }
        h1 { font-size: 18px; margin-bottom: 4px; }
        .subtitulo { color: #666; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ccc; padding: 6px 8px; text-align: left; font-size: 11px; }
        th { background-color: #1F4E79; color: white; }
        .firma { margin-top: 60px; display: flex; justify-content: space-between; }
        .firma div { width: 45%; text-align: center; border-top: 1px solid #333; padding-top: 6px; }
    </style>
    <title>CAMBIO DE LOTE</title>
</head>
<body>
    <h1>REGISTRO WMS DE CAMBIO DE LOTE</h1>
    <p class="subtitulo">Nota Tipo {{ $tipoRegistro }} · N° {{ $idRegistro }} </p>

    <p>Los siguientes productos fueron
    despachados desde un lote físico distinto al declarado originalmente en la nota de despacho.
    La factura/nota original no ha sido modificada.</p>

    <table>
        <thead>
            <tr>
                <th>Código</th>
                <th>Descripción</th>
                <th>Lote Solicitado</th>
                <th>Lote Despachado</th>
                <th>Cantidad</th>
                <th>Usuario</th>
                <th>Fecha</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($excepciones as $e)
                <tr>
                    <td>{{ $e->codigo }}</td>
                    <td>{{ $e->descrip }} {{ $e->descrip1 }}</td>
                    <td>{{ $e->lote_solicitado ?? 'S/L' }}</td>
                    <td>{{ $e->lote_aplicado }}</td>
                    <td>{{ $e->cantidad }}</td>
                    <td>{{ $e->creador->name ?? 'N/D' }}</td>
                    <td>{{ $e->created_at->format('d/m/Y H:i') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="firma">
        <div>Entregado por (Almacén)</div>
        <div><p>Recibido por (Transportista)</p></div>
    </div>
    <div>
        · Impreso: {{ now()->format('d/m/Y H:i') }}
    </div>
</body>
</html>