<?php

namespace App\Http\Controllers\Wms;

use App\Http\Controllers\Controller;
use App\Models\WmsConfigPallet;
use App\Models\WmsEntregaProduccion;
use App\Services\WmsContextService;
use App\Services\WmsLiberacionProduccionService;
use App\Services\WmsProductoCatalogoService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use RuntimeException;

class WmsLiberacionProduccionController extends Controller
{
    public function __construct(
        private WmsContextService $context,
        private WmsLiberacionProduccionService $service,
        private WmsProductoCatalogoService $catalogo
    ) {
    }

    public function index()
    {
        return view('wms.produccion.liberacion-index');
    }

    public function create(Request $request)
    {
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
