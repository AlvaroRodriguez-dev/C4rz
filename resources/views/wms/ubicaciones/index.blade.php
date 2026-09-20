<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">WMS - Posiciones</h2>
            <p class="text-sm text-gray-500">
                Visualización y control del maestro de ubicaciones generadas
            </p>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-3">

            <div class="flex flex-wrap gap-2 justify-between items-center mb-4">
                <a href="{{ route('wms.index') }}" class="text-sm text-gray-600 hover:text-gray-900">
                    &larr; Volver al WMS
                </a>
                @if ($galpon)
                    <a href="{{ route('wms.galpones.rangos.index', $galpon) }}"
                       class="text-sm text-indigo-600 hover:text-indigo-800 font-semibold">
                        Ver tramos de {{ $galpon->codigo }}
                    </a>
                @endif
            </div>

            @if ($almacenes->isEmpty())
                <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 rounded-xl p-4">
                    No existen almacenes principales registrados.
                </div>
            @else

                <div class="bg-white shadow rounded-xl p-4 mb-4">
                    <form method="GET" action="{{ route('wms.ubicaciones.index') }}">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-3">

                            <div>
                                <label for="almacen" class="block text-sm font-semibold text-gray-700 mb-1">
                                    Almacén
                                </label>
                                <select id="almacen" name="almacen"
                                        class="w-full rounded-lg border-gray-300"
                                        onchange="this.form.submit()">
                                    @foreach ($almacenes as $item)
                                        <option value="{{ $item->id }}" @selected($almacen?->id === $item->id)>
                                            {{ $item->codigo }} - {{ $item->nombre }}
                                            @if (!$item->activo) (INACTIVO) @endif
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label for="galpon" class="block text-sm font-semibold text-gray-700 mb-1">
                                    Galpón
                                </label>
                                <select id="galpon" name="galpon" class="w-full rounded-lg border-gray-300">
                                    <option value="">Todos los galpones</option>
                                    @foreach ($galpones as $item)
                                        <option value="{{ $item->id }}" @selected($galpon?->id === $item->id)>
                                            {{ $item->codigo }} - {{ $item->nombre }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label for="tipo" class="block text-sm font-semibold text-gray-700 mb-1">
                                    Tipo
                                </label>
                                <select id="tipo" name="tipo" class="w-full rounded-lg border-gray-300">
                                    <option value="">Todos</option>
                                    <option value="NORMAL" @selected($tipo === 'NORMAL')>NORMAL</option>
                                    <option value="PREPARACION" @selected($tipo === 'PREPARACION')>PREPARACION</option>
                                    <option value="DESPACHO" @selected($tipo === 'DESPACHO')>DESPACHO</option>
                                </select>
                            </div>

                            <div>
                                <label for="estado" class="block text-sm font-semibold text-gray-700 mb-1">
                                    Estado
                                </label>
                                <select id="estado" name="estado" class="w-full rounded-lg border-gray-300">
                                    <option value="ACTIVO" @selected($estado === 'ACTIVO')>ACTIVO</option>
                                    <option value="INACTIVO" @selected($estado === 'INACTIVO')>INACTIVO</option>
                                    <option value="TODOS" @selected($estado === 'TODOS')>TODOS</option>
                                </select>
                            </div>

                            <div>
                                <label for="q" class="block text-sm font-semibold text-gray-700 mb-1">
                                    Buscar
                                </label>
                                <div class="flex gap-2">
                                    <input id="q" name="q" value="{{ $busqueda }}"
                                           class="w-full rounded-lg border-gray-300"
                                           placeholder="Ej. 173">
                                    <button type="submit"
                                            class="shrink-0 bg-blue-600 hover:bg-blue-700 text-white px-4 rounded-lg font-semibold">
                                        Buscar
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="mt-3 flex flex-wrap gap-2">
                            <a href="{{ route('wms.ubicaciones.index', ['almacen' => $almacen?->id]) }}"
                               class="text-sm text-gray-600 hover:text-gray-900">
                                Limpiar filtros
                            </a>
                        </div>
                    </form>
                </div>

                <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-5">
                    <div class="bg-white shadow rounded-xl p-4">
                        <div class="text-xs uppercase tracking-wide text-gray-500">Total</div>
                        <div class="text-2xl font-bold text-gray-800 mt-1">{{ number_format($resumen['total'], 0, ',', '.') }}</div>
                    </div>
                    <div class="bg-white shadow rounded-xl p-4">
                        <div class="text-xs uppercase tracking-wide text-gray-500">Normales</div>
                        <div class="text-2xl font-bold text-blue-700 mt-1">{{ number_format($resumen['normales'], 0, ',', '.') }}</div>
                    </div>
                    <div class="bg-white shadow rounded-xl p-4">
                        <div class="text-xs uppercase tracking-wide text-gray-500">Especiales</div>
                        <div class="text-2xl font-bold text-indigo-700 mt-1">{{ number_format($resumen['especiales'], 0, ',', '.') }}</div>
                    </div>
                    <div class="bg-white shadow rounded-xl p-4">
                        <div class="text-xs uppercase tracking-wide text-gray-500">Activas</div>
                        <div class="text-2xl font-bold text-green-700 mt-1">{{ number_format($resumen['activas'], 0, ',', '.') }}</div>
                    </div>
                </div>

                <div class="bg-white shadow rounded-xl overflow-hidden">
                    <div class="px-4 py-3 border-b border-gray-100 flex flex-wrap justify-between gap-2 items-center">
                        <div>
                            <h3 class="font-bold text-gray-800">Posiciones registradas</h3>
                            <p class="text-xs text-gray-500">
                                Las posiciones son generadas por los tramos. No se permite renumerarlas ni eliminarlas desde esta pantalla.
                            </p>
                        </div>
                        <span class="text-xs text-gray-500">
                            Mostrando {{ $ubicaciones->firstItem() ?? 0 }}-{{ $ubicaciones->lastItem() ?? 0 }}
                            de {{ $ubicaciones->total() }}
                        </span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-100 text-gray-700">
                                <tr>
                                    <th class="p-3 text-left">Código</th>
                                    <th class="p-3 text-left">Tipo</th>
                                    <th class="p-3 text-left">Galpón</th>
                                    <th class="p-3 text-right">Número</th>
                                    <th class="p-3 text-center">Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($ubicaciones as $ubicacion)
                                    <tr class="border-t hover:bg-gray-50">
                                        <td class="p-3 font-mono font-semibold">
                                            {{ $ubicacion->codigo }}
                                        </td>
                                        <td class="p-3">
                                            @if ($ubicacion->tipo === 'NORMAL')
                                                <span class="inline-flex px-2 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-800">
                                                    NORMAL
                                                </span>
                                            @elseif ($ubicacion->tipo === 'PREPARACION')
                                                <span class="inline-flex px-2 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-800">
                                                    PREPARACION
                                                </span>
                                            @elseif ($ubicacion->tipo === 'DESPACHO')
                                                <span class="inline-flex px-2 py-1 rounded-full text-xs font-semibold bg-purple-100 text-purple-800">
                                                    DESPACHO
                                                </span>
                                            @else
                                                {{ $ubicacion->tipo }}
                                            @endif
                                        </td>
                                        <td class="p-3">
                                            @if ($ubicacion->galpon)
                                                <span class="font-semibold">{{ $ubicacion->galpon->codigo }}</span>
                                                <span class="text-gray-500">· {{ $ubicacion->galpon->nombre }}</span>
                                            @else
                                                <span class="text-gray-400">—</span>
                                            @endif
                                        </td>
                                        <td class="p-3 text-right font-mono">
                                            {{ $ubicacion->numero ?? '—' }}
                                        </td>
                                        <td class="p-3 text-center">
                                            @if ($ubicacion->activo)
                                                <span class="inline-flex px-2 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800">
                                                    ACTIVO
                                                </span>
                                            @else
                                                <span class="inline-flex px-2 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-700">
                                                    INACTIVO
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="p-8 text-center text-gray-500">
                                            No se encontraron posiciones con los filtros seleccionados.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if ($ubicaciones->hasPages())
                        <div class="p-4 border-t border-gray-100">
                            {{ $ubicaciones->links() }}
                        </div>
                    @endif
                </div>

                <div class="mt-4 bg-blue-50 border border-blue-200 text-blue-800 rounded-xl p-4 text-sm">
                    <strong>Regla de integridad:</strong>
                    esta pantalla consulta el maestro de posiciones. La generación, ampliación o corrección de rangos se realiza desde
                    <strong>Galpones → Tramos</strong>; no se cambian códigos ni se eliminan posiciones individualmente.
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
