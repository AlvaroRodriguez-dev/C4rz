<x-app-layout>
    <x-slot name="header">
        <h2 class="text-2xl font-bold text-gray-800">Editar roles y permisos: {{ $user->name }}</h2>
    </x-slot>

    <div class="py-6 max-w-3xl mx-auto px-4">
        <form method="POST" action="{{ route('admin.usuarios.update', $user) }}" class="bg-white rounded-xl shadow p-6">
            @csrf
            @method('PUT')

            <p class="text-sm text-gray-500 mb-6">{{ $user->email }}</p>

            <!-- ROLES -->
            <h3 class="font-bold text-gray-700 mb-3 uppercase text-sm tracking-wide">Roles</h3>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-2 mb-4">
                @foreach ($roles as $role)
                    <label class="flex items-center gap-2 bg-gray-50 rounded-md px-3 py-2">
                        <input
                            type="checkbox"
                            name="roles[]"
                            value="{{ $role->name }}"
                            data-permisos='@json($role->permissions->pluck("name"))'
                            {{ in_array($role->name, $userRoles) ? 'checked' : '' }}
                            class="rounded border-gray-300 text-indigo-600 role-checkbox">
                        <span class="text-sm text-gray-700">{{ $role->name }}</span>
                    </label>
                @endforeach
            </div>

            <p class="text-xs text-gray-500 mb-8 flex items-center gap-1">
                <span class="inline-block w-3 h-3 rounded bg-indigo-50 ring-1 ring-indigo-200"></span>
                Los permisos resaltados y deshabilitados ya quedan incluidos por el rol seleccionado (no se guardan como permiso directo, para no duplicar el dato).
            </p>

            <!-- PERMISOS DIRECTOS -->
            <h3 class="font-bold text-gray-700 mb-3 uppercase text-sm tracking-wide">
                Permisos
                <span class="normal-case font-normal text-gray-400">
                    (solo marca aquí permisos sueltos que ningún rol seleccionado ya otorgue)
                </span>
            </h3>

            @foreach ($permisos as $modulo => $items)
                <div class="mb-4">
                    <p class="text-xs font-semibold text-gray-500 uppercase mb-2">{{ $modulo }}</p>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                        @foreach ($items as $permiso)
                            <label class="flex items-center gap-2 rounded-md px-2 py-1 transition-colors permiso-label">
                                <input
                                    type="checkbox"
                                    name="permisos[]"
                                    value="{{ $permiso->name }}"
                                    {{ in_array($permiso->name, $userPermisos) ? 'checked' : '' }}
                                    class="rounded border-gray-300 text-indigo-600 permiso-checkbox">
                                <span class="text-sm text-gray-700">{{ $permiso->name }}</span>
                                <span class="text-[10px] text-indigo-500 permiso-via-rol hidden">(por rol)</span>
                            </label>
                        @endforeach
                    </div>
                </div>
            @endforeach

            <div class="mt-6 flex gap-3">
                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md text-sm font-medium">
                    Guardar
                </button>
                <a href="{{ route('admin.usuarios.index') }}" class="text-gray-500 text-sm px-4 py-2">Cancelar</a>
            </div>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const roleCheckboxes = document.querySelectorAll('.role-checkbox');
            const permisoCheckboxes = document.querySelectorAll('.permiso-checkbox');

            // Permisos que el usuario ya tenía asignados DIRECTAMENTE (tabla model_has_permissions).
            const permisosManuales = new Set(@json($userPermisos));

            function resaltar(checkbox, activo) {
                const label = checkbox.closest('label');
                if (!label) return;
                label.classList.toggle('bg-indigo-50', activo);
                label.classList.toggle('ring-1', activo);
                label.classList.toggle('ring-indigo-200', activo);

                const badge = label.querySelector('.permiso-via-rol');
                if (badge) badge.classList.toggle('hidden', !activo);
            }

            // Unión de permisos otorgados por todos los roles actualmente marcados
            function permisosOtorgadosPorRolesActivos() {
                const set = new Set();
                roleCheckboxes.forEach(function (roleCheckbox) {
                    if (roleCheckbox.checked) {
                        JSON.parse(roleCheckbox.dataset.permisos || '[]').forEach(p => set.add(p));
                    }
                });
                return set;
            }

            // Si un permiso ya lo otorga algún rol activo: NO se marca el checkbox (evita duplicar
            // el registro en model_has_permissions), solo se pinta y se deshabilita.
            // Si no lo otorga ningún rol activo: el checkbox queda libre, reflejando si fue marcado a mano.
            function recalcularPermisos() {
                const otorgadosPorRol = permisosOtorgadosPorRolesActivos();

                permisoCheckboxes.forEach(function (permisoCheckbox) {
                    const nombre = permisoCheckbox.value;
                    const porRol = otorgadosPorRol.has(nombre);

                    if (porRol) {
                        permisoCheckbox.checked = false;
                        permisoCheckbox.disabled = true;
                    } else {
                        permisoCheckbox.disabled = false;
                        permisoCheckbox.checked = permisosManuales.has(nombre);
                    }

                    resaltar(permisoCheckbox, porRol);
                });
            }

            roleCheckboxes.forEach(function (roleCheckbox) {
                roleCheckbox.addEventListener('change', recalcularPermisos);
            });

            // Clic manual del administrador sobre un permiso suelto (solo posible si no está deshabilitado)
            permisoCheckboxes.forEach(function (permisoCheckbox) {
                permisoCheckbox.addEventListener('change', function () {
                    if (this.checked) {
                        permisosManuales.add(this.value);
                    } else {
                        permisosManuales.delete(this.value);
                    }
                });
            });

            // Estado inicial al cargar la página
            recalcularPermisos();
        });
    </script>
</x-app-layout>