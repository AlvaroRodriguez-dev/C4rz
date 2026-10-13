<?php

namespace App\Http\Controllers\Wms;

use App\Http\Controllers\Controller;
use App\Models\WmsConfigPallet;
use App\Models\WmsEntregaDetalle;
use App\Models\WmsHuDiscrepancia;
use App\Models\WmsHuVerificacion;
use App\Models\WmsEntregaProduccion;
use App\Services\WmsContextService;
use App\Services\WmsLiberacionProduccionService;
use App\Services\WmsPaletizacionService;
use App\Services\WmsProductoCatalogoService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class WmsLiberacionProduccionController extends Controller
{
    public function __construct(
        private WmsContextService $context,
        private WmsLiberacionProduccionService $service,
        private WmsProductoCatalogoService $catalogo,
        private WmsPaletizacionService $paletizacion
    ) {
    }

    public function index()
    {
        return view('wms.produccion.liberacion-index');
    }

    public function create(Request $request)
    {
        if ($request->boolean('buscar')) {
            return $this->buscar($request);
        }

        if ($request->boolean('pdf')) {
            $entrega = WmsEntregaProduccion::findOrFail($request->integer('id'));
            return $this->pdf($entrega);
        }

        if (!$request->boolean('nuevo')) {
            return view('wms.produccion.liberacion-index');
        }

        abort_unless(auth()->user()->can('wms.produccion.liberar'), 403);

        $formatos = WmsConfigPallet::query()->orderBy('codigo')->get();
        $almacen = $this->context->almacen();

        return view('wms.produccion.liberacion', compact('formatos', 'almacen'));
    }

    public function buscar(Request $request)
    {
        $almacen = $this->context->almacen();
        $search = trim((string) $request->get('q'));
        $estado = trim((string) $request->get('estado'));
        $scope = trim((string) $request->get('scope'));
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
            ->when($scope === 'verificacion', fn ($q) => $q->whereIn('estado', ['PENDIENTE_VERIFICACION', 'EN_VERIFICACION', 'PENDIENTE_REVERIFICACION', 'VERIFICADA']))
            ->when($estado !== '', fn ($q) => $q->where('estado', $estado))
            ->orderByDesc('id');

        $resultado = $query->paginate(15, ['*'], 'page', $page);

        return response()->json([
            'data' => $resultado->through(function (WmsEntregaProduccion $entrega) {
                return [
                    'id' => $entrega->id,
                    'puede_corregir' => (int) $entrega->created_id === (int) auth()->id() && auth()->user()->can('wms.produccion.liberar'),
                    'documento' => $entrega->documento?->id_documento,
                    'rdocum_sas' => $entrega->rdocum_sas,
                    'folio_fisico' => $entrega->folio_fisico,
                    'fecha_entrega' => optional($entrega->fecha_entrega)->format('d/m/Y'),
                    'origen' => $entrega->origen,
                    'total_declarado' => $entrega->total_declarado,
                    'total_fisico' => $entrega->total_fisico,
                    'pallets' => $entrega->hu()->where('estado', '!=', 'SUSTITUIDO')->count(),
                    'estado' => $entrega->estado,
                ];
            })->items(),
            'current_page' => $resultado->currentPage(),
            'last_page' => $resultado->lastPage(),
            'total' => $resultado->total(),
        ]);
    }

    public function generarPallets(WmsEntregaProduccion $entrega)
    {
        $this->validarAlmacen($entrega);

        if ($entrega->estado !== 'PENDIENTE_PALLET') {
            return back()->with('error', 'La liberación no está pendiente de generación de pallets.');
        }

        try {
            $creados = $this->paletizacion->guardar(
                $entrega,
                [],
                (int) auth()->id()
            );

            return redirect()
                ->route('wms.produccion.liberacion.pallets', $entrega)
                ->with('success', count($creados) === 1
                    ? '1 pallet fue generado correctamente.'
                    : count($creados) . ' pallets fueron generados correctamente.');
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function pallets(WmsEntregaProduccion $entrega, Request $request)
    {
        $this->validarAlmacen($entrega);

        if (!in_array($entrega->estado, [
            'PENDIENTE_VERIFICACION',
            'EN_VERIFICACION',
            'OBSERVADA',
            'PENDIENTE_REVERIFICACION',
            'VERIFICADA',
            'PALETIZADA',
            'UBICADA',
            'CONCILIADA',
            'CON_DIFERENCIA',
        ], true)) {
            return redirect()
                ->route('wms.produccion.liberacion.create')
                ->with('error', 'La liberación todavía no tiene pallets generados.');
        }

        $entrega->load([
            'documento',
            'almacen',
            'hu' => fn ($query) => $query->where('estado', '!=', 'SUSTITUIDO')->with('detalles')->orderBy('id'),
        ]);

        if ($request->boolean('pdf')) {
            if ($entrega->hu->isEmpty()) {
                return back()->with('error', 'La liberación no tiene pallets para imprimir.');
            }

            $pdf = Pdf::loadView('wms.produccion.pallet-etiquetas-pdf', compact('entrega'))
                ->setPaper([0, 0, 226.7716535, 226.7716535], 'portrait')
                ->setOption('isRemoteEnabled', true)
                ->setOption('dpi', 203);

            $documento = $entrega->documento?->id_documento ?? ('RG-CB-36-' . $entrega->id);

            return $pdf->stream('ETIQUETAS-PALLETS-' . $documento . '.pdf');
        }

        return view('wms.produccion.paletizacion-revision-detalle', compact('entrega'));
    }


    public function corregirDiscrepancias(WmsEntregaProduccion $entrega)
    {
        $this->validarAlmacen($entrega);
        abort_unless(auth()->user()->can('wms.produccion.liberar'), 403);
        abort_unless((int) $entrega->created_id === (int) auth()->id(), 403, 'Solo la persona que registró la liberación puede corregirla.');

        if (!in_array($entrega->estado, ['OBSERVADA', 'PENDIENTE_CORRECCION'], true)) {
            return redirect()->route('wms.produccion.liberacion.create')
                ->with('error', 'La liberación no tiene discrepancias pendientes de corrección.');
        }

        if ($entrega->posteado_erp_at !== null || $entrega->hu()->where('estado', '!=', 'SUSTITUIDO')->where(function ($q) {
            $q->whereNotNull('ubicacion_id')->orWhereNotIn('estado', ['PALETIZADO', 'PENDIENTE_VERIFICACION']);
        })->exists()) {
            return redirect()->route('wms.produccion.liberacion.create')
                ->with('error', 'La corrección solo está disponible antes de movimientos físicos y del ingreso al SAS-ERP.');
        }

        $entrega->load('documento');
        $discrepancias = WmsHuDiscrepancia::query()
            ->with(['hu.detalles', 'detalle'])
            ->where('entrega_id', $entrega->id)
            ->where('estado', 'PENDIENTE_CORRECCION')
            ->orderBy('hu_id')
            ->orderBy('id')
            ->get()
            ->groupBy('hu_id');

        if ($discrepancias->isEmpty()) {
            return redirect()->route('wms.produccion.liberacion.create')
                ->with('error', 'La liberación no tiene discrepancias pendientes de corrección.');
        }

        return view('wms.produccion.corregir-discrepancias', compact('entrega', 'discrepancias'));
    }

    public function guardarCorreccionDiscrepancias(WmsEntregaProduccion $entrega, Request $request)
    {
        $this->validarAlmacen($entrega);
        abort_unless(auth()->user()->can('wms.produccion.liberar'), 403);
        abort_unless((int) $entrega->created_id === (int) auth()->id(), 403, 'Solo la persona que registró la liberación puede corregirla.');

        $data = $request->validate([
            'hu_id' => ['required', 'integer'],
            'detalles' => ['required', 'array', 'min:1'],
            'detalles.*.id' => ['required', 'integer'],
            'detalles.*.codigo' => ['required', 'string', 'max:30'],
            'detalles.*.calidad' => ['required', 'string', 'in:EXTRA,COMERCIAL,ECONOMICO'],
            'detalles.*.cantidad' => ['required', 'integer', 'min:0'],
            'detalles.*.tono' => ['nullable', 'digits_between:1,3'],
            'detalles.*.calibre' => ['nullable', 'digits_between:1,2'],
            'observacion_correccion' => ['required', 'string', 'min:3', 'max:1000'],
        ]);

        try {
            $resultado = DB::transaction(function () use ($entrega, $data) {
                $actual = WmsEntregaProduccion::query()->lockForUpdate()->findOrFail($entrega->id);
                if (!in_array($actual->estado, ['OBSERVADA', 'PENDIENTE_CORRECCION'], true) || $actual->posteado_erp_at !== null) {
                    throw new RuntimeException('La liberación cambió de estado y ya no puede corregirse por este flujo.');
                }
                if ((int) $actual->created_id !== (int) auth()->id()) {
                    throw new RuntimeException('La corrección debe realizarla la persona que registró la liberación.');
                }
                if ($actual->hu()->where('estado', '!=', 'SUSTITUIDO')->where(function ($q) {
                    $q->whereNotNull('ubicacion_id')->orWhereNotIn('estado', ['PALETIZADO', 'PENDIENTE_VERIFICACION']);
                })->exists()) {
                    throw new RuntimeException('La liberación tiene pallets ubicados o con movimientos físicos.');
                }

                $hu = \App\Models\WmsHu::query()
                    ->where('entrega_id', $actual->id)
                    ->whereKey((int) $data['hu_id'])
                    ->where('estado', '!=', 'SUSTITUIDO')
                    ->with('detalles')
                    ->lockForUpdate()
                    ->firstOrFail();

                $incidencias = WmsHuDiscrepancia::query()
                    ->where('entrega_id', $actual->id)
                    ->where('hu_id', $hu->id)
                    ->where('estado', 'PENDIENTE_CORRECCION')
                    ->lockForUpdate()
                    ->get();

                if ($incidencias->isEmpty()) {
                    throw new RuntimeException('Este pallet ya no tiene discrepancias pendientes de corrección.');
                }

                $idsRecibidos = collect($data['detalles'])->pluck('id')->map(fn ($id) => (int) $id)->sort()->values();
                $idsEsperados = $hu->detalles->pluck('id')->map(fn ($id) => (int) $id)->sort()->values();
                if ($idsRecibidos->all() !== $idsEsperados->all()) {
                    throw new RuntimeException('Los detalles del pallet cambiaron. Recargue la pantalla antes de guardar.');
                }

                $almacen = $this->context->almacen();
                $detallesCorregidos = [];
                $cambioComposicion = false;
                $hayCambio = false;
                foreach ($data['detalles'] as $linea) {
                    $detalle = $hu->detalles->firstWhere('id', (int) $linea['id']);
                    $codigo = strtoupper(trim($linea['codigo']));
                    $calidad = strtoupper(trim($linea['calidad']));
                    $producto = $this->catalogo->validar($almacen, $codigo, $hu->formato, $calidad);
                    $tono = $calidad === 'EXTRA' ? ($linea['tono'] ?? null) : null;
                    $calibre = $calidad === 'EXTRA' ? ($linea['calibre'] ?? null) : null;
                    $lote = $this->loteCorregido($actual->fecha_entrega, $calidad, $tono, $calibre);

                    $cantidadDetalle = (int) $linea['cantidad'];
                    if ($codigo !== strtoupper((string) $detalle->codigo)
                        || $calidad !== strtoupper((string) $detalle->calidad)
                        || $lote !== (string) $detalle->lote) {
                        $cambioComposicion = true;
                        $hayCambio = true;
                    }
                    if ($cantidadDetalle !== (int) $detalle->cantidad) {
                        $hayCambio = true;
                    }
                    if ($cantidadDetalle === 0) {
                        $cambioComposicion = true;
                        $hayCambio = true;
                        continue;
                    }

                    $detallesCorregidos[] = [
                        'id' => $detalle->id,
                        'entrega_detalle_id' => $detalle->entrega_detalle_id,
                        'codigo' => $codigo,
                        'lote' => $lote,
                        'descripcion' => $producto->descripcion,
                        'descripcion2' => $producto->descripcion2,
                        'formato' => $hu->formato,
                        'calidad' => $calidad,
                        'cantidad' => $cantidadDetalle,
                    ];
                }

                if (!$hayCambio) {
                    throw new RuntimeException('No se detectaron cambios en este pallet. Modifique los datos observados antes de guardar.');
                }

                $cantidadTotal = collect($detallesCorregidos)->sum('cantidad');
                if ($cantidadTotal <= 0) {
                    throw new RuntimeException('La corrección debe dejar al menos una caja en el pallet.');
                }
                $discrepanciaCantidad = $incidencias->firstWhere('tipo', 'CANTIDAD');
                if ($discrepanciaCantidad && $cantidadTotal !== (int) $discrepanciaCantidad->valor_observado) {
                    throw new RuntimeException(
                        'La suma de cantidades corregidas (' . $cantidadTotal . ') debe coincidir con la cantidad física observada (' . (int) $discrepanciaCantidad->valor_observado . ').'
                    );
                }
                if ($cantidadTotal > (int) $hu->capacidad_estandar) {
                    throw new RuntimeException('La cantidad corregida supera la capacidad configurada del pallet.');
                }

                $snapshotAnterior = $hu->detalles->map(fn ($d) => [
                    'id' => $d->id, 'codigo' => $d->codigo, 'lote' => $d->lote,
                    'calidad' => $d->calidad, 'cantidad' => (int) $d->cantidad,
                ])->values()->all();
                $snapshotNuevo = collect($detallesCorregidos)->map(fn ($d) => [
                    'codigo' => $d['codigo'], 'lote' => $d['lote'],
                    'calidad' => $d['calidad'], 'cantidad' => $d['cantidad'],
                ])->values()->all();

                $huDestino = $hu;
                if ($cambioComposicion) {
                    // Cambiar producto/calidad/lote modifica la identidad física del HU:
                    // se conserva el historial del código anterior y se emite un nuevo correlativo.
                    $numero = app(\App\Services\PalletCorrelativoService::class)
                        ->generarSiguientes($actual->almacen, 1)[0];
                    $hu->update(['estado' => 'SUSTITUIDO', 'update_id' => auth()->id()]);
                    $huDestino = \App\Models\WmsHu::create([
                        'numero' => $numero,
                        'entrega_id' => $actual->id,
                        'almacen_id' => $actual->almacen_id,
                        'ubicacion_id' => null,
                        'formato' => $hu->formato,
                        'capacidad_estandar' => $hu->capacidad_estandar,
                        'cantidad_total' => $cantidadTotal,
                        'tipo' => $cantidadTotal === (int) $hu->capacidad_estandar ? 'COMPLETO' : 'SALDO',
                        'estado' => 'PALETIZADO',
                        'created_id' => auth()->id(),
                        'update_id' => auth()->id(),
                    ]);
                    foreach ($detallesCorregidos as $detalleNuevo) {
                        \App\Models\WmsHuDetalle::create([
                            'hu_id' => $huDestino->id,
                            'entrega_detalle_id' => $detalleNuevo['entrega_detalle_id'],
                            'codigo' => $detalleNuevo['codigo'],
                            'lote' => $detalleNuevo['lote'],
                            'descripcion' => $detalleNuevo['descripcion'],
                            'descripcion2' => $detalleNuevo['descripcion2'],
                            'formato' => $detalleNuevo['formato'],
                            'calidad' => $detalleNuevo['calidad'],
                            'cantidad' => $detalleNuevo['cantidad'],
                        ]);
                    }
                } else {
                    foreach ($detallesCorregidos as $detalleNuevo) {
                        \App\Models\WmsHuDetalle::query()->whereKey($detalleNuevo['id'])->update([
                            'cantidad' => $detalleNuevo['cantidad'],
                        ]);
                    }
                    $hu->update([
                        'cantidad_total' => $cantidadTotal,
                        'tipo' => $cantidadTotal === (int) $hu->capacidad_estandar ? 'COMPLETO' : 'SALDO',
                        'update_id' => auth()->id(),
                    ]);
                }

                $verificacionAnterior = WmsHuVerificacion::query()->where('hu_id', $hu->id)->first();
                foreach ($incidencias as $incidencia) {
                    $incidencia->update([
                        'estado' => 'CORREGIDA',
                        'corregido_id' => auth()->id(),
                        'corregido_at' => now(),
                        'valor_anterior' => json_encode($snapshotAnterior, JSON_UNESCAPED_UNICODE),
                        'valor_corregido' => json_encode([
                            'observacion_correccion' => $data['observacion_correccion'],
                            'pallet_anterior' => $hu->numero,
                            'pallet_actual' => $huDestino->numero,
                            'detalles' => $snapshotNuevo,
                        ], JSON_UNESCAPED_UNICODE),
                        'verificacion_anterior' => $verificacionAnterior
                            ? json_encode(array_intersect_key($verificacionAnterior->getAttributes(), array_flip([
                                'cantidad_esperada', 'cantidad_verificada', 'diferencia', 'resultado',
                                'observacion', 'verificado_id', 'verificado_at',
                            ])), JSON_UNESCAPED_UNICODE)
                            : null,
                        'observacion' => trim((string) $incidencia->observacion . ' | Corrección: ' . $data['observacion_correccion']),
                    ]);
                }

                WmsHuVerificacion::query()->where('hu_id', $hu->id)->delete();
                $pendientes = WmsHuDiscrepancia::query()
                    ->where('entrega_id', $actual->id)
                    ->where('estado', 'PENDIENTE_CORRECCION')
                    ->exists();

                $actual->update([
                    'estado' => $pendientes ? 'OBSERVADA' : 'PENDIENTE_REVERIFICACION',
                    'verificado_at' => null,
                    'verificado_id' => null,
                    'update_id' => auth()->id(),
                ]);

                return [
                    'numero_anterior' => $hu->numero,
                    'numero_actual' => $huDestino->numero,
                    'sustituido' => $cambioComposicion,
                    'cantidad' => $cantidadTotal,
                    'pendientes' => $pendientes,
                ];
            });

            $mensaje = $resultado['sustituido']
                ? 'Pallet corregido. El identificador anterior fue sustituido por ' . $resultado['numero_actual'] . '.'
                : 'Pallet corregido: ' . $resultado['numero_actual'] . '.';
            $mensaje .= $resultado['pendientes']
                ? ' Quedan otras discrepancias pendientes en la liberación.'
                : ' Ya puede iniciar la reverificación de los pallets corregidos.';

            return redirect()
                ->route('wms.produccion.liberacion.create')
                ->with('success', $mensaje);
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    private function loteCorregido($fecha, string $calidad, ?string $tono, ?string $calibre): string
    {
        $fechaCodigo = Carbon::parse($fecha)->format('ymd');
        if ($calidad !== 'EXTRA') {
            return $fechaCodigo . '-NNNNN';
        }
        if ($tono === null || $calibre === null) {
            throw new RuntimeException('Los productos EXTRA requieren tono y calibre.');
        }

        return sprintf('%s-%03d%02d', $fechaCodigo, (int) $tono, (int) $calibre);
    }

    public function pdf(WmsEntregaProduccion $entrega)
    {
        $this->validarAlmacen($entrega);

        $entrega->load(['documento.tipo', 'almacen', 'detalles']);

        $pdf = Pdf::loadView('wms.produccion.liberacion-pdf', compact('entrega'))
            ->setPaper('letter', 'portrait');

        $documento = $entrega->documento?->id_documento ?? ('RG-CB-36-' . $entrega->id);

        return $pdf->stream('RG-CB-36-' . $documento . '.pdf');
    }

    public function buscarProductos(Request $request)
    {
        $q = trim((string) $request->get('q'));
        $formato = strtoupper(trim((string) $request->get('formato')));
        $calidad = strtoupper(trim((string) $request->get('calidad')));

        if ($formato === '' || $calidad === '') {
            return response()->json(['results' => []]);
        }

        try {
            $productos = $this->catalogo->buscar(
                $this->context->almacen(),
                $q,
                $formato,
                $calidad
            );

            return response()->json(['results' => $productos->values()]);
        } catch (RuntimeException $e) {
            return response()->json([
                'results' => [],
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'results' => [],
                'message' => 'No fue posible consultar el catálogo de productos. Revise la conexión al maestro de inventario.',
            ], 500);
        }
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->can('wms.produccion.liberar'), 403);

        $data = $request->validate([
            'fecha_entrega' => ['required', 'date'],
            'formato' => ['required', 'string', 'max:20'],
            'folio_fisico' => ['nullable', 'string', 'max:30'],
            'observaciones' => ['nullable', 'string', 'max:2000'],
            'lineas' => ['required', 'array', 'min:1'],
            'lineas.*.codigo' => ['required', 'string', 'max:30'],
            'lineas.*.calidad' => ['required', 'string', 'in:EXTRA,COMERCIAL,ECONOMICO'],
            'lineas.*.cantidad' => ['required', 'integer', 'min:1'],
            'lineas.*.tono' => ['nullable', 'integer', 'min:0', 'max:999'],
            'lineas.*.calibre' => ['nullable', 'integer', 'min:0', 'max:99'],
        ]);

        try {
            foreach ($data['lineas'] as $linea) {
                $this->catalogo->validar(
                    $this->context->almacen(),
                    $linea['codigo'],
                    $data['formato'],
                    $linea['calidad']
                );
            }

            $entrega = $this->service->crear($data);

            return response()->json([
                'ok' => true,
                'message' => 'Liberación RG-CB-36 creada correctamente.',
                'redirect' => route('wms.produccion.liberacion.create'),
                'documento' => $entrega->documento?->id_documento,
                'total' => $entrega->total_declarado,
            ]);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    private function validarAlmacen(WmsEntregaProduccion $entrega): void
    {
        $almacen = $this->context->almacen();

        if ((int) $entrega->almacen_id !== (int) $almacen->id) {
            abort(403, 'La liberación no pertenece al almacén operativo del usuario.');
        }
    }
}
