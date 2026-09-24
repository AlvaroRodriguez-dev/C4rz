<?php

namespace App\Http\Controllers\Wms;

use App\Http\Controllers\Controller;
use App\Models\WmsEntregaDetalle;
use App\Models\WmsEntregaProduccion;
use App\Services\WmsConciliacionService;
use App\Services\WmsContextService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class WmsProduccionVerificacionController extends Controller
{
    public function __construct(
        private WmsContextService $context,
        private WmsConciliacionService $conciliacion
    ) {
    }

    public function index()
    {
        return view('wms.produccion.verificacion');
    }

    public function buscar(Request $request)
    {
        $almacen = $this->context->almacen();
        $search = trim((string) $request->get('q'));
        $estado = trim((string) $request->get('estado'));
        $page = max(1, (int) $request->get('page', 1));

        $query = WmsEntregaProduccion::query()
            ->with('documento')
            ->where('almacen_id', $almacen->id)
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('rdocum_sas', 'like', "%{$search}%")
                        ->orWhere('origen', 'like', "%{$search}%")
                        ->orWhereHas('documento', function ($doc) use ($search) {
                            $doc->where('id_documento', 'like', "%{$search}%");
                        });
                });
            })
            ->when($estado !== '', fn ($q) => $q->where('estado', $estado))
            ->orderByDesc('id');

        $resultado = $query->paginate(15, ['*'], 'page', $page);

        return response()->json([
            'data' => $resultado->through(function (WmsEntregaProduccion $entrega) {
                return [
                    'id' => $entrega->id,
                    'documento' => $entrega->documento?->id_documento,
                    'rdocum_sas' => $entrega->rdocum_sas,
                    'fecha_entrega' => optional($entrega->fecha_entrega)->format('d/m/Y'),
                    'origen' => $entrega->origen,
                    'total_declarado' => $entrega->total_declarado,
                    'total_fisico' => $entrega->total_fisico,
                    'estado' => $entrega->estado,
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

        $entrega->load(['documento.tipo', 'almacen', 'detalles', 'verificadoPor']);

        return view('wms.produccion.verificacion-detalle', compact('entrega'));
    }

    public function iniciar(WmsEntregaProduccion $entrega)
    {
        $this->validarAlmacen($entrega);

        try {
            $entrega = $this->conciliacion->iniciarVerificacion($entrega);

            return response()->json([
                'ok' => true,
                'message' => 'La entrega está ahora en proceso de verificación.',
                'entrega' => $this->resumen($entrega),
            ]);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function cantidad(Request $request, WmsEntregaDetalle $detalle)
    {
        $entrega = $detalle->entrega;
        $this->validarAlmacen($entrega);

        $validated = $request->validate([
            'cantidad_fisica' => ['required', 'integer', 'min:0'],
        ]);

        try {
            $detalle = $this->conciliacion->registrarCantidadFisica(
                $detalle,
                (int) $validated['cantidad_fisica']
            );

            return response()->json([
                'ok' => true,
                'detalle' => $this->detalleResumen($detalle),
            ]);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function conciliar(WmsEntregaProduccion $entrega)
    {
        $this->validarAlmacen($entrega);

        try {
            $entrega = $this->conciliacion->conciliar($entrega);

            return response()->json([
                'ok' => true,
                'message' => $entrega->estado === 'CONCILIADA'
                    ? 'La entrega fue conciliada correctamente.'
                    : 'La entrega fue conciliada con diferencias.',
                'entrega' => $this->resumen($entrega),
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

    private function resumen(WmsEntregaProduccion $entrega): array
    {
        return [
            'id' => $entrega->id,
            'estado' => $entrega->estado,
            'total_declarado' => $entrega->total_declarado,
            'total_fisico' => $entrega->total_fisico,
            'fecha_recepcion' => optional($entrega->fecha_recepcion)->format('d/m/Y'),
            'verificado_at' => optional($entrega->verificado_at)->format('d/m/Y H:i'),
        ];
    }

    private function detalleResumen(WmsEntregaDetalle $detalle): array
    {
        return [
            'id' => $detalle->id,
            'cantidad_declarada' => $detalle->cantidad_declarada,
            'cantidad_fisica' => $detalle->cantidad_fisica,
            'diferencia' => $detalle->cantidad_fisica - $detalle->cantidad_declarada,
            'estado' => $detalle->estado,
        ];
    }
}
