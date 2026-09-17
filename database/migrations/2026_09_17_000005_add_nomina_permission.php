<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permission = Permission::firstOrCreate([
            'name' => 'rrhh.nomina',
            'guard_name' => 'web',
        ]);

        foreach (['RRHH-ADMIN', 'SIS-ADMIN'] as $roleName) {
            $role = Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'web',
            ]);

            $role->givePermissionTo($permission);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permission = Permission::where('name', 'rrhh.nomina')
            ->where('guard_name', 'web')
            ->first();

        if ($permission) {
            foreach (['RRHH-ADMIN', 'SIS-ADMIN'] as $roleName) {
                $role = Role::where('name', $roleName)
                    ->where('guard_name', 'web')
                    ->first();

                $role?->revokePermissionTo($permission);
            }

            $permission->delete();
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
