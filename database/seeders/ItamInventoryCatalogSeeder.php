<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ItamInventoryCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $estados = [
            ['codigo' => 'DISPONIBLE', 'descripcion' => 'Disponible', 'permite_asignacion' => true, 'permite_traslado' => true],
            ['codigo' => 'ASIGNADO', 'descripcion' => 'Asignado', 'permite_asignacion' => false, 'permite_traslado' => true],
            ['codigo' => 'MANTENIMIENTO', 'descripcion' => 'En mantenimiento', 'permite_asignacion' => false, 'permite_traslado' => true],
            ['codigo' => 'FUERA_SERVICIO', 'descripcion' => 'Fuera de servicio', 'permite_asignacion' => false, 'permite_traslado' => false],
            ['codigo' => 'PARA_BAJA', 'descripcion' => 'Pendiente de baja', 'permite_asignacion' => false, 'permite_traslado' => false],
            ['codigo' => 'BAJA', 'descripcion' => 'Dado de baja', 'permite_asignacion' => false, 'permite_traslado' => false],
        ];

        foreach ($estados as $estado) {
            if (!DB::table('it_estados')->where('codigo', $estado['codigo'])->exists()) {
                DB::table('it_estados')->insert([
                    ...$estado,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}