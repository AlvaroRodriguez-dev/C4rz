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
            ->with(['documento', 'detalles'])
            ->where('almacen_id', $almacen->id)
            ->whereIn('estado', ['CONCILIADA', 'CON_DIFERENCIA'])
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('folio_fisico', 'like', "%{$search}%")
                        ->orWhere('origen', 'like', "%{$search}%")
                        ->orWhereHas('documento', fn ($doc) => $doc->where('id_documento', 'like', "%{$search}%"));
                });
            })
            ->orderByDesc('id')
            ->paginate(15, ['*'], 'page', $page);

        return response()->json([
            'data' => $resultado->through(function (WmsEntregaProduccion $entrega) {
                $disponibles = collect($this->paletizacion->detalleDisponible($entrega));
                $pendiente = (int) $disponibles->sum('cantidad_pendiente');
                $paletizado = (int) $disponibles->sum('cantidad_paletizada');

                return [
                    'id' => $entrega->id,
                    'documento' => $entrega->documento?->id_documento,
                    'folio_fisico' => $entrega->folio_fisico,
                    'fecha_entrega' => optional($entrega->fecha_entrega)->format('d/m/Y'),
                    'origen' => $entrega->origen,
                    'total_fisico' => (int) $entrega->total_fisico,
                    'cantidad_paletizada' => $paletizado,
                    'pendiente' => $pendiente,
                    'estado' => $entrega->estado,
                ];
            })->filter(fn (array $item) => $item['pendiente'] > 0)->values()->all(),
            'current_page' => $resultado->currentPage(),
            'last_page' => $resultado->lastPage(),
            'total' => $resultado->total(),
        ]);
    }

    public function show(WmsEntregaProduccion $entrega)
    {
        $this->validarAlmacen($entrega);

        if (!in_array($entrega->estado, ['CONCILIADA', 'CON_DIFERENCIA'], true)) {
            return redirect()
                ->route('wms.paletizacion.index')
                ->with('error', 'La entrega debe estar conciliada o con diferencia antes de iniciar la paletización.');
        }

        $entrega->load(['documento', 'almacen']);
        $detalles = $this->paletizacion->detalleDisponible($entrega);

        if (collect($detalles)->sum('cantidad_pendiente') <= 0) {
            return redirect()
                ->route('wms.paletizacion.index')
                ->with('success', 'La entrega ya no tiene cantidades pendientes de paletizar.');
        }

        return view('wms.produccion.paletizacion-detalle', compact('entrega', 'detalles'));
    }

    public function store(Request $request, WmsEntregaProduccion $entrega)
    {
        $this->validarAlmacen($entrega);

        $validated = $request->validate([
            'pallets' => ['required', 'string'],
        ]);

        $pallets = json_decode($validated['pallets'], true);

        if (!is_array($pallets)) {
            return back()->withInput()->with('error', 'La información de paletización no es válida.');
        }

        try {
            $creados = $this->paletizacion->guardar(
                $entrega,
                $pallets,
                (int) auth()->id()
            );

            return redirect()
                ->route('wms.paletizacion.show', $entrega)
                ->with('success', count($creados) === 1
                    ? '1 pallet fue registrado correctamente.'
                    : count($creados) . ' pallets fueron registrados correctamente.');
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
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
