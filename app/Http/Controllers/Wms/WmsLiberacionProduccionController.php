<?php

namespace App\Http\Controllers\Wms;

use App\Http\Controllers\Controller;
use App\Models\WmsConfigPallet;
use App\Models\WmsEntregaDetalle;
use App\Models\WmsHuDiscrepancia;
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

        if ($entrega->estado !== 'OBSERVADA') {
            return redirect()->route('wms.produccion.liberacion.create')
                ->with('error', 'La liberación no tiene discrepancias pendientes de corrección.');
        }

        if ($entrega->posteado_erp_at !== null || $entrega->hu()->where(function ($q) {
            $q->whereNotNull('ubicacion_id')->orWhereNotIn('estado', ['PALETIZADO', 'PENDIENTE_VERIFICACION']);
        })->exists()) {
            return redirect()->route('wms.produccion.liberacion.create')
                ->with('error', 'La corrección solo está disponible antes de movimientos físicos y del ingreso al SAS-ERP.');
        }

        $entrega->load(['documento', 'detalles']);
        $discrepancias = WmsHuDiscrepancia::query()
            ->with(['hu', 'detalle'])
            ->where('entrega_id', $entrega->id)
            ->where('estado', 'PENDIENTE_CORRECCION')
            ->orderBy('id')
            ->get();

        return view('wms.produccion.corregir-discrepancias', compact('entrega', 'discrepancias'));
    }

    public function guardarCorreccionDiscrepancias(WmsEntregaProduccion $entrega, Request $request)
    {
        $this->validarAlmacen($entrega);
        abort_unless(auth()->user()->can('wms.produccion.liberar'), 403);
        abort_unless((int) $entrega->created_id === (int) auth()->id(), 403, 'Solo la persona que registró la liberación puede corregirla.');

        $data = $request->validate([
            'lineas' => ['required', 'array', 'min:1'],
            'lineas.*.id' => ['required', 'integer'],
            'lineas.*.codigo' => ['required', 'string', 'max:30'],
            'lineas.*.calidad' => ['required', 'string', 'in:EXTRA,COMERCIAL,ECONOMICO'],
            'lineas.*.cantidad_declarada' => ['required', 'integer', 'min:0'],
            'lineas.*.tono' => ['nullable', 'digits_between:1,3'],
            'lineas.*.calibre' => ['nullable', 'digits_between:1,2'],
        ]);

        try {
            $resultado = DB::transaction(function () use ($entrega, $data) {
                $actual = WmsEntregaProduccion::query()->lockForUpdate()->findOrFail($entrega->id);
                if ($actual->estado !== 'OBSERVADA' || $actual->posteado_erp_at !== null) {
                    throw new RuntimeException('La liberación cambió de estado y ya no puede corregirse por este flujo.');
                }
                if ((int) $actual->created_id !== (int) auth()->id()) {
                    throw new RuntimeException('La corrección debe realizarla la persona que registró la liberación.');
                }
                if ($actual->hu()->where(function ($q) {
                    $q->whereNotNull('ubicacion_id')->orWhereNotIn('estado', ['PALETIZADO', 'PENDIENTE_VERIFICACION']);
                })->exists()) {
                    throw new RuntimeException('La liberación tiene pallets ubicados o con movimientos físicos.');
                }

                $detalles = WmsEntregaDetalle::query()
                    ->where('entrega_id', $actual->id)
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                $idsRecibidos = collect($data['lineas'])->pluck('id')->map(fn ($id) => (int) $id)->sort()->values();
                $idsEsperados = $detalles->keys()->map(fn ($id) => (int) $id)->sort()->values();
                if ($idsRecibidos->all() !== $idsEsperados->all()) {
                    throw new RuntimeException('La lista de líneas cambió. Recargue la pantalla antes de guardar.');
                }

                $almacen = $this->context->almacen();
                $snapshot = [];
                foreach ($data['lineas'] as $linea) {
                    /** @var WmsEntregaDetalle $detalle */
                    $detalle = $detalles->get((int) $linea['id']);
                    $codigo = strtoupper(trim($linea['codigo']));
                    $calidad = strtoupper(trim($linea['calidad']));
                    $producto = $this->catalogo->validar($almacen, $codigo, $detalle->formato, $calidad);
                    $tono = $calidad === 'EXTRA' ? ($linea['tono'] ?? null) : null;
                    $calibre = $calidad === 'EXTRA' ? ($linea['calibre'] ?? null) : null;
                    $lote = $this->loteCorregido($actual->fecha_entrega, $calidad, $tono, $calibre);

                    $detalle->update([
                        'codigo' => $codigo,
                        'descripcion' => $producto->descripcion,
                        'descripcion2' => $producto->descripcion2,
                        'calidad' => $calidad,
                        'modelo' => $producto->modelo,
                        'cantidad_declarada' => (int) $linea['cantidad_declarada'],
                        'tono' => $tono,
                        'calibre' => $calibre,
                        'lote' => $lote,
                        'estado' => 'CORREGIDO',
                    ]);

                    $snapshot[] = [
                        'detalle_id' => $detalle->id,
                        'codigo' => $codigo,
                        'calidad' => $calidad,
                        'cantidad_declarada' => (int) $linea['cantidad_declarada'],
                        'tono' => $tono,
                        'calibre' => $calibre,
                        'lote' => $lote,
                    ];
                }

                if (collect($snapshot)->sum('cantidad_declarada') <= 0) {
                    throw new RuntimeException('La corrección no puede dejar la liberación sin cantidades.');
                }

                $actual->update([
                    'total_declarado' => collect($snapshot)->sum('cantidad_declarada'),
                    'update_id' => auth()->id(),
                ]);

                WmsHuDiscrepancia::query()
                    ->where('entrega_id', $actual->id)
                    ->where('estado', 'PENDIENTE_CORRECCION')
                    ->update([
                        'estado' => 'CORREGIDA',
                        'corregido_id' => auth()->id(),
                        'corregido_at' => now(),
                        'valor_corregido' => json_encode($snapshot, JSON_UNESCAPED_UNICODE),
                        'updated_at' => now(),
                    ]);

                return $this->paletizacion->reconstruirDespuesCorreccion($actual, (int) auth()->id());
            });

            return redirect()->route('wms.produccion.liberacion.create')
                ->with('success', 'Corrección guardada. Pallets conservados: ' . $resultado['conservados'] .
                    '; sustituidos: ' . $resultado['sustituidos'] . '; nuevos: ' . $resultado['nuevos'] .
                    '. Los pallets nuevos quedaron pendientes de reverificación.');
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
