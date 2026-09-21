<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ItamCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $tiposActivo = [
            ['codigo' => 'LAPTOP', 'descripcion' => 'Laptop', 'requiere_serial' => true],
            ['codigo' => 'PC', 'descripcion' => 'PC de escritorio', 'requiere_serial' => true],
            ['codigo' => 'MONITOR', 'descripcion' => 'Monitor', 'requiere_serial' => true],
            ['codigo' => 'IMPRESORA', 'descripcion' => 'Impresora', 'requiere_serial' => true],
            ['codigo' => 'SERVIDOR', 'descripcion' => 'Servidor', 'requiere_serial' => true],
            ['codigo' => 'SWITCH', 'descripcion' => 'Switch', 'requiere_serial' => true],
            ['codigo' => 'ROUTER', 'descripcion' => 'Router', 'requiere_serial' => true],
            ['codigo' => 'FIREWALL', 'descripcion' => 'Firewall', 'requiere_serial' => true],
            ['codigo' => 'ACCESS_POINT', 'descripcion' => 'Access Point', 'requiere_serial' => true],
            ['codigo' => 'UPS', 'descripcion' => 'UPS', 'requiere_serial' => true],
            ['codigo' => 'NAS', 'descripcion' => 'NAS', 'requiere_serial' => true],
            ['codigo' => 'PROYECTOR', 'descripcion' => 'Proyector', 'requiere_serial' => true],
            ['codigo' => 'CAMARA', 'descripcion' => 'Cámara', 'requiere_serial' => true],
            ['codigo' => 'TELEFONO', 'descripcion' => 'Teléfono', 'requiere_serial' => true],
            ['codigo' => 'TABLET', 'descripcion' => 'Tablet', 'requiere_serial' => true],
            ['codigo' => 'COMPONENTE', 'descripcion' => 'Componente', 'requiere_serial' => false],
            ['codigo' => 'REPUESTO', 'descripcion' => 'Repuesto', 'requiere_serial' => false],
            ['codigo' => 'ACCESORIO', 'descripcion' => 'Accesorio', 'requiere_serial' => false],
        ];

        foreach ($tiposActivo as $tipo) {
            DB::table('it_tipos_activo')->updateOrInsert(
                ['codigo' => $tipo['codigo']],
                [
                    'descripcion' => $tipo['descripcion'],
                    'requiere_serial' => $tipo['requiere_serial'],
                    'deleted_at' => null,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
        }

        $ubicaciones = [
            ['codigo' => 'STOCK-TI', 'descripcion' => 'Stock TI'],
            ['codigo' => 'STOCK-REP-TI', 'descripcion' => 'Stock Repuestos TI'],
            ['codigo' => 'PREPARACION-TI', 'descripcion' => 'Preparación TI'],
            ['codigo' => 'CUSTODIA-TI', 'descripcion' => 'Custodia TI'],
            ['codigo' => 'REPARACION-TI', 'descripcion' => 'Reparación TI'],
            ['codigo' => 'PARA-BAJA', 'descripcion' => 'Para Baja'],
            ['codigo' => 'DATA-CENTER', 'descripcion' => 'Data Center'],
            ['codigo' => 'OFICINA-SISTEMAS', 'descripcion' => 'Oficina Sistemas'],
            ['codigo' => 'ALMACEN-TI', 'descripcion' => 'Almacén TI'],
        ];

        foreach ($ubicaciones as $ubicacion) {
            DB::table('it_ubicaciones')->updateOrInsert(
                ['codigo' => $ubicacion['codigo']],
                [
                    'descripcion' => $ubicacion['descripcion'],
                    'ubicacion_padre_id' => null,
                    'nivel_jerarquia' => 1,
                    'deleted_at' => null,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
        }
    }
}
