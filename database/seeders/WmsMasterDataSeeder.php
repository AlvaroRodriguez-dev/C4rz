<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WmsMasterDataSeeder extends Seeder
{
    public function run(): void
    {
        $ahora = now();

        $principalId = DB::table('wms_almacenes')->updateOrInsert(
            ['codigo' => '110'],
            [
                'nombre' => 'FABRIA CBBA',
                'tipo' => 'PRINCIPAL',
                'almacen_padre_id' => null,
                'prefijo_documento' => 'A',
                'activo' => true,
                'updated_at' => $ahora,
                'created_at' => $ahora,
            ]
        );

        $principal = DB::table('wms_almacenes')->where('codigo', '110')->first();

        foreach ([
            ['codigo' => '111', 'nombre' => 'FABRIA CBBA - SUBALMACEN 111'],
            ['codigo' => '112', 'nombre' => 'FABRIA CBBA - SUBALMACEN 112'],
        ] as $sub) {
            DB::table('wms_almacenes')->updateOrInsert(
                ['codigo' => $sub['codigo']],
                [
                    'nombre' => $sub['nombre'],
                    'tipo' => 'SUB',
                    'almacen_padre_id' => $principal->id,
                    'prefijo_documento' => 'A',
                    'activo' => true,
                    'updated_at' => $ahora,
                    'created_at' => $ahora,
                ]
            );
        }

        $galpones = [
            ['codigo' => 'G1', 'nombre' => 'GALPON 1', 'desde' => 1, 'hasta' => 150],
            ['codigo' => 'G2', 'nombre' => 'GALPON 2', 'desde' => 151, 'hasta' => 200],
            ['codigo' => 'G3', 'nombre' => 'GALPON 3', 'desde' => 201, 'hasta' => 500],
        ];

        foreach ($galpones as $galpon) {
            DB::table('wms_galpones')->updateOrInsert(
                [
                    'almacen_id' => $principal->id,
                    'codigo' => $galpon['codigo'],
                ],
                [
                    'nombre' => $galpon['nombre'],
                    'desde_ubicacion' => $galpon['desde'],
                    'hasta_ubicacion' => $galpon['hasta'],
                    'activo' => true,
                    'updated_at' => $ahora,
                    'created_at' => $ahora,
                ]
            );

            $galponRow = DB::table('wms_galpones')
                ->where('almacen_id', $principal->id)
                ->where('codigo', $galpon['codigo'])
                ->first();

            for ($numero = $galpon['desde']; $numero <= $galpon['hasta']; $numero++) {
                DB::table('wms_ubicaciones')->updateOrInsert(
                    [
                        'almacen_id' => $principal->id,
                        'codigo' => (string) $numero,
                    ],
                    [
                        'galpon_id' => $galponRow->id,
                        'tipo' => 'NORMAL',
                        'numero' => $numero,
                        'activo' => true,
                        'updated_at' => $ahora,
                        'created_at' => $ahora,
                    ]
                );
            }
        }

        foreach (['PREPARACION', 'DESPACHO'] as $tipo) {
            DB::table('wms_ubicaciones')->updateOrInsert(
                [
                    'almacen_id' => $principal->id,
                    'codigo' => $tipo,
                ],
                [
                    'galpon_id' => null,
                    'tipo' => $tipo,
                    'numero' => null,
                    'activo' => true,
                    'updated_at' => $ahora,
                    'created_at' => $ahora,
                ]
            );
        }
    }
}
