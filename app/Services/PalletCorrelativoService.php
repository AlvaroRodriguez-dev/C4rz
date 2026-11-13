<?php

namespace App\Services;

use App\Models\WmsAlmacen;
use App\Models\WmsPalletCorrelativo;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PalletCorrelativoService
{
    /**
     * Genera el siguiente código físico de pallet:
     * {codigo_almacen}-{anio}{correlativo de 4 dígitos}
     *
     * Ejemplo para el almacén 110, año 26 y correlativo 3193:
     * 110-263193
     */
    public function generarSiguiente(WmsAlmacen $almacen): string
    {
        return DB::transaction(function () use ($almacen) {
            $anioActual = date('y');
            $registro = $this->obtenerOInicializar($almacen, $anioActual);

            $siguiente = ((int) $registro->correlativo) + 1;

            $registro->update([
                'correlativo' => $siguiente,
            ]);

            return $this->formatear($almacen, $anioActual, $siguiente);
        });
    }

    /**
     * Genera y consume varios correlativos para un almacén.
     */
    public function generarSiguientes(WmsAlmacen $almacen, int $cantidad): array
    {
        if ($cantidad <= 0) {
            return [];
        }

        return DB::transaction(function () use ($almacen, $cantidad) {
            $anioActual = date('y');
            $registro = $this->obtenerOInicializar($almacen, $anioActual);

            $inicio = (int) $registro->correlativo;
            $registro->update([
                'correlativo' => $inicio + $cantidad,
            ]);

            $resultado = [];

            for ($i = 1; $i <= $cantidad; $i++) {
                $resultado[] = $this->formatear(
                    $almacen,
                    $anioActual,
                    $inicio + $i
                );
            }

            return $resultado;
        });
    }

    private function obtenerOInicializar(WmsAlmacen $almacen, string $anio): WmsPalletCorrelativo
    {
        $registro = WmsPalletCorrelativo::query()
            ->where('almacen_id', $almacen->id)
            ->where('anio', $anio)
            ->lockForUpdate()
            ->first();

        if ($registro) {
            return $registro;
        }

        // El registro histórico del almacén 110 ya contiene el correlativo real
        // desde el cual debe continuar la numeración (3192 -> 3193).
        // Para un almacén/año que todavía no tenga historial, iniciamos en 0.
        // Así cada almacén mantiene su propia secuencia independiente.
        DB::table('wms_pallet_correlativos')->insertOrIgnore([
            'almacen_id' => $almacen->id,
            'anio' => $anio,
            'correlativo' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return WmsPalletCorrelativo::query()
            ->where('almacen_id', $almacen->id)
            ->where('anio', $anio)
            ->lockForUpdate()
            ->firstOrFail();
    }

    private function formatear(WmsAlmacen $almacen, string $anio, int $correlativo): string
    {
        if ($correlativo > 9999) {
            throw new RuntimeException(
                "El correlativo de pallets del almacén {$almacen->codigo} superó el límite de 4 dígitos."
            );
        }

        return sprintf(
            '%s-%s%04d',
            $almacen->codigo,
            $anio,
            $correlativo
        );
    }
}
