<?php

namespace App\Services;

use App\Models\WmsReubicacion;
use Illuminate\Support\Carbon;

class ReubicacionService
{
    public function __construct(private SaldoService $saldoService)
    {
    }

    /**
     * Traslada TODO el contenido físico actual de un pallet a una nueva ubicación,
     * generando una fila en wms_reubicaciones por cada producto/lote que contenga.
     *
     * @param string       $pallet           Pallet de origen a trasladar completo.
     * @param string       $galponDestino    Galpón destino.
     * @param string       $ubicacionDestino Ubicación destino.
     * @param string|null  $almacenDestino   Almacén destino (por defecto, el mismo que el item).
     * @param string|null  $observacion      Nota/observación del traspaso.
     * @param int|null     $ordenTrabajoId   Si viene informado, marca esta reubicación como
     *                                       AUTOMÁTICA (generada al cerrar esa Orden de Trabajo).
     *                                       Null = reubicación manual (pantalla RE-UBICACIÓN).
     * @param Carbon|null  $timestamp        Si viene informado, se usa como created_at/updated_at
     *                                       explícito, en vez del momento exacto del guardado.
     *                                       Necesario para poder desfasar en el tiempo movimientos
     *                                       automáticos encadenados y así garantizar el orden
     *                                       cronológico correcto en el Kardex.
     */
    public function trasladarPalletCompleto(
        string $pallet,
        string $galponDestino,
        string $ubicacionDestino,
        ?string $almacenDestino = null,
        ?string $observacion = null,
        ?int $ordenTrabajoId = null,
        ?Carbon $timestamp = null
    ): int {
        $items = $this->saldoService->calcular(['pallet' => $pallet]);

        foreach ($items as $item) {
            $reubicacion = new WmsReubicacion([
                'orden_trabajo_id' => $ordenTrabajoId,
                'tipo' => 'pallet_completo',
                'codigo' => $item['codigo'],
                'clote' => $item['clote'],
                'descrip' => $item['descrip'],
                'descrip1' => $item['descrip1'],
                'cantidad' => $item['saldo'],
                'pallet_origen' => $item['pallet'],
                'almacen_origen' => $item['almacen'],
                'galpon_origen' => $item['galpon'],
                'ubicacion_origen' => $item['ubicacion'],
                'pallet_destino' => $item['pallet'],
                'almacen_destino' => $almacenDestino ?? $item['almacen'],
                'galpon_destino' => $galponDestino,
                'ubicacion_destino' => $ubicacionDestino,
                'observacion' => $observacion,
            ]);

            if ($timestamp) {
                $reubicacion->created_at = $timestamp;
                $reubicacion->updated_at = $timestamp;
            }

            $reubicacion->save();
        }

        return $items->count();
    }
}