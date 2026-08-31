<?php

namespace App\Http\Controllers\Wms;

use App\Http\Controllers\Controller;
use App\Models\WmsReubicacion;
use App\Services\ReubicacionService;
use App\Services\SaldoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class WmsReubicacionController extends Controller
{
    public function __construct(private SaldoService $saldoService, private ReubicacionService $reubicacionService) {}


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

        $items = $this->saldoService->calcular(['pallet' => $data['pallet_origen']]);

        if ($items->isEmpty()) {
            return response()->json(['errors' => ['general' => ['Este pallet no tiene saldo disponible para reubicar.']]], 422);
        }

        $mismaUbicacion = $items->every(
            fn($i) =>
            $i['galpon'] == $data['galpon_destino'] && $i['ubicacion'] == $data['ubicacion_destino']
        );

        if ($mismaUbicacion) {
            return response()->json(['errors' => ['general' => ['El destino es igual a la ubicación actual del pallet.']]], 422);
        }

        DB::transaction(function () use ($data) {
            $this->reubicacionService->trasladarPalletCompleto(
                $data['pallet_origen'],
                $data['galpon_destino'],
                $data['ubicacion_destino'],
                null,
                $data['observacion'] ?? null
            );
        });

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

        return response()->json(['message' => 'Cajas reubicadas correctamente.']);
    }


    
}
