<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class ItamPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permisos = [
            'it.solicitudes.view',
            'it.solicitudes.create',
            'it.solicitudes.update',
            'it.solicitudes.evaluate',
        ];

        foreach ($permisos as $permiso) {
            Permission::firstOrCreate(['name' => $permiso]);
        }

        Role::firstOrCreate(['name' => 'SIS-ADMIN'])
            ->givePermissionTo($permisos);
    }
}
