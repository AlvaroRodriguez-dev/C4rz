<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-2xl font-bold text-gray-800">Inventario de activos</h2>
                <p class="text-sm text-gray-500">Registro individual y consulta de activos tecnológicos</p>
            </div>
            @can('it.activos.create')
                <a href="{{ route('itam.activos.create') }}" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Registrar activo</a>
            @endcan
        </div>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-7xl space-y-4 px-4 sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="rounded-lg bg-green-50 p-3 text-sm text-green-800">{{ session('success') }}</div>
            @endif
            <form method="GET" class="grid grid-cols-1 gap-3 rounded-xl bg-white p-4 shadow sm:grid-cols-2 lg:grid-cols-5">
                <input name="buscar" value="{{ request('buscar') }}" placeholder="Código, serie, marca o modelo" class="rounded-lg border-gray-300 text-sm">
                <select name="tipo_activo_id" class="rounded-lg border-gray-300 text-sm">
                    <option value="">Todos los tipos</option>
                    @foreach ($tiposActivo as $tipo)
                        <option value="{{ $tipo->id }}" @selected(request('tipo_activo_id') == $tipo->id)>{{ $tipo->descripcion }}</option>
                    @endforeach
                </select>
                <select name="estado_id" class="rounded-lg border-gray-300 text-sm">
                    <option value="">Todos los estados</option>
                    @foreach ($estados as $estado)
                        <option value="{{ $estado->id }}" @selected(request('estado_id') == $estado->id)>{{ $estado->descripcion }}</option>
                    @endforeach
                </select>
                <select name="ubicacion_id" class="rounded-lg border-gray-300 text-sm">
                    <option value="">Todas las ubicaciones</option>
                    @foreach ($ubicaciones as $ubicacion)
                        <option value="{{ $ubicacion->id }}" @selected(request('ubicacion_id') == $ubicacion->id)>{{ $ubicacion->codigo }} — {{ $ubicacion->descripcion }}</option>
                    @endforeach
                </select>
                <div class="flex gap-2">
                    <button class="flex-1 rounded-lg bg-slate-800 px-3 py-2 text-sm font-semibold text-white">Filtrar</button>
                    <a href="{{ route('itam.activos.index') }}" class="rounded-lg border px-3 py-2 text-sm">Limpiar</a>
                </div>
            </form>

            <div class="overflow-hidden rounded-xl bg-white shadow">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500">
                            <tr>
                                <th class="px-4 py-3">Código ITAM</th><th class="px-4 py-3">Activo</th><th class="px-4 py-3">Serie</th><th class="px-4 py-3">Ubicación</th><th class="px-4 py-3">Estado</th><th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($activos as $activo)
                                <tr>
                                    <td class="whitespace-nowrap px-4 py-3 font-semibold">{{ $activo->codigo_ti }}</td>
                                    <td class="px-4 py-3">{{ $activo->tipoActivo?->descripcion }}<div class="text-xs text-gray-500">{{ $activo->marca?->nombre }} {{ $activo->modelo?->nombre }}</div></td>
                                    <td class="px-4 py-3">{{ $activo->numero_serie ?: '—' }}</td>
                                    <td class="px-4 py-3">{{ $activo->ubicacion?->descripcion }}</td>
                                    <td class="px-4 py-3">{{ $activo->estado?->descripcion }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-right"><a class="font-semibold text-blue-700 hover:underline" href="{{ route('itam.activos.show', $activo) }}">Ver ficha</a></td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="px-4 py-10 text-center text-gray-500">No se encontraron activos registrados.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="border-t px-4 py-3">{{ $activos->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>