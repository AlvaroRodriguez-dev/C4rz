<?php
// app/Http/Controllers/Admin/UserRoleController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class UserRoleController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->get('search');

        $users = User::with('roles')
            ->when($search, fn($q) => $q->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%"))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('admin.usuarios.index', compact('users', 'search'));
    }


    public function edit(User $user)
    {
        $roles = Role::with('permissions')->orderBy('name')->get(); // <-- eager load permissions
        $userRoles = $user->roles->pluck('name')->toArray();

        $permisos = Permission::orderBy('name')->get()
            ->groupBy(fn($p) => explode('.', $p->name)[0]);

        $userPermisos = $user->getDirectPermissions()->pluck('name')->toArray();

        return view('admin.usuarios.edit', compact('user', 'roles', 'userRoles', 'permisos', 'userPermisos'));
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'roles' => ['array'],
            'roles.*' => ['string', 'exists:roles,name'],
            'permisos' => ['array'],
            'permisos.*' => ['string', 'exists:permissions,name'],
        ]);

        $user->syncRoles($request->input('roles', []));
        $user->syncPermissions($request->input('permisos', []));

        return redirect()
            ->route('admin.usuarios.index')
            ->with('success', "Roles y permisos actualizados para {$user->name}.");
    }
}
