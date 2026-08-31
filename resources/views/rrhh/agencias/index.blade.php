<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">🏢 Agencias</h2>
            <a href="{{ route('rrhh.agencias.create') }}"
               class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm
                      font-semibold py-2 px-4 rounded-lg">
                + Nueva Agencia
            </a>
        </div>
    </x-slot>

    <div class="py-8 max-w-5xl mx-auto px-4">

        @if(session('success'))
            <div class="mb-4 bg-green-50 border border-green-200 text-green-700
                        text-sm rounded-lg px-4 py-3">
                ✅ {{ session('success') }}
            </div>
        @endif

        <div class="bg-white rounded-xl shadow overflow-hidden">
            <table class="min-w-full text-sm">
                <thead class="bg-indigo-600 text-white">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold">Código</th>
                        <th class="px-4 py-3 text-left font-semibold">Nombre</th>
                        <th class="px-4 py-3 text-center font-semibold">Coordenadas</th>
                        <th class="px-4 py-3 text-center font-semibold">Tolerancia</th>
                        <th class="px-4 py-3 text-center font-semibold">Usuarios</th>
                        <th class="px-4 py-3 text-center font-semibold">Estado</th>
                        <th class="px-4 py-3 text-center font-semibold">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($agencias as $a)
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-4 py-2 font-mono font-semibold text-indigo-700">
                                {{ $a->codigo }}
                            </td>
                            <td class="px-4 py-2 font-medium text-gray-800">
                                {{ $a->nombre }}
                            </td>
                            <td class="px-4 py-2 text-center">
                                <a href="https://www.google.com/maps?q={{ $a->latitud }},{{ $a->longitud }}"
                                   target="_blank"
                                   class="text-xs text-indigo-600 hover:underline font-mono">
                                    📍 {{ number_format($a->latitud, 5) }},
                                    {{ number_format($a->longitud, 5) }}
                                </a>
                            </td>
                            <td class="px-4 py-2 text-center">
                                <span class="bg-blue-100 text-blue-700 text-xs
                                             font-semibold px-2 py-0.5 rounded-full">
                                    {{ $a->tolerancia }} m
                                </span>
                            </td>
                            <td class="px-4 py-2 text-center">
                                <span class="inline-flex items-center justify-center w-7 h-7
                                      rounded-full bg-gray-100 text-gray-700 text-xs font-bold">
                                    {{ $a->users_count }}
                                </span>
                            </td>
                            <td class="px-4 py-2 text-center">
                                <span class="px-2 py-0.5 rounded-full text-xs font-semibold
                                    {{ $a->activo
                                       ? 'bg-green-100 text-green-700'
                                       : 'bg-gray-100 text-gray-400' }}">
                                    {{ $a->activo ? 'Activo' : 'Inactivo' }}
                                </span>
                            </td>
                            <td class="px-4 py-2 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <a href="{{ route('rrhh.agencias.edit', $a) }}"
                                       class="text-indigo-600 hover:text-indigo-800
                                              text-xs font-semibold">
                                        ✏️ Editar
                                    </a>
                                    <form method="POST"
                                          action="{{ route('rrhh.agencias.destroy', $a) }}"
                                          onsubmit="return confirm('¿Eliminar esta agencia?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="text-red-500 hover:text-red-700
                                                       text-xs font-semibold">
                                            🗑️ Eliminar
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-10 text-center text-gray-400">
                                No hay agencias registradas.
                                <a href="{{ route('rrhh.agencias.create') }}"
                                   class="text-indigo-600 hover:underline ml-1">
                                    Crear la primera
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>