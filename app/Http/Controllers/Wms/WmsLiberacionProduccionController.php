<?php

namespace App\Http\Controllers\Wms;

use App\Http\Controllers\Controller;
use App\Models\WmsConfigPallet;
use App\Services\WmsContextService;
use App\Services\WmsLiberacionProduccionService;
use App\Services\WmsProductoCatalogoService;
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
        if (!$request->boolean('nuevo')) {
            return redirect()->route('wms.produccion.liberacion.create');
        }

        $formatos = WmsConfigPallet::query()->orderBy('codigo')->get();
        $almacen = $this->context->almacen();

        return view('wms.produccion.liberacion', compact('formatos', 'almacen'));
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
}
