<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WmsTipoDocumentoSeeder extends Seeder
{
    public function run(): void
    {
        $ahora = now();

        $tipos = [
            [
                'id' => 10,
                'codigo' => '10',
                'descripcion' => 'LIBERACION PRODUCCION FABOCE I',
                'talonario' => '0',
            ],
            [
                'id' => 11,
                'codigo' => '11',
                'descripcion' => 'LIBERACION POR RECLASIFICACION FABOCE I',
                'talonario' => '1',
            ],
            [
                'id' => 20,
                'codigo' => '20',
                'descripcion' => 'INGRESO WMS',
                'talonario' => '0',
            ],
            [
                'id' => 30,
                'codigo' => '30',
                'descripcion' => 'SALIDA WMS',
                'talonario' => '0',
            ],
        ];

        foreach ($tipos as $tipo) {
            DB::table('wms_tipo_documentos')->updateOrInsert(
                ['id' => $tipo['id']],
                [
                    'codigo' => $tipo['codigo'],
                    'descripcion' => $tipo['descripcion'],
                    'talonario' => $tipo['talonario'],
                    'updated_at' => $ahora,
                    'created_at' => $ahora,
                ]
            );
        }
    }
};