<?php

namespace App\Http\Controllers\Wms;

use App\Http\Controllers\Controller;
use App\Models\WmsEntregaProduccion;
use App\Services\WmsContextService;
use App\Services\WmsPaletizacionService;
use Illuminate\Http\Request;
use RuntimeException;

class WmsPaletizacionController extends Controller
{
    public function __construct(
        private WmsContextService $context,
        private WmsPaletizacionService $paletizacion
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

        $resultado = WmsEntregaProduccion::query()
            ->with('documento')
            ->where('almacen_id', $almacen->id)
            ->where('estado', 'CONCILIADA')
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('rdocum_sas', 'like', "%{$search}%")
                        ->orWhere('origen', 'like', "%{$search}%")
                        ->orWhereHas('documento', fn ($doc) => $doc->where('id_documento', 'like', "%{$search}%"));
                });
            })
            ->orderByDesc('id')
            ->paginate(15, ['*'], 'page', $page);

        return response()->json([
            'data' => $resultado->through(function (WmsEntregaProduccion $entrega) {
                $disponibles = collect($this->paletizacion->detalleDisponible($entrega));
                $pendiente = $disponibles->sum('cantidad_pendiente');
                $paletizado = $disponibles->sum('cantidad_paletizada');

                return [
                    'id' => $entrega->id,
                    'documento' => $entrega->documento?->id_documento,
                    'fecha_entrega' => optional($entrega->fecha_entrega)->format('d/m/Y'),
                    'origen' => $entrega->origen,
                    'total_fisico' => $entrega->total_fisico,
                    'cantidad_paletizada' => $paletizado,
                    'cantidad_pendiente' => $pendiente,
                    'estado' => $pendiente === 0 ? 'PALETIZADA' : 'PENDIENTE_PALETIZAR',
                ];
            })->items(),
            'current_page' => $resultado->currentPage(),
            'last_page' => $resultado->lastPage(),
            'total' => $resultado->total(),
        ]);
    }

    public function show(WmsEntregaProduccion $entrega)
    {
        $this->validarAlmacen($entrega);

        if ($entrega->estado !== 'CONCILIADA') {
            abort(422, 'La entrega debe estar conciliada antes de iniciar la paletización.');
        }

        $entrega->load(['documento', 'almacen']);
        $detalles = $this->paletizacion->detalleDisponible($entrega);

        return view('wms.produccion.paletizacion-detalle', compact('entrega', 'detalles'));
    }

    public function store(Request $request, WmsEntregaProduccion $entrega)
    {
        $this->validarAlmacen($entrega);

        $validated = $request->validate([
            'pallets' => ['required', 'array', 'min:1'],
            'pallets.*.items' => ['required', 'array', 'min:1'],
            'pallets.*.items.*.entrega_detalle_id' => ['required', 'integer'],
            'pallets.*.items.*.cantidad' => ['required', 'integer', 'min:1'],
        ]);

        try {
            $creados = $this->paletizacion->guardar(
                $entrega,
                $validated['pallets'],
                (int) auth()->id()
            );

            return response()->json([
                'ok' => true,
                'message' => 'Paletización registrada correctamente.',
                'pallets' => $creados,
            ]);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    private function validarAlmacen(WmsEntregaProduccion $entrega): void
    {
        $almacen = $this->context->almacen();

        if ((int) $entrega->almacen_id !== (int) $almacen->id) {
            abort(403, 'La entrega no pertenece al almacén operativo del usuario.');
        }
    }
}
