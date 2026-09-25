<?php

namespace App\Http\Controllers\Wms;

use App\Http\Controllers\Controller;
use App\Models\WmsConfigPallet;
use App\Services\WmsContextService;
use App\Services\WmsLiberacionProduccionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class WmsLiberacionProduccionController extends Controller
{
    public function __construct(
        private WmsContextService $context,
        private WmsLiberacionProduccionService $service
    ) {
    }

    public function create()
    {
        $formatos = WmsConfigPallet::query()->orderBy('codigo')->get();
        $almacen = $this->context->almacen();

        return view('wms.produccion.liberacion', compact('formatos', 'almacen'));
    }

    public function buscarProductos(Request $request)
    {
        $q = trim((string) $request->get('q'));
        $formato = strtoupper(trim((string) $request->get('formato')));

        if ($formato === '') {
            return response()->json(['results' => []]);
        }

        try {
            $productos = DB::connection('sisinvconsolidado2026')
                ->table('stock')
                ->where('CODIGO', 'like', '6C%')
                ->whereRaw('UPPER(SUBSTRING(CODIGO,6,4)) = ?', [$formato])
                ->when($q !== '', function ($query) use ($q) {
                    $query->where(function ($sub) use ($q) {
                        $sub->where('CODIGO', 'like', '%' . $q . '%')
                            ->orWhere('DESCRIP', 'like', '%' . $q . '%')
                            ->orWhere('DESCRIP1', 'like', '%' . $q . '%');
                    });
                })
                // El maestro de inventario actual no dispone de DESCRIP2.
                // Se conserva el campo descripcion2 en WMS, pero por ahora se
                // deja NULL hasta definir su fuente oficial.
                ->select('CODIGO', 'DESCRIP', 'DESCRIP1')
                ->orderBy('CODIGO')
                ->limit(30)
                ->get();

            return response()->json([
                'results' => $productos->map(function ($p) {
                    $calidades = [
                        '1' => 'EXTRA',
                        '2' => 'COMERCIAL',
                        '3' => 'ECONOMICO',
                        'X' => 'OTRO',
                    ];

                    $codigo = strtoupper(trim((string) $p->CODIGO));

                    return [
                        'id' => $codigo,
                        'text' => trim($codigo . ' · ' . (string) $p->DESCRIP . ' ' . (string) $p->DESCRIP1),
                        'codigo' => $codigo,
                        'descripcion' => trim((string) $p->DESCRIP . ' ' . (string) $p->DESCRIP1),
                        'descripcion2' => null,
                        'modelo' => substr($codigo, -4),
                        'calidad' => $calidades[substr($codigo, 4, 1)] ?? 'OTRO',
                    ];
                }),
            ]);
        } catch (Throwable $e) {
            Log::error('Error buscando productos para liberación RG-CB-36', [
                'usuario_id' => auth()->id(),
                'q' => $q,
                'formato' => $formato,
                'exception' => $e->getMessage(),
            ]);

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
            'lineas.*.cantidad' => ['required', 'integer', 'min:1'],
            'lineas.*.tono' => ['nullable', 'integer', 'min:0', 'max:999'],
            'lineas.*.calibre' => ['nullable', 'integer', 'min:0', 'max:99'],
        ]);

        try {
            $entrega = $this->service->crear($data);

            return response()->json([
                'ok' => true,
                'message' => 'Liberación RG-CB-36 creada correctamente.',
                'redirect' => route('wms.produccion.verificacion.index'),
                'documento' => $entrega->documento?->id_documento,
                'total' => $entrega->total_declarado,
            ]);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}
