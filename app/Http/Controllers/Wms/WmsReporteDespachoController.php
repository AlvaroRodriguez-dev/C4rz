<?php

namespace App\Http\Controllers\Wms;

use App\Http\Controllers\Controller;
use App\Models\WmsSalida;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class WmsReporteDespachoController extends Controller
{
    public function index()
    {
        return view('wms.reporte-despacho.index');
    }

    /**
     * AJAX - Búsqueda de notas de despacho (mismo criterio que Salidas).
     */
    public function buscarNotas(Request $request)
    {
        $q = trim((string) $request->get('q'));

        $notas = DB::connection('faboce2026')
            ->table('log_registro')
            ->whereBetween('tipo_registro', [1, 3])
            ->where('agencia', 110)
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('id', 'like', "%{$q}%")
                        ->orWhere('glosa', 'like', "%{$q}%");
                });
            })
            ->select('id', 'tipo_registro', 'agencia', 'glosa', 'fecha')
            ->orderByDesc('id')
            ->limit(30)
            ->get();

        $resultados = $notas->map(function ($nota) {
            $fechaFormateada = $nota->fecha ? \Carbon\Carbon::parse($nota->fecha)->format('d/m/Y') : 'S/F';

            return [
                'id' => $nota->id,
                'text' => "Tipo {$nota->tipo_registro} · #{$nota->id} · {$fechaFormateada} · Agencia {$nota->agencia} · {$nota->glosa}",
                'tipo_registro' => $nota->tipo_registro,
                'glosa' => $nota->glosa,
            ];
        });

        return response()->json(['results' => $resultados]);
    }

    /**
     * AJAX - Vista previa del detalle de despachos reales (wms_salidas) de una nota,
     * agrupado por producto, mostrando lote solicitado vs. lote aplicado.
     */
    public function detalle(string $tipoRegistro, string $idRegistro)
    {
        $datos = $this->obtenerDatosReporte($tipoRegistro, $idRegistro);

        return response()->json($datos);
    }

    /**
     * Genera el PDF imprimible del reporte.
     */
    public function pdf(string $tipoRegistro, string $idRegistro)
    {
        $datos = $this->obtenerDatosReporte($tipoRegistro, $idRegistro);

        $pdf = Pdf::loadView('wms.reporte-despacho.pdf', $datos);

        return $pdf->stream("reporte-despacho-{$idRegistro}.pdf");
    }

    private function obtenerDatosReporte(string $tipoRegistro, string $idRegistro): array
    {
        $salidas = WmsSalida::with('creador:id,name')
            ->where('tipo_registro', $tipoRegistro)
            ->where('id_registro', $idRegistro)
            ->orderBy('codigo')
            ->orderBy('created_at')
            ->get();

        // NUEVO: detalle de Órdenes de Trabajo de esta nota que AÚN NO fueron chequeadas
        // (ya reservaron cantidad, pero todavía no descontaron stock real).
        $enCamino = \App\Models\WmsOrdenTrabajoDetalle::with(['ordenTrabajo', 'chequeadoPor:id,name'])
            ->whereHas(
                'ordenTrabajo',
                fn($q) => $q
                    ->where('estado', 'pendiente')
                    ->where('tipo_registro', $tipoRegistro)
                    ->where('id_registro', $idRegistro)
            )
            ->orderBy('codigo')
            ->orderBy('created_at')
            ->get();

        $glosa = optional($salidas->first())->glosa ?? optional($enCamino->first()->ordenTrabajo ?? null)->glosa;

        $lineas = $salidas->map(function (WmsSalida $s) {
            return [
                'fecha' => $s->created_at->format('d/m/Y H:i'),
                'codigo' => $s->codigo,
                'descripcion' => trim("{$s->descrip} {$s->descrip1}"),
                'lote_solicitado' => $s->lote_declarado ?? 'S/L',
                'lote_aplicado' => $s->clote ?? 'S/L',
                'es_excepcion' => $s->es_excepcion_lote,
                'pallet' => $s->pallet,
                'galpon' => $s->galpon,
                'ubicacion' => $s->ubicacion,
                'cantidad' => $s->cantidad,
                'usuario' => $s->creador->name ?? 'N/D',
            ];
        });

        // NUEVO: líneas "en camino"
        $lineasEnCamino = $enCamino->map(function (\App\Models\WmsOrdenTrabajoDetalle $d) {
            return [
                'orden_trabajo_id' => $d->orden_trabajo_id,
                'fecha' => $d->created_at->format('d/m/Y H:i'),
                'codigo' => $d->codigo,
                'descripcion' => trim("{$d->descrip} {$d->descrip1}"),
                'lote_solicitado' => $d->lote_declarado ?? 'S/L',
                'lote_aplicado' => $d->clote ?? 'S/L',
                'es_excepcion' => $d->es_excepcion_lote,
                'pallet' => $d->pallet,
                'galpon' => $d->galpon_origen,
                'ubicacion' => $d->ubicacion_origen,
                'cantidad' => $d->cantidad,
                'chequeado' => $d->chequeado,
            ];
        });

        // Resumen por producto: total despachado + total en camino vs. total autorizado
        $totalesPorProducto = $salidas->groupBy('codigo')->map(function ($grupo) {
            $primero = $grupo->first();

            return [
                'codigo' => $primero->codigo,
                'descripcion' => trim("{$primero->descrip} {$primero->descrip1}"),
                'total_despachado' => $grupo->sum('cantidad'),
                'tuvo_excepcion' => $grupo->contains('es_excepcion_lote', true),
            ];
        })->values()->keyBy('codigo');

        // Fusiona con lo que está en camino, por si un producto solo tiene OT pendiente (sin salida real aún)
        foreach ($enCamino->groupBy('codigo') as $codigo => $grupo) {
            if (!$totalesPorProducto->has($codigo)) {
                $primero = $grupo->first();
                $totalesPorProducto->put($codigo, [
                    'codigo' => $codigo,
                    'descripcion' => trim("{$primero->descrip} {$primero->descrip1}"),
                    'total_despachado' => 0,
                    'tuvo_excepcion' => $grupo->contains('es_excepcion_lote', true),
                ]);
            }
        }

        $autorizado = DB::connection('faboce2026')
            ->table('log_registro_detalle')
            ->where('id_registro', $idRegistro)
            ->select('codigo', DB::raw('SUM(cantidad_despacho) as total_autorizado'))
            ->groupBy('codigo')
            ->get()
            ->keyBy('codigo');

        $totalesEnCaminoPorProducto = $enCamino->groupBy('codigo')->map(fn($g) => $g->sum('cantidad'));

        $totalesPorProducto = $totalesPorProducto->map(function ($t) use ($autorizado, $totalesEnCaminoPorProducto) {
            $t['total_autorizado'] = (int) ($autorizado[$t['codigo']]->total_autorizado ?? 0);
            $t['total_en_camino'] = (int) ($totalesEnCaminoPorProducto[$t['codigo']] ?? 0);
            $t['completo'] = $t['total_despachado'] >= $t['total_autorizado'];
            return $t;
        })->values();

        return [
            'tipo_registro' => $tipoRegistro,
            'id_registro' => $idRegistro,
            'glosa' => $glosa,
            'lineas' => $lineas,
            'lineas_en_camino' => $lineasEnCamino,           // <-- nuevo
            'totales_por_producto' => $totalesPorProducto,
            'total_general' => $salidas->sum('cantidad'),
            'total_en_camino' => $enCamino->sum('cantidad'), // <-- nuevo
            'generado' => now()->format('d/m/Y H:i'),
        ];
    }
}
