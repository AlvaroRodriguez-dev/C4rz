<?php

namespace App\Http\Controllers\Wms;

use App\Http\Controllers\Controller;
use App\Models\WmsExcepcionDespacho;
use Illuminate\Http\Request;

class WmsExcepcionDespachoController extends Controller
{
    public function index()
    {
        return view('wms.excepciones-despacho.index');
    }

    /**
     * AJAX - listado agrupado por nota (tipo_registro + id_registro), con buscador único.
     */
    public function buscar(Request $request)
    {
        $search = trim((string) $request->get('q'));
        $page = (int) $request->get('page', 1);

        $notas = WmsExcepcionDespacho::query()
            ->selectRaw('tipo_registro, id_registro, MAX(created_at) as ultima_fecha, COUNT(*) as total_lineas, SUM(cantidad) as total_cajas')
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('id_registro', 'like', "%{$search}%")
                        ->orWhere('codigo', 'like', "%{$search}%")
                        ->orWhere('lote_solicitado', 'like', "%{$search}%")
                        ->orWhere('lote_aplicado', 'like', "%{$search}%");
                });
            })
            ->groupBy('tipo_registro', 'id_registro')
            ->orderByDesc('ultima_fecha')
            ->paginate(15, ['*'], 'page', $page);

        $data = $notas->through(function ($grupo) {
            $lineas = WmsExcepcionDespacho::with('creador:id,name')
                ->where('tipo_registro', $grupo->tipo_registro)
                ->where('id_registro', $grupo->id_registro)
                ->orderBy('created_at')
                ->get();

            return [
                'tipo_registro' => $grupo->tipo_registro,
                'id_registro' => $grupo->id_registro,
                'ultima_fecha' => \Carbon\Carbon::parse($grupo->ultima_fecha)->format('d/m/Y H:i'),
                'total_lineas' => $grupo->total_lineas,
                'total_cajas' => $grupo->total_cajas,
                'items' => $lineas->map(fn($l) => [
                    'codigo' => $l->codigo,
                    'descripcion' => trim("{$l->descrip} {$l->descrip1}"),
                    'lote_solicitado' => $l->lote_solicitado ?? 'S/L',
                    'lote_aplicado' => $l->lote_aplicado,
                    'cantidad' => $l->cantidad,
                    'usuario' => $l->creador->name ?? 'N/D',
                ]),
                'ticket_url' => route('wms.salidas.ticket-variacion-lote', [
                    'tipoRegistro' => $grupo->tipo_registro,
                    'idRegistro' => $grupo->id_registro,
                ]),
            ];
        });

        return response()->json([
            'data' => $data->items(),
            'current_page' => $notas->currentPage(),
            'last_page' => $notas->lastPage(),
            'total' => $notas->total(),
        ]);
    }
}
