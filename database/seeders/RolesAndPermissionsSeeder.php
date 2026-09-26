<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $wmsPermisos = [
            'wms.ingresos.create',
            'wms.ingresos.ver',
            'wms.ingresos.ajuste',
            'wms.salidas.create',
            'wms.salidas.ver',
            'wms.reubicacion',
            'wms.ordenes.trabajo',
            'wms.excepciones.despacho',
            'wms.pallet.ver',
            'wms.ubicacion.ver',
            'wms.inventario',
            'wms.kardex',
            'wms.configurar',
            'wms.produccion.liberar',
            'wms.produccion.verificar',
            'wms.produccion.paletizar',
        ];

        // Permiso separado para ejecutar la verificación física.
        // No se asigna automáticamente a WMS-ALMACEN para permitir separar
        // al usuario que registra la liberación del usuario que verifica.
        $wmsPermisosVerificacion = [
            'wms.produccion.verificar.ejecutar',
        ];

        $rrhhPermisos = [
            'rrhh.importar-usb',
            'rrhh.recuperar-datos',
            'rrhh.reporte',
            'rrhh.novedades',
        ];

        foreach (array_merge($wmsPermisos, $wmsPermisosVerificacion, $rrhhPermisos) as $permiso) {
            Permission::firstOrCreate(['name' => $permiso]);
        }

        // ================= ROLES =================

        // WMS-ADMIN: todas las opciones de WMS, incluida la ejecución de verificación.
        Role::firstOrCreate(['name' => 'WMS-ADMIN'])
            ->givePermissionTo(array_merge($wmsPermisos, $wmsPermisosVerificacion));

        // WMS-ALMACEN: acceso al módulo de producción y liberación,
        // pero la ejecución de la verificación física se asigna por separado.
        Role::firstOrCreate(['name' => 'WMS-ALMACEN'])
            ->givePermissionTo(
                collect($wmsPermisos)->reject(fn($p) => $p === 'wms.ingresos.ajuste')->all()
            );

        // WMS-MONTACARGA: solo Órdenes de Trabajo
        Role::firstOrCreate(['name' => 'WMS-MONTACARGA'])
            ->givePermissionTo(['wms.ordenes.trabajo']);

        // RRHH-ADMIN: todas las opciones de Biométricos
        Role::firstOrCreate(['name' => 'RRHH-ADMIN'])
            ->givePermissionTo($rrhhPermisos);

        // RRHH-USER: todo Biométricos excepto Importar desde USB
        Role::firstOrCreate(['name' => 'RRHH-USER'])
            ->givePermissionTo(
                collect($rrhhPermisos)->reject(fn($p) => $p === 'rrhh.importar-usb')->all()
            );

        // SIS-ADMIN: pendiente de definir permisos de "recepción" a excluir
        Role::firstOrCreate(['name' => 'SIS-ADMIN']);

        $sisPermisos = [
            'sis.verificar-bd',
            'sis.migrar-contables',
            'sis.migrar-inv',
            'sis.comercial.agencias',
            'sis.comercial.contactos',
        ];

        foreach ($sisPermisos as $permiso) {
            Permission::firstOrCreate(['name' => $permiso]);
        }

        // SIS-ADMIN: ve TODO el proyecto sin excepciones (WMS + RRHH + SIS)
        Role::firstOrCreate(['name' => 'SIS-ADMIN'])
            ->givePermissionTo(array_merge($wmsPermisos, $wmsPermisosVerificacion, $rrhhPermisos, $sisPermisos));
    }
}
