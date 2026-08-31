<?php

namespace App\Services;

use App\Models\WmsPalletCorrelativo;
use Illuminate\Support\Facades\DB;

class PalletCorrelativoService
{
    /**
     * Genera el siguiente código de pallet: {AA}{correlativo de 5 dígitos}
     * Ej: 26 + 00010 = 2600010
     */
    public function generarSiguiente(): string
    {
        return DB::transaction(function () {
            $anioActual = date('y'); // '26', '27', ...

            $registro = WmsPalletCorrelativo::where('anio', $anioActual)
                ->lockForUpdate()
                ->first();

            if (!$registro) {
                $registro = WmsPalletCorrelativo::create([
                    'anio' => $anioActual,
                    'correlativo' => $this->extraerCorrelativoBase(),
                ]);

                // Volvemos a leer con lock para mantener el flujo consistente
                $registro = WmsPalletCorrelativo::where('anio', $anioActual)
                    ->lockForUpdate()
                    ->first();
            }

            $siguiente = $registro->correlativo + 1;
            $registro->update(['correlativo' => $siguiente]);

            $correlativoFormateado = str_pad($siguiente, 5, '0', STR_PAD_LEFT);

            return "{$anioActual}{$correlativoFormateado}";
        });
    }

    /**
     * Toma los últimos 5 dígitos de PALLET_INICIO como base.
     * PALLET_INICIO=2600009 -> base = 9 (siguiente = 10 -> "00010")
     */
    private function extraerCorrelativoBase(): int
    {
        $palletInicio = (string) config('wms.pallet_inicio');

        return (int) substr($palletInicio, -5);
    }

    /**
     * Genera y CONSUME varios correlativos de una sola vez.
     */
    public function generarSiguientes(int $cantidad): array
    {
        if ($cantidad <= 0) {
            return [];
        }

        return DB::transaction(function () use ($cantidad) {
            $anioActual = date('y');

            $registro = WmsPalletCorrelativo::where('anio', $anioActual)
                ->lockForUpdate()
                ->first();

            if (!$registro) {
                WmsPalletCorrelativo::create([
                    'anio' => $anioActual,
                    'correlativo' => $this->extraerCorrelativoBase(),
                ]);
                $registro = WmsPalletCorrelativo::where('anio', $anioActual)
                    ->lockForUpdate()
                    ->first();
            }

            $inicio = $registro->correlativo;
            $registro->update(['correlativo' => $inicio + $cantidad]);

            $resultado = [];
            for ($i = 1; $i <= $cantidad; $i++) {
                $correlativoFormateado = str_pad($inicio + $i, 5, '0', STR_PAD_LEFT);
                $resultado[] = "{$anioActual}{$correlativoFormateado}";
            }

            return $resultado;
        });
    }
}
