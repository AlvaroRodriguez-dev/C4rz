<?php

namespace App\Services\Itam;

use App\Models\ItActivo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ItActivoService
{
    public function registrar(array $data): ItActivo
    {
        return DB::transaction(function () use ($data) {
            $codigo = $this->generarCodigo();

            $activo = ItActivo::create([
                ...$data,
                'codigo_ti' => $codigo,
                'estado_id' => DB::table('it_estados')
                    ->where('codigo', 'DISPONIBLE')
                    ->whereNull('deleted_at')
                    ->value('id'),
                'custodio_id' => null,
                'usuario_asignado_id' => null,
                'fecha_alta' => now()->toDateString(),
            ]);

            if (!$activo->estado_id) {
                throw ValidationException::withMessages([
                    'estado_id' => 'No existe el estado DISPONIBLE. Ejecute el seeder de catálogos ITAM.',
                ]);
            }

            return $activo->load(['tipoActivo', 'marca', 'modelo', 'estado', 'ubicacion']);
        }, 3);
    }

    private function generarCodigo(): string
    {
        do {
            $codigo = 'IT-' . now()->format('Y') . '-' . Str::upper(Str::random(8));
        } while (ItActivo::withTrashed()->where('codigo_ti', $codigo)->exists());

        return $codigo;
    }
}
