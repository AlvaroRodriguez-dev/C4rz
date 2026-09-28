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
     * {codigo_almacen}-{anio}{correlativo de 5 dígitos}
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

        $registro = WmsPalletCorrelativo::create([
            'almacen_id' => $almacen->id,
            'anio' => $anio,
            'correlativo' => $this->extraerCorrelativoBase($almacen),
        ]);

        return WmsPalletCorrelativo::query()
            ->whereKey($registro->id)
            ->lockForUpdate()
            ->firstOrFail();
    }

    /**
     * Conservamos la compatibilidad del valor inicial existente.
     * La configuración histórica puede seguir proporcionando la base
     * cuando no exista todavía un correlativo para el almacén/año.
     */
    private function extraerCorrelativoBase(WmsAlmacen $almacen): int
    {
        $palletInicio = (string) config('wms.pallet_inicio');

        if ($palletInicio === '') {
            throw new RuntimeException(
                "No existe configuración de pallet inicial para el almacén {$almacen->codigo}."
            );
        }

        return (int) substr($palletInicio, -5);
    }

    private function formatear(WmsAlmacen $almacen, string $anio, int $correlativo): string
    {
        return sprintf(
            '%s-%s%05d',
            $almacen->codigo,
            $anio,
            $correlativo
        );
    }
}
