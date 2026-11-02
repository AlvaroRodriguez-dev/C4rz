<?php

namespace App\Http\Controllers\Wms;

use Illuminate\Validation\ValidationException;
use App\Http\Controllers\Controller;
use App\Services\WmsContextService;
use App\Services\WmsRecepcionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class WmsRecepcionController extends Controller
{
    public function __construct(
        private readonly WmsRecepcionService $recepcionService,
        private readonly WmsContextService $context
    ) {}

    public function create(): View
    {
        // La vista es independiente del ingreso con nota.
        return view('wms.recepciones.create');
    }

    public function buscar(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'q' => ['required', 'string', 'min:1', 'max:50'],
        ]);

        $codigoAlmacen = $this->context->codigo();

        $recepciones = $this->recepcionService->buscar(
            $datos['q'],
            $codigoAlmacen
        );

        return response()->json([
            'results' => $recepciones->map(fn($recepcion) => [
                'id' => $recepcion->id,
                'text' => $recepcion->id
                    . ' · '
                    . $recepcion->fecha
                    . ' · Agencia '
                    . $recepcion->agencia,
                'rfecha' => $recepcion->fecha,
            ])->values(),
        ]);
    }

    public function detalle(string $id): JsonResponse
    {
        try {
            $items = $this->recepcionService->detalle($id);

            return response()->json([
                'recepcion' => $id,
                'items' => $items,
                'total_cajas_pendientes' => $items->sum(
                    'cantidad_pendiente'
                ),
            ]);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        } catch (RuntimeException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 409);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'No fue posible consultar el detalle de la recepción.',
            ], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'recepcion' => ['required', 'string', 'max:20'],

            'grupos' => ['required', 'array', 'min:1'],

            'grupos.*.galpon' => ['required', 'string', 'max:20'],
            'grupos.*.ubicacion' => ['required', 'string', 'max:20'],

            'grupos.*.items' => ['required', 'array', 'min:1'],

            'grupos.*.items.*.codigo' => [
                'required',
                'string',
                'max:30',
            ],

            'grupos.*.items.*.clote' => [
                'required',
                'string',
                'max:30',
            ],

            'grupos.*.items.*.cantidad' => [
                'required',
                'integer',
                'min:1',
            ],

            'grupos.*.items.*.observacion_exceso' => [
                'nullable',
                'string',
                'max:500',
            ],

            'grupos.*.items.*.observacion_lote' => [
                'nullable',
                'string',
                'max:500',
            ],

            'grupos.*.items.*.asignaciones' => [
                'required',
                'array',
                'min:1',
            ],

            'grupos.*.items.*.asignaciones.*.id_detalle_origen' => [
                'required',
                'integer',
                'min:1',
            ],

            'grupos.*.items.*.asignaciones.*.cantidad' => [
                'required',
                'integer',
                'min:1',
            ],
        ]);

        try {
            $resultado = $this->recepcionService->registrar(
                $datos['recepcion'],
                $datos['grupos']
            );

            return response()->json($resultado, 201);
                } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Los datos enviados no superan las validaciones.',
                'errors' => $e->errors(),
            ], 422);

        } catch (InvalidArgumentException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);

        } catch (RuntimeException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 409);

        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'No fue posible registrar el ingreso por recepción.',
            ], 500);
        }
    }
}
