<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">💰 Personal de Nómina</h2>
                <p class="text-sm text-gray-500 mt-1">Identidad sincronizada con RRHH mediante LICENSE.</p>
            </div>
        </div>
    </x-slot>

    <div class="py-8 max-w-6xl mx-auto px-4 space-y-5">
        @if(session('success'))
            <div class="bg-green-50 border border-green-200 text-green-700 text-sm rounded-lg px-4 py-3">
                ✅ {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg px-4 py-3">
                ⚠️ {{ session('error') }}
            </div>
        @endif

        <div class="bg-white rounded-xl shadow p-5">
            <h3 class="font-semibold text-gray-800 mb-3">Buscar trabajador en RRHH</h3>
            <form method="GET" action="{{ route('nomina.personal.index') }}" class="flex flex-col sm:flex-row gap-3">
                <input
                    type="text"
                    name="buscar"
                    value="{{ $buscar }}"
                    maxlength="30"
                    placeholder="Ingrese LICENSE"
                    class="w-full sm:max-w-md rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                >
                <button type="submit" class="rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white font-semibold px-5 py-2">
                    🔎 Buscar
                </button>
                @if($buscar !== '')
                    <a href="{{ route('nomina.personal.index') }}" class="rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold px-5 py-2 text-center">
                        Limpiar
                    </a>
                @endif
            </form>

            @if($buscar !== '')
                <div class="mt-5 border-t pt-4">
                    @forelse($resultadosRrhh as $resultado)
                        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 rounded-lg bg-gray-50 border border-gray-200 p-4">
                            <div>
                                <div class="font-mono font-bold text-indigo-700">{{ $resultado['license'] }}</div>
                                <div class="font-medium text-gray-800">{{ $resultado['nombre_completo'] }}</div>
                                <div class="text-xs text-gray-500 mt-1">Trabajador activo encontrado en RRHH</div>
                            </div>
                            <form method="POST" action="{{ route('nomina.personal.store') }}">
                                @csrf
                                <input type="hidden" name="license" value="{{ $resultado['license'] }}">
                                <button type="submit" class="w-full md:w-auto rounded-lg bg-green-600 hover:bg-green-700 text-white font-semibold px-5 py-2">
                                    + Registrar en Nómina
                                </button>
                            </form>
                        </div>
                    @empty
                        <div class="rounded-lg bg-yellow-50 border border-yellow-200 text-yellow-800 px-4 py-3 text-sm">
                            No se encontró un trabajador activo con ese LICENSE.
                        </div>
                    @endforelse
                </div>
            @endif
        </div>

        <div class="bg-white rounded-xl shadow overflow-hidden">
            <div class="px-5 py-4 border-b">
                <h3 class="font-semibold text-gray-800">Trabajadores registrados en Nómina</h3>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-indigo-600 text-white">
                        <tr>
                            <th class="px-4 py-3 text-left">LICENSE</th>
                            <th class="px-4 py-3 text-left">Nombre completo</th>
                            <th class="px-4 py-3 text-center">Estado</th>
                            <th class="px-4 py-3 text-center">Acción</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($personas as $persona)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 font-mono font-semibold text-indigo-700">{{ $persona->license }}</td>
                                <td class="px-4 py-3 text-gray-800">{{ $persona->nombre_completo ?: '—' }}</td>
                                <td class="px-4 py-3 text-center">
                                    <span class="px-2 py-1 rounded-full text-xs font-semibold {{ $persona->estado === 'ACTIVO' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                                        {{ $persona->estado }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <form method="POST" action="{{ route('nomina.personal.toggle', $persona) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="text-xs font-semibold {{ $persona->estado === 'ACTIVO' ? 'text-red-600 hover:text-red-800' : 'text-green-600 hover:text-green-800' }}">
                                            {{ $persona->estado === 'ACTIVO' ? 'Desactivar' : 'Activar' }}
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-10 text-center text-gray-400">
                                    Todavía no hay trabajadores registrados en Nómina.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($personas->hasPages())
                <div class="px-5 py-4 border-t">{{ $personas->links() }}</div>
            @endif
        </div>
    </div>
</x-app-layout>
