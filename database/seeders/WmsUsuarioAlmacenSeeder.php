<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WmsUsuarioAlmacenSeeder extends Seeder
{
    public function run(): void
    {
        $almacen = DB::table('wms_almacenes')
            ->where('codigo', '110')
            ->where('activo', true)
            ->first();

        if (!$almacen) {
            throw new \RuntimeException('No existe el almacén WMS 110 activo.');
        }

        $usuarioId = 1;

        DB::table('wms_usuario_almacenes')->updateOrInsert(
            [
                'user_id' => $usuarioId,
                'almacen_id' => $almacen->id,
            ],
            [
                'activo' => true,
                'es_principal' => true,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }
}
