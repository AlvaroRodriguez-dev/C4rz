<?php

namespace App\Http\Controllers\Wms;

use App\Http\Controllers\Controller;
use App\Models\WmsEntregaProduccion;
use App\Services\WmsContextService;
use Illuminate\Http\Request;

class WmsPaletizacionProduccionController extends Controller
{
    public function __construct(
        private WmsContextService $context
    ) {
    }

    public function index()
    {
        return view('wms.produccion.paletizacion');
    }

    public function buscar(Request $request)
    {
        $almacen = $this->context->almacen();
        $search = trim((string) $request->get('q'));
        $page = max(1, (int) $request->get('page', 1));

        $query = WmsEntregaProduccion::query()
            ->with(['documento', 'almacen'])
            ->withSum('detalles as total_paletizado', 'cantidad_paletizada')
            ->where('almacen_id', $almacen->id)
            ->where('estado', 'CONCILIADA')
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->whereHas('documento', function ($doc) use ($search) {
                        $doc->where('id_documento', 'like', "%{$search}%");
                    })
                    ->orWhere('origen', 'like', "%{$search}%")
                    ->orWhere('folio_fisico', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('id');

        $resultado = $query->paginate(15, ['*'], 'page', $page);

        $data = $resultado->getCollection()->map(function (WmsEntregaProduccion $entrega) {
            $paletizado = (int) ($entrega->total_paletizado ?? 0);
            $fisico = (int) $entrega->total_fisico;

            return [
                'id' => $entrega->id,
                'documento' => $entrega->documento?->id_documento,
                'folio_fisico' => $entrega->folio_fisico,
                'fecha_entrega' => optional($entrega->fecha_entrega)->format('d/m/Y'),
                'almacen' => $entrega->almacen?->codigo,
                'almacen_nombre' => $entrega->almacen?->nombre,
                'total_fisico' => $fisico,
                'total_paletizado' => $paletizado,
                'pendiente' => max(0, $fisico - $paletizado),
                'estado' => $entrega->estado,
            ];
        })->values();

        return response()->json([
            'data' => $data,
            'current_page' => $resultado->currentPage(),
            'last_page' => $resultado->lastPage(),
            'total' => $resultado->total(),
        ]);
    }
}
