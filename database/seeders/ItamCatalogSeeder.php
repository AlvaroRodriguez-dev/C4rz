<?php

namespace Database\\Seeders;

use Illuminate\\Database\\Seeder;
use Illuminate\\Support\\Facades\\DB;

class ItamCatalogSeeder extends Seeder
{
    public function run(): void
    {
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
        ];

        foreach ($tiposActivo as $tipo) {
            // No actualizar timestamps ni reactivar registros eliminados al repetir el seeder.
            if (!DB::table('it_tipos_activo')->where('codigo', $tipo['codigo'])->exists()) {
                DB::table('it_tipos_activo')->insert([...$tipo, 'created_at' => now(), 'updated_at' => now()]);
            }
        }

        // Categorías fuera del alcance actual: inactivar sin borrar su historial ni sus referencias.
        DB::table('it_tipos_activo')
            ->whereIn('codigo', ['COMPONENTE', 'REPUESTO', 'ACCESORIO'])
            ->whereNull('deleted_at')
            ->update(['deleted_at' => now(), 'updated_at' => now()]);

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
            if (!DB::table('it_ubicaciones')->where('codigo', $ubicacion['codigo'])->exists()) {
                DB::table('it_ubicaciones')->insert([
                    ...$ubicacion,
                    'ubicacion_padre_id' => null,
                    'nivel_jerarquia' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
