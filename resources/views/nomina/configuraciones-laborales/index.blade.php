<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Configuración laboral</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 rounded-md bg-green-50 p-4 text-sm text-green-700">
                    {{ session('success') }}
                </div>
            @endif

            @if($errors->any())
                <div class="mb-4 rounded-md bg-red-50 p-4 text-sm text-red-700">
                    <ul class="mb-0 list-disc pl-5">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="mb-6">
                        <p class="text-sm text-gray-600">Consulta y sincroniza la información laboral desde RRHH.</p>
                    </div>

                    <form method="GET" class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-4 md:items-end">
                        <div>
                            <label for="license" class="block text-sm font-medium text-gray-700">LICENSE</label>
                            <input id="license" type="text" name="license" value="{{ request('license') }}"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                placeholder="Ej. 5287807">
                        </div>
                        <div>
                            <label for="estado" class="block text-sm font-medium text-gray-700">Estado</label>
                            <select id="estado" name="estado"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Todos</option>
                                <option value="ACTIVO" @selected(request('estado') === 'ACTIVO')>ACTIVO</option>
                                <option value="INACTIVO" @selected(request('estado') === 'INACTIVO')>INACTIVO</option>
                            </select>
                        </div>
                        <div class="flex gap-2 md:col-span-2">
                            <button class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500" type="submit">
                                Buscar
                            </button>
                            <a href="{{ route('nomina.configuraciones-laborales.index') }}"
                                class="rounded-md bg-white px-4 py-2 text-sm font-semibold text-gray-700 ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                                Limpiar
                            </a>
                        </div>
                    </form>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">LICENSE</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Trabajador</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Área</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Cargo</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Agencia</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Configuración</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                                @forelse($personas as $persona)
                                    @php($config = $persona->configuracionesLaborales->first())
                                    <tr>
                                        <td class="whitespace-nowrap px-4 py-3 text-sm font-semibold text-gray-900">{{ $persona->license }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-700">{{ $persona->nombre_completo }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-700">{{ $config?->area_nombre ?? '—' }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-700">{{ $config?->cargo_nombre ?? '—' }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-700">{{ $config?->agencia_nombre ?? '—' }}</td>
                                        <td class="px-4 py-3 text-sm">
                                            @if($config)
                                                <span class="rounded-full bg-green-100 px-2.5 py-1 text-xs font-medium text-green-800">Registrada</span>
                                            @else
                                                <span class="rounded-full bg-yellow-100 px-2.5 py-1 text-xs font-medium text-yellow-800">Pendiente</span>
                                            @endif
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3 text-right text-sm">
                                            <a href="{{ route('nomina.configuraciones-laborales.show', $persona) }}"
                                                class="mr-2 inline-flex items-center rounded-md border border-gray-300 bg-white px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50">
                                                Ver
                                            </a>
                                            <form action="{{ route('nomina.configuraciones-laborales.sincronizar', $persona->license) }}" method="POST" class="inline">
                                                @csrf
                                                <button type="submit"
                                                    class="inline-flex items-center rounded-md bg-green-600 px-3 py-2 text-xs font-semibold text-white hover:bg-green-500">
                                                    Sincronizar
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="px-4 py-8 text-center text-sm text-gray-500">No se encontraron trabajadores.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($personas->hasPages())
                        <div class="mt-4">{{ $personas->links() }}</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
