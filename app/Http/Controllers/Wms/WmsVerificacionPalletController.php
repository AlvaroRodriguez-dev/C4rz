<?php

namespace App\Http\Controllers\Wms;

use App\Http\Controllers\Controller;
use App\Models\WmsEntregaProduccion;
use App\Models\WmsHu;
use App\Services\WmsContextService;
use App\Services\WmsVerificacionPalletService;
use Illuminate\Http\Request;
use RuntimeException;

class WmsVerificacionPalletController extends Controller
{
    public function __construct(
        private WmsContextService $context,
        private WmsVerificacionPalletService $service
    ) {
    }

    public function indexVerificacion()
    {
        $this->validarPermiso();

        return view('wms.produccion.verificacion-pallets-index');
    }

    public function show(WmsEntregaProduccion $entrega)
    {
        $this->validarPermiso();
        $this->validarAlmacen($entrega);

        try {
            $this->service->iniciar($entrega);
        } catch (RuntimeException $e) {
            return redirect()
                ->route('wms.produccion.liberacion.create')
                ->with('error', $e->getMessage());
        }

        $entrega->load([
            'documento',
            'almacen',
            'hu' => fn ($query) => $query
                ->where('estado', '!=', 'SUSTITUIDO')
                ->with(['detalles', 'verificacion.verificadoPor'])
                ->orderBy('id'),
        ]);

        $resumen = $this->service->resumen($entrega);

        return view('wms.produccion.verificacion-pallets', compact('entrega', 'resumen'));
    }

    public function buscar(WmsEntregaProduccion $entrega, Request $request)
    {
        $this->validarPermiso();
        $this->validarAlmacen($entrega);

        $q = trim((string) $request->input('q', ''));

        if ($request->has('q')) {
            $results = WmsHu::query()
                ->where('entrega_id', $entrega->id)
                ->when($q !== '', fn ($builder) => $builder->where('numero', 'like', "%{$q}%"))
                ->with('verificacion')
                ->orderBy('numero')
                ->limit(30)
                ->get()
                ->map(function (WmsHu $hu) {
                    $estado = $hu->verificacion
                        ? ($hu->verificacion->resultado === 'CONFIRMADO_CON_DIFERENCIA' ? 'CONFIRMADO CON DIFERENCIA' : 'CONFIRMADO')
                        : 'PENDIENTE';

                    return [
                        'id' => $hu->numero,
                        'text' => $hu->numero . ' · ' . $estado,
                    ];
                })
                ->values();

            return response()->json(['results' => $results]);
        }

        $data = $request->validate([
            'numero' => ['required', 'string', 'max:50'],
        ]);

        try {
            $hu = $this->service->buscarPallet($entrega, $data['numero']);

            return response()->json([
                'ok' => true,
                'pallet' => $this->palletResumen($hu),
            ]);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function confirmar(WmsEntregaProduccion $entrega, WmsHu $hu, Request $request)
    {
        $this->validarPermiso();
        $this->validarAlmacen($entrega);

        $data = $request->validate([
            'cantidad_verificada' => ['required', 'integer', 'min:0'],
            'observacion' => ['nullable', 'string', 'max:1000'],
            'discrepancias' => ['nullable', 'array', 'max:20'],
            'discrepancias.*.hu_detalle_id' => ['required', 'integer'],
            'discrepancias.*.tipo' => ['required', 'string', 'in:TONO,CALIBRE,CALIDAD_PRODUCTO'],
            'discrepancias.*.valor_observado' => ['required', 'string', 'max:255'],
            'discrepancias.*.observacion' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $verificacion = $this->service->confirmar(
                $entrega,
                $hu,
                (int) $data['cantidad_verificada'],
                $data['observacion'] ?? null,
                $data['discrepancias'] ?? []
            );

            $entrega->refresh();
            $resumen = $this->service->resumen($entrega);

            return response()->json([
                'ok' => true,
                'message' => $verificacion->resultado === 'CONFIRMADO'
                    ? 'Pallet confirmado correctamente.'
                    : 'Pallet confirmado con diferencia. La diferencia quedó registrada para auditoría.',
                'resultado' => $verificacion->resultado,
                'diferencia' => $verificacion->diferencia,
                'estado_entrega' => $entrega->estado,
                'resumen' => $resumen,
            ]);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    private function palletResumen(WmsHu $hu): array
    {
        return [
            'id' => $hu->id,
            'numero' => $hu->numero,
            'formato' => $hu->formato,
            'tipo' => $hu->tipo,
            'capacidad' => $hu->capacidad_estandar,
            'cantidad_esperada' => (int) $hu->cantidad_total,
            'verificado' => $hu->verificacion !== null,
            'resultado' => $hu->verificacion?->resultado,
            'cantidad_verificada' => $hu->verificacion?->cantidad_verificada,
            'diferencia' => $hu->verificacion?->diferencia,
            'observacion' => $hu->verificacion?->observacion,
            'discrepancias' => $hu->discrepancias->map(fn ($d) => [
                'id' => $d->id,
                'tipo' => $d->tipo,
                'valor_esperado' => $d->valor_esperado,
                'valor_observado' => $d->valor_observado,
                'observacion' => $d->observacion,
                'estado' => $d->estado,
            ])->values(),
            'contenido' => $hu->detalles->map(fn ($detalle) => [
                'id' => $detalle->id,
                'codigo' => $detalle->codigo,
                'calidad' => $detalle->calidad,
                'tono' => $detalle->entregaDetalle?->tono,
                'calibre' => $detalle->entregaDetalle?->calibre,
                'descripcion' => $detalle->descripcion,
                'descripcion1' => $detalle->descripcion2,
                'lote' => $detalle->lote,
                'cantidad' => (int) $detalle->cantidad,
            ])->values(),
        ];
    }

    private function validarPermiso(): void
    {
        abort_unless(auth()->user()->can('wms.produccion.verificar.ejecutar'), 403);
    }

    private function validarAlmacen(WmsEntregaProduccion $entrega): void
    {
        $almacen = $this->context->almacen();

        if ((int) $entrega->almacen_id !== (int) $almacen->id) {
            abort(403, 'La liberación no pertenece al almacén operativo del usuario.');
        }
    }
}
