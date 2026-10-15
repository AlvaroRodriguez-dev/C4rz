<?php

namespace App\Http\Controllers\Wms;

use App\Http\Controllers\Controller;
use App\Models\WmsEntregaProduccion;
use App\Models\WmsHu;
use App\Models\WmsHuDiscrepancia;
use App\Models\WmsIngreso;
use App\Models\WmsUbicacion;
use App\Services\WmsContextService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class WmsIngresoPtLiberadoController extends Controller
{
    public function __construct(private WmsContextService $context) {}

    public function index()
    {
        $almacen = $this->context->almacen();
        $liberaciones = WmsEntregaProduccion::query()
            ->with(['documento', 'hu' => fn ($q) => $q->where('estado', '!=', 'SUSTITUIDO')->with(['verificacion', 'ubicacion'])])
            ->where('almacen_id', $almacen->id)
            ->whereIn('estado', ['VERIFICADA', 'CONFIRMADO_CON_UBICACION'])
            ->orderByDesc('id')->get()
            ->filter(fn ($e) => $e->hu->isNotEmpty() && $e->hu->every(fn ($hu) => $this->palletVerificadoSinPendientes($hu)))
            ->values();

        return view('wms.ingreso-pt-liberado.index', compact('liberaciones'));
    }

    public function pallets(WmsEntregaProduccion $entrega)
    {
        $this->validarAlmacen($entrega);
        $this->validarLiberacionVerificada($entrega);
        $pallets = $entrega->hu()->where('estado', '!=', 'SUSTITUIDO')
            ->with(['verificacion', 'ubicacion.galpon', 'detalles'])->orderBy('numero')->get()
            ->filter(fn ($hu) => $this->palletVerificadoSinPendientes($hu))
            ->map(fn ($hu) => [
                'id' => $hu->id, 'numero' => $hu->numero, 'formato' => $hu->formato,
                'cantidad' => (int) $hu->cantidad_total,
                'ubicado' => $hu->ubicacion_id !== null || $hu->ubicado_at !== null,
                'detalles' => $hu->detalles->map(fn ($d) => [
                    'codigo' => $d->codigo, 'lote' => $d->lote, 'cantidad' => (int) $d->cantidad,
                ])->values(),
            ])->values();

        return response()->json(['pallets' => $pallets]);
    }

    public function ubicaciones()
    {
        $almacen = $this->context->almacen();
        $ubicaciones = WmsUbicacion::query()->with('galpon')
            ->where('almacen_id', $almacen->id)->where('tipo', 'NORMAL')->where('activo', true)
            ->whereHas('galpon', fn ($q) => $q->where('activo', true))
            ->orderBy('codigo')->get()->map(fn ($u) => [
                'codigo' => $u->codigo, 'galpon' => $u->galpon?->codigo,
                'texto' => trim(($u->galpon?->codigo ?? '') . ' / ' . $u->codigo, ' /'),
            ])->values();

        return response()->json(['ubicaciones' => $ubicaciones]);
    }

    public function confirmar(Request $request, WmsEntregaProduccion $entrega)
    {
        $data = $request->validate([
            'hu_id' => ['required', 'integer'],
            'ubicacion' => ['required', 'string', 'max:30'],
        ]);

        try {
            $resultado = DB::transaction(function () use ($data, $entrega) {
                $liberacion = WmsEntregaProduccion::query()->lockForUpdate()->findOrFail($entrega->id);
                $this->validarAlmacen($liberacion);
                $this->validarLiberacionVerificada($liberacion);
                $almacen = $this->context->almacen();

                $hu = WmsHu::query()->with(['detalles', 'verificacion'])
                    ->where('entrega_id', $liberacion->id)->where('almacen_id', $almacen->id)
                    ->where('estado', '!=', 'SUSTITUIDO')->lockForUpdate()->findOrFail((int) $data['hu_id']);

                if (!$this->palletVerificadoSinPendientes($hu)) {
                    throw new RuntimeException('El pallet no tiene una verificación vigente y confirmada al 100%.');
                }
                if ($hu->ubicacion_id || $hu->ubicado_at) {
                    throw new RuntimeException('Este pallet ya tiene una ubicación registrada.');
                }

                $codigo = strtoupper(trim($data['ubicacion']));
                $ubicacion = WmsUbicacion::query()->with('galpon')
                    ->where('almacen_id', $almacen->id)->whereRaw('UPPER(codigo) = ?', [$codigo])
                    ->where('tipo', 'NORMAL')->where('activo', true)
                    ->whereHas('galpon', fn ($q) => $q->where('activo', true))->first();

                if (!$ubicacion || !$ubicacion->galpon) {
                    throw ValidationException::withMessages(['ubicacion' => 'La ubicación escaneada no existe, está inactiva o no es una ubicación NORMAL habilitada.']);
                }
                if ($hu->detalles->isEmpty()) {
                    throw new RuntimeException('El pallet no tiene detalle de productos; no se puede generar el ingreso físico.');
                }

                $pallet = trim((string) $hu->numero);
                $rdocum = 'PT-' . $liberacion->id;
                if (WmsIngreso::query()->where('rdocum', $rdocum)->where('pallet', $pallet)->exists()) {
                    throw new RuntimeException('Ya existe un ingreso físico para este pallet. No se duplicó el stock.');
                }

                $fecha = $liberacion->fecha_entrega ?? now()->toDateString();
                foreach ($hu->detalles as $detalle) {
                    WmsIngreso::create([
                        'rdocum' => $rdocum, 'rfecha' => $fecha, 'tipo_ingreso' => 'produccion',
                        'motivo' => 'Ingreso PT liberado - liberación WMS #' . $liberacion->id,
                        'pallet' => $pallet, 'codigo' => $detalle->codigo, 'clote' => $detalle->lote,
                        'descrip' => $detalle->descripcion, 'descrip1' => $detalle->descripcion2,
                        'cantidad' => (int) $detalle->cantidad, 'almacen' => $almacen->codigo,
                        'galpon' => $ubicacion->galpon->codigo, 'ubicacion' => $ubicacion->codigo,
                    ]);
                }

                $hu->update([
                    'ubicacion_id' => $ubicacion->id, 'ubicado_at' => now(), 'update_id' => auth()->id(),
                ]);

                $pallets = WmsHu::query()->where('entrega_id', $liberacion->id)
                    ->where('estado', '!=', 'SUSTITUIDO')->lockForUpdate()->get();
                $todosUbicados = $pallets->every(fn ($p) =>
                    (int) $p->id === (int) $hu->id || ($p->ubicacion_id !== null && $p->ubicado_at !== null)
                );

                if ($todosUbicados) {
                    $liberacion->update(['estado' => 'CONFIRMADO_CON_UBICACION', 'update_id' => auth()->id()]);
                }

                return [
                    'todos_ubicados' => $todosUbicados,
                    'pallet' => $pallet,
                    'ubicacion' => trim($ubicacion->galpon->codigo . ' / ' . $ubicacion->codigo, ' /'),
                ];
            });

            return response()->json([
                'ok' => true,
                'message' => $resultado['todos_ubicados']
                    ? 'Ubicación registrada y stock actualizado. Todos los pallets de la liberación están ubicados.'
                    : 'Ubicación registrada y stock físico actualizado correctamente.',
                'resultado' => $resultado,
            ]);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    private function validarAlmacen(WmsEntregaProduccion $entrega): void
    {
        if ((int) $entrega->almacen_id !== (int) $this->context->almacen()->id) {
            abort(403, 'La liberación no pertenece al almacén operativo del usuario.');
        }
    }

    private function validarLiberacionVerificada(WmsEntregaProduccion $entrega): void
    {
        if (!in_array($entrega->estado, ['VERIFICADA', 'CONFIRMADO_CON_UBICACION'], true)) {
            throw new RuntimeException('La liberación todavía no está verificada y confirmada al 100%.');
        }
        $pallets = $entrega->hu()->where('estado', '!=', 'SUSTITUIDO')->with(['verificacion', 'discrepancias'])->get();
        if ($pallets->isEmpty() || $pallets->contains(fn ($hu) => !$this->palletVerificadoSinPendientes($hu))) {
            throw new RuntimeException('La liberación tiene pallets pendientes de verificación o reverificación.');
        }
    }

    private function palletVerificadoSinPendientes(WmsHu $hu): bool
    {
        $v = $hu->verificacion;
        if (!$v || $v->resultado !== 'CONFIRMADO') return false;

        return !$hu->discrepancias->contains(fn (WmsHuDiscrepancia $d) =>
            $d->estado === 'PENDIENTE_CORRECCION'
            || ($d->estado === 'CORREGIDA' && $d->corregido_at && $v->verificado_at && $d->corregido_at->gt($v->verificado_at))
        );
    }
}
