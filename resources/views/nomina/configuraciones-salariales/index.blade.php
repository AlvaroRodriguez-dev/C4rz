<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Configuración salarial
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @if (session('success'))
                <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
                    {{ session('error') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">LICENSE</label>
                        <input type="text" name="license" value="{{ request('license') }}"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Estado</label>
                        <select name="estado" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Todos</option>
                            <option value="ACTIVO" @selected(request('estado') === 'ACTIVO')>ACTIVO</option>
                            <option value="INACTIVO" @selected(request('estado') === 'INACTIVO')>INACTIVO</option>
                        </select>
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" class="px-4 py-2 bg-gray-800 text-white rounded-md hover:bg-gray-700">Buscar</button>
                        <a href="{{ route('nomina.configuraciones-salariales.index') }}" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">Limpiar</a>
                    </div>
                </form>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Trabajador</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Haber básico</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Categoría</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Vigencia</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Acción</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse ($personas as $persona)
                                @php($actual = $persona->configuracionesSalariales->first())
                                <tr>
                                    <td class="px-6 py-4">
                                        <div class="font-medium text-gray-900">{{ $persona->nombre_completo }}</div>
                                        <div class="text-sm text-gray-500">LICENSE {{ $persona->license }}</div>
                                    </td>
                                    <td class="px-6 py-4 text-gray-900">
                                        {{ $actual ? number_format((float) $actual->haber_basico, 2, '.', ',') : '—' }}
                                    </td>
                                    <td class="px-6 py-4 text-gray-700">
                                        {{ $actual?->categoria?->nombre ?? '—' }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-700">
                                        @if ($actual)
                                            {{ $actual->fecha_inicio?->format('d/m/Y') }} — {{ $actual->fecha_fin?->format('d/m/Y') ?? 'Actual' }}
                                        @else
                                            Sin configuración
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <a href="{{ route('nomina.configuraciones-salariales.show', $persona) }}"
                                            class="inline-flex items-center px-3 py-2 bg-gray-800 text-white rounded-md text-sm hover:bg-gray-700">
                                            Ver / configurar
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-8 text-center text-gray-500">No existen trabajadores registrados en Nómina.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($personas->hasPages())
                    <div class="p-4">{{ $personas->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
