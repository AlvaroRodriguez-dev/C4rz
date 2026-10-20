<?php

namespace App\Http\Controllers\Wms;

use App\Http\Controllers\Controller;
use App\Models\WmsReubicacion;
use App\Services\ReubicacionService;
use App\Services\SaldoService;
use App\Services\WmsStockLockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class WmsReubicacionController extends Controller
{
    public function __construct(
        private SaldoService $saldoService,
        private ReubicacionService $reubicacionService,
        private WmsStockLockService $stockLockService
    ) {}


    public function index()
    {
        return view('wms.reubicacion.index');
    }

    /** AJAX - contenido de un pallet, reutilizado para escoger el origen. */
    public function contenidoPallet(string $pallet)
    {
        $items = $this->saldoService->calcular(['pallet' => $pallet]);

        return response()->json(['pallet' => $pallet, 'items' => $items->values()]);
    }

    /** AJAX - resuelve la ubicación actual de un pallet destino (si ya existe). */
    public function ubicacionDePallet(string $pallet)
    {
        $ubicacion = $this->saldoService->ubicacionActualDePallet($pallet);

        return response()->json(['pallet' => $pallet, 'ubicacion' => $ubicacion]);
    }

    /**
     * MODO 1: Traspaso de PALLET COMPLETO a otra ubicación.
     * Mueve todos los códigos/lotes del pallet origen a la nueva ubicación,
     * manteniendo el mismo número de pallet.
     */
    public function storePalletCompleto(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'pallet_origen' => ['required', 'string', 'max:30'],
            'galpon_destino' => ['required', 'string', 'max:20'],
            'ubicacion_destino' => ['required', 'string', 'max:20'],
            'observacion' => ['nullable', 'string', 'max:150'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();

        $resultado = DB::transaction(function () use ($data, $request) {
            $pallet = $data['pallet_origen'];
            $this->stockLockService->bloquearPallets([$pallet]);

            $respuestaAutorizacion = $this->validarMovimientoConReserva([$pallet], $request);
            if ($respuestaAutorizacion) {
                return $respuestaAutorizacion;
            }

            // El saldo se vuelve a calcular dentro de la transacción y después del bloqueo.
            $items = $this->saldoService->calcular(['pallet' => $pallet]);

            if ($items->isEmpty()) {
                return response()->json(['errors' => ['general' => ['Este pallet no tiene saldo disponible para reubicar.']]], 422);
            }

            $mismaUbicacion = $items->every(
                fn ($i) => $i['galpon'] == $data['galpon_destino']
                    && $i['ubicacion'] == $data['ubicacion_destino']
            );

            if ($mismaUbicacion) {
                return response()->json(['errors' => ['general' => ['El destino es igual a la ubicación actual del pallet.']]], 422);
            }

            $this->reubicacionService->trasladarPalletCompleto(
                $pallet,
                $data['galpon_destino'],
                $data['ubicacion_destino'],
                null,
                $data['observacion'] ?? null
            );

            return null;
        });

        if ($resultado) {
            return $resultado;
        }

        return response()->json(['message' => 'Pallet reubicado correctamente.']);
    }

    /**
     * MODO 2: Mover CANTIDAD PARCIAL de un pallet/lote hacia otro pallet destino
     * (para completar un pallet existente).
     */
    public function storeCompletarPallet(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'codigo' => ['required', 'string', 'max:30'],
            'clote' => ['nullable', 'string', 'max:30'],
            'descrip' => ['nullable', 'string', 'max:60'],
            'descrip1' => ['nullable', 'string', 'max:60'],
            'cantidad' => ['required', 'integer', 'min:1'],
            'pallet_origen' => ['required', 'string', 'max:30'],
            'almacen_origen' => ['required', 'string', 'max:10'],
            'galpon_origen' => ['required', 'string', 'max:20'],
            'ubicacion_origen' => ['required', 'string', 'max:20'],
            'pallet_destino' => ['required', 'string', 'max:30', 'different:pallet_origen'],
            'almacen_destino' => ['required', 'string', 'max:10'],
            'galpon_destino' => ['required', 'string', 'max:20'],
            'ubicacion_destino' => ['required', 'string', 'max:20'],
            'observacion' => ['nullable', 'string', 'max:150'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();

        $resultado = DB::transaction(function () use ($data, $request) {
            $pallets = [$data['pallet_origen'], $data['pallet_destino']];
            $this->stockLockService->bloquearPallets($pallets);

            $respuestaAutorizacion = $this->validarMovimientoConReserva($pallets, $request);
            if ($respuestaAutorizacion) {
                return $respuestaAutorizacion;
            }

            // Validar el saldo bajo bloqueo para no aceptar cantidades obsoletas.
            $saldoActual = $this->saldoService->saldoDeGrupo(
                $data['codigo'],
                $data['clote'] ?? null,
                $data['pallet_origen'],
                $data['almacen_origen'],
                $data['galpon_origen'],
                $data['ubicacion_origen']
            );

            if ($data['cantidad'] > $saldoActual) {
                return response()->json([
                    'errors' => ['general' => ["El pallet {$data['pallet_origen']} no tiene saldo suficiente. Disponible: {$saldoActual}."]],
                ], 422);
            }

            WmsReubicacion::create([
                'tipo' => 'completar_pallet',
                'codigo' => $data['codigo'],
                'clote' => $data['clote'] ?? null,
                'descrip' => $data['descrip'] ?? null,
                'descrip1' => $data['descrip1'] ?? null,
                'cantidad' => $data['cantidad'],
                'pallet_origen' => $data['pallet_origen'],
                'almacen_origen' => $data['almacen_origen'],
                'galpon_origen' => $data['galpon_origen'],
                'ubicacion_origen' => $data['ubicacion_origen'],
                'pallet_destino' => $data['pallet_destino'],
                'almacen_destino' => $data['almacen_destino'],
                'galpon_destino' => $data['galpon_destino'],
                'ubicacion_destino' => $data['ubicacion_destino'],
                'observacion' => $data['observacion'] ?? null,
            ]);

            return null;
        });

        if ($resultado) {
            return $resultado;
        }

        return response()->json(['message' => 'Cajas reubicadas correctamente.']);
    }


    /**
     * Un pallet reservado por una OT pendiente solo puede moverse con confirmación
     * explícita de un administrador WMS/sistema. La validación se hace dentro de
     * la transacción y después de bloquear los pallets involucrados.
     */
    private function validarMovimientoConReserva(array $pallets, Request $request)
    {
        $reservados = collect($pallets)
            ->unique()
            ->filter(fn ($pallet) => $this->stockLockService->cantidadReservada($pallet) > 0)
            ->values();

        if ($reservados->isEmpty()) {
            return null;
        }

        $supervisor = $request->user()?->hasAnyRole(['WMS-ADMIN', 'SIS-ADMIN']) ?? false;
        $confirmado = $request->boolean('confirmar_reubicacion_reservada');

        if (!$supervisor || !$confirmado) {
            return response()->json([
                'requires_supervisor_confirmation' => true,
                'pallets_reservados' => $reservados,
                'errors' => [
                    'general' => [
                        'Uno o más pallets tienen cajas reservadas en una Orden de Trabajo pendiente. Se requiere confirmación explícita de un supervisor WMS.'
                    ]
                ],
            ], 409);
        }

        return null;
    }
}
