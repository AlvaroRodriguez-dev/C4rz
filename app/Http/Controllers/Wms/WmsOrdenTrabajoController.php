<?php

namespace App\Http\Controllers\Wms;

use App\Http\Controllers\Controller;
use App\Models\WmsOrdenTrabajo;
use App\Models\WmsSalida;
use App\Services\ReubicacionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class WmsOrdenTrabajoController extends Controller
{
    private const GALPON_PLAYA = 'PLAYA';
    private const UBICACION_PLAYA = '1';

    public function __construct(private ReubicacionService $reubicacionService) {}

    public function index()
    {
        return view('wms.ordenes-trabajo.index');
    }

    /** AJAX - listado de OTs pendientes. */
    public function pendientes()
    {
        $ordenes = WmsOrdenTrabajo::where('estado', 'pendiente')
            ->with('detalles')
            ->orderBy('created_at')
            ->get()
            ->map(function (WmsOrdenTrabajo $ot) {
                $pallets = $ot->detalles->groupBy('pallet')->map(function ($lineas, $pallet) {
                    $primera = $lineas->first();

                    return [
                        'pallet' => $pallet,
                        'chequeado' => $lineas->every(fn($l) => $l->chequeado),
                        'almacen' => $primera->almacen_origen,
                        'galpon' => $primera->galpon_origen,
                        'ubicacion' => $primera->ubicacion_origen,
                        'items' => $lineas->map(fn($l) => [
                            'codigo' => $l->codigo,
                            'clote' => $l->clote,
                            'descrip' => $l->descrip,
                            'descrip1' => $l->descrip1,
                            'cantidad' => $l->cantidad,
                        ])->values(),
                    ];
                })->values();

                return [
                    'id' => $ot->id,
                    'tipo_registro' => $ot->tipo_registro,
                    'id_registro' => $ot->id_registro,
                    'glosa' => $ot->glosa,
                    'creado' => $ot->created_at->format('d/m/Y H:i'),
                    'total_pallets' => $pallets->count(),
                    'pallets_chequeados' => $pallets->where('chequeado', true)->count(),
                    'pallets' => $pallets,
                ];
            });

        return response()->json(['ordenes' => $ordenes]);
    }

    /**
     * Marca un pallet específico de la OT como chequeado.
     * Si con esto quedan TODOS los pallets chequeados, finaliza la OT automáticamente
     * (reubica cada pallet completo a PLAYA/1 y luego registra la salida real).
     */
    public function marcarPallet(Request $request, WmsOrdenTrabajo $ordenTrabajo, string $pallet)
    {
        if ($ordenTrabajo->estado !== 'pendiente') {
            return response()->json(['errors' => ['general' => ['Esta Orden de Trabajo ya fue completada.']]], 422);
        }

        $lineas = $ordenTrabajo->detalles()->where('pallet', $pallet)->get();

        if ($lineas->isEmpty()) {
            return response()->json(['errors' => ['general' => ['El pallet no pertenece a esta Orden de Trabajo.']]], 422);
        }

        DB::transaction(function () use ($lineas) {
            foreach ($lineas as $linea) {
                $linea->update([
                    'chequeado' => true,
                    'chequeado_por' => Auth::id(),
                    'chequeado_at' => now(),
                ]);
            }
        });

        $todosChequeados = $ordenTrabajo->fresh('detalles')->detalles->every(fn($l) => $l->chequeado);

        if ($todosChequeados) {
            $this->finalizarOrdenTrabajo($ordenTrabajo);
            return response()->json(['message' => 'Pallet verificado. Todos los pallets fueron chequeados: la salida quedó registrada.', 'completada' => true]);
        }

        return response()->json(['message' => 'Pallet verificado.', 'completada' => false]);
    }

    /**
     * Ejecuta la finalización: por cada pallet distinto de la OT,
     * traslada TODO su contenido físico a PLAYA/1, y luego registra
     * la salida real (descuento de saldo) desde esa nueva ubicación.
     */
    private function finalizarOrdenTrabajo(WmsOrdenTrabajo $ordenTrabajo): void
    {
        DB::transaction(function () use ($ordenTrabajo) {
            // Momento base para la reubicación a Playa, y la Salida 1 segundo después,
            // para que el Kardex ordene siempre: INGRESO -> REUBICACIÓN A PLAYA -> SALIDA
            $tReubicacion = now();
            $tSalida = $tReubicacion->copy()->addSecond();

            $pallets = $ordenTrabajo->detalles->pluck('pallet')->unique();

            foreach ($pallets as $pallet) {
                $this->reubicacionService->trasladarPalletCompleto(
                    $pallet,
                    self::GALPON_PLAYA,
                    self::UBICACION_PLAYA,
                    null,
                    "Traslado a zona de despacho - OT #{$ordenTrabajo->id}",
                    $ordenTrabajo->id,   // <-- restablecido: marca la reubicación como automática de esta OT
                    $tReubicacion        // <-- restablecido: timestamp explícito para el orden del Kardex
                );
            }

            foreach ($ordenTrabajo->detalles as $linea) {
                $salida = new WmsSalida([
                    'tipo_registro' => $ordenTrabajo->tipo_registro,
                    'id_registro' => $ordenTrabajo->id_registro,
                    'glosa' => $ordenTrabajo->glosa,
                    'pallet' => $linea->pallet,
                    'codigo' => $linea->codigo,
                    'clote' => $linea->clote,
                    'lote_declarado' => $linea->lote_declarado,
                    'es_excepcion_lote' => $linea->es_excepcion_lote,
                    'descrip' => $linea->descrip,
                    'descrip1' => $linea->descrip1,
                    'cantidad' => $linea->cantidad,
                    'almacen' => $linea->almacen_origen,
                    'galpon' => self::GALPON_PLAYA,
                    'ubicacion' => self::UBICACION_PLAYA,
                ]);

                $salida->created_at = $tSalida;
                $salida->updated_at = $tSalida;
                $salida->save();
            }

            $ordenTrabajo->update(['estado' => 'completada', 'completada_at' => now()]);
        });
    }

    public function imprimir(WmsOrdenTrabajo $ordenTrabajo)
    {
        $detalles = $ordenTrabajo->detalles()
            ->with('chequeadoPor:id,name')
            ->orderBy('pallet')
            ->get();

        $pdf = Pdf::loadView('wms.ordenes-trabajo.imprimir', [
            'ordenTrabajo' => $ordenTrabajo,
            'detalles' => $detalles,
        ]);

        // stream(): abre el PDF en una nueva pestaña, listo para Ctrl+P / botón imprimir del visor.
        return $pdf->stream("orden-trabajo-{$ordenTrabajo->id}.pdf");
    }

    public function verIndex()
    {
        return view('wms.ordenes-trabajo.ver');
    }

    /**
     * AJAX - listado de TODAS las Órdenes de Trabajo (pendientes y completadas),
     * con buscador único y paginación, al estilo de Excepciones de Despacho.
     */
    public function buscarHistorial(Request $request)
    {
        $search = trim((string) $request->get('q'));
        $page = (int) $request->get('page', 1);

        $query = WmsOrdenTrabajo::query()
            ->withCount('detalles')
            ->withCount(['detalles as chequeados_count' => fn($q) => $q->where('chequeado', true)])
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('id', 'like', "%{$search}%")
                        ->orWhere('id_registro', 'like', "%{$search}%")
                        ->orWhere('glosa', 'like', "%{$search}%")
                        ->orWhereRaw("DATE_FORMAT(created_at, '%d/%m/%Y') like ?", ["%{$search}%"]);
                });
            })
            ->orderByDesc('created_at');

        $ordenes = $query->paginate(15, ['*'], 'page', $page);

        $data = $ordenes->through(function (WmsOrdenTrabajo $ot) {
            return [
                'id' => $ot->id,
                'tipo_registro' => $ot->tipo_registro,
                'id_registro' => $ot->id_registro,
                'glosa' => $ot->glosa,
                'estado' => $ot->estado,
                'creado' => $ot->created_at->format('d/m/Y H:i'),
                'completada' => optional($ot->completada_at)->format('d/m/Y H:i'),
                'total_lineas' => $ot->detalles_count,
                'lineas_chequeadas' => $ot->chequeados_count,
                'ticket_url' => route('wms.ordenes.trabajo.imprimir', $ot->id),
            ];
        });

        return response()->json([
            'data' => $data->items(),
            'current_page' => $ordenes->currentPage(),
            'last_page' => $ordenes->lastPage(),
            'total' => $ordenes->total(),
        ]);
    }
}
