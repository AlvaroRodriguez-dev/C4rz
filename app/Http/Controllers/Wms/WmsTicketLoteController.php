<?php

namespace App\Http\Controllers\Wms;

use App\Http\Controllers\Controller;
use App\Models\WmsExcepcionDespacho;
use Barryvdh\DomPDF\Facade\Pdf;

class WmsTicketLoteController extends Controller
{
    public function descargar(string $tipoRegistro, string $idRegistro)
    {
        $excepciones = WmsExcepcionDespacho::where('tipo_registro', $tipoRegistro)
            ->where('id_registro', $idRegistro)
            ->with('creador:id,name')
            ->orderBy('created_at')
            ->get();

        if ($excepciones->isEmpty()) {
            abort(404, 'No hay excepciones de lote registradas para esta nota.');
        }

        $pdf = Pdf::loadView('wms.salidas.ticket-variacion-lote', [
            'excepciones' => $excepciones,
            'tipoRegistro' => $tipoRegistro,
            'idRegistro' => $idRegistro,
        ]);

        return $pdf->download("ticket-variacion-lote-{$idRegistro}.pdf");
    }
}