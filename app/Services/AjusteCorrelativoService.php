<?php

namespace App\Services;

use App\Models\WmsAjusteCorrelativo;
use Illuminate\Support\Facades\DB;

class AjusteCorrelativoService
{
    /**
     * Genera el siguiente código sintético: AJUSTE-{AAAA}-{correlativo de 5 dígitos}
     * Ej: AJUSTE-2026-00001
     */
    public function generarSiguiente(): string
    {
        return DB::transaction(function () {
            $anioActual = date('Y');

            $registro = WmsAjusteCorrelativo::where('anio', $anioActual)
                ->lockForUpdate()
                ->first();

            if (!$registro) {
                WmsAjusteCorrelativo::create(['anio' => $anioActual, 'correlativo' => 0]);
                $registro = WmsAjusteCorrelativo::where('anio', $anioActual)
                    ->lockForUpdate()
                    ->first();
            }

            $siguiente = $registro->correlativo + 1;
            $registro->update(['correlativo' => $siguiente]);

            $correlativoFormateado = str_pad($siguiente, 5, '0', STR_PAD_LEFT);

            return "AJUSTE-{$anioActual}-{$correlativoFormateado}";
        });
    }
}