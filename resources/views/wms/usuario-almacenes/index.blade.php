<x-app-layout>
    <x-slot name="header">
        <h2 class="text-2xl font-bold text-gray-800">Asignación de almacenes WMS</h2>
    </x-slot>

    <div class="py-6 max-w-5xl mx-auto px-4">
        @if (session('success'))
            <div class="mb-4 rounded-xl bg-green-100 border border-green-300 text-green-800 p-3">
                {{ session('success') }}
            </div>
        @endif

        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
            <div>
                <a href="{{ route('wms.index') }}" class="text-sm text-gray-600 hover:text-gray-800">&larr; Volver al WMS</a>
                <p class="text-sm text-gray-500 mt-1">Define dónde puede operar cada usuario. Los permisos continúan controlando qué puede hacer.</p>
            </div>
        </div>

        <form method="GET" class="mb-4">
            <input type="text" name="search" value="{{ $search }}"
                placeholder="Buscar por nombre o correo"
                class="border-gray-300 rounded-md shadow-sm w-full md:w-1/3">
        </form>

        <div class="bg-white rounded-xl shadow overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Usuario</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Almacenes WMS</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Principal</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse ($users as $user)
                        @php
                            $activos = $user->wmsAlmacenes->filter(fn($a) => (bool) $a->pivot->activo);
                            $principal = $activos->first(fn($a) => (bool) $a->pivot->es_principal);
                        @endphp
                        <tr>
                            <td class="px-4 py-3">
                                <div class="font-medium text-gray-800">{{ $user->name }}</div>
                                <div class="text-xs text-gray-500">{{ $user->email }}</div>
                            </td>
                            <td class="px-4 py-3">
                                @forelse ($activos as $almacen)
                                    <span class="inline-block bg-gray-100 text-gray-700 text-xs px-2 py-1 rounded-full mr-1 mb-1">
                                        {{ $almacen->codigo }} · {{ $almacen->nombre }}
                                    </span>
                                @empty
                                    <span class="text-xs text-gray-400">Sin almacén asignado</span>
                                @endforelse
                            </td>
                            <td class="px-4 py-3">
                                @if ($principal)
                                    <span class="font-semibold text-blue-700">{{ $principal->codigo }}</span>
                                @else
                                    <span class="text-xs text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if ($activos->isNotEmpty())
                                    <span class="text-xs font-semibold text-green-700 bg-green-100 px-2 py-1 rounded-full">OPERATIVO</span>
                                @else
                                    <span class="text-xs font-semibold text-gray-500 bg-gray-100 px-2 py-1 rounded-full">SIN ASIGNACIÓN</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('wms.usuario-almacenes.edit', $user) }}"
                                   class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">
                                    Asignar / editar
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-6 text-center text-gray-400">No hay usuarios.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $users->links() }}</div>
    </div>
</x-app-layout>
