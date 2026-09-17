<?php

namespace App\Services;

use App\Models\NominaConfiguracionSalarial;
use App\Models\NominaPersonal;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class NominaConfiguracionSalarialService
{
    public function guardar(
        NominaPersonal $personal,
        string $fechaInicio,
        float $haberBasico,
        ?int $categoriaId = null,
        ?string $modalidadRemuneracion = null,
        ?float $salarioCotizable = null,
        ?string $observaciones = null,
    ): NominaConfiguracionSalarial {
        if ($haberBasico < 0) {
            throw new InvalidArgumentException('El haber básico no puede ser negativo.');
        }

        if ($salarioCotizable !== null && $salarioCotizable < 0) {
            throw new InvalidArgumentException('El salario cotizable no puede ser negativo.');
        }

        $inicio = Carbon::parse($fechaInicio)->startOfDay();

        return DB::transaction(function () use (
            $personal,
            $inicio,
            $haberBasico,
            $categoriaId,
            $modalidadRemuneracion,
            $salarioCotizable,
            $observaciones
        ) {
            $actual = NominaConfiguracionSalarial::query()
                ->where('rh_personal_id', $personal->id)
                ->whereNull('fecha_fin')
                ->latest('fecha_inicio')
                ->first();

            if ($actual && $actual->fecha_inicio->isSameDay($inicio)) {
                $actual->update([
                    'haber_basico' => $haberBasico,
                    'categoria_id' => $categoriaId,
                    'modalidad_remuneracion' => $modalidadRemuneracion,
                    'salario_cotizable' => $salarioCotizable,
                    'observaciones' => $observaciones,
                ]);

                return $actual->fresh('categoria');
            }

            if ($actual && $inicio->lessThanOrEqualTo($actual->fecha_inicio)) {
                throw new InvalidArgumentException(
                    'La nueva fecha de inicio debe ser posterior a la configuración salarial vigente.'
                );
            }

            if ($actual) {
                $actual->update([
                    'fecha_fin' => $inicio->copy()->subDay()->toDateString(),
                ]);
            }

            return NominaConfiguracionSalarial::create([
                'rh_personal_id' => $personal->id,
                'fecha_inicio' => $inicio->toDateString(),
                'fecha_fin' => null,
                'haber_basico' => $haberBasico,
                'categoria_id' => $categoriaId,
                'modalidad_remuneracion' => $modalidadRemuneracion,
                'salario_cotizable' => $salarioCotizable,
                'observaciones' => $observaciones,
            ]);
        });
    }
}
