<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">WMS - HU Paletizados</h2>
    </x-slot>

    <div class="py-4 px-3 sm:py-6 sm:px-4">
        <div class="max-w-7xl mx-auto">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
                <div>
                    <a href="{{ route('wms.index') }}" class="text-sm text-gray-600 inline-flex items-center gap-1">&larr; Volver al WMS</a>
                    <h1 class="text-2xl font-bold text-gray-800 mt-2">Unidades de Manipulación (HU)</h1>
                    <p class="text-sm text-gray-500 mt-1">HU generados durante la paletización y pendientes de ingreso físico al almacén.</p>
                </div>
                <a href="{{ route('wms.pallet.ver.index', ['modo' => 'historico']) }}" class="px-4 py-2.5 border border-gray-300 rounded-lg text-gray-700 font-semibold text-center bg-white">VER SALDO HISTÓRICO</a>
            </div>

            <form method="GET" class="bg-white shadow rounded-xl p-4 mb-4">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Buscar HU</label>
                        <input type="text" name="buscar" value="{{ $buscar }}" placeholder="HU, documento o folio..." class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
                        <select name="estado" class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                            <option value="">Todos</option>
                            @foreach($estados as $item)
                                <option value="{{ $item }}" @selected($estado === $item)>{{ $item }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex items-end gap-2">
                        <button class="flex-1 px-4 py-2.5 bg-blue-600 text-white rounded-lg font-semibold hover:bg-blue-700">BUSCAR</button>
                        <a href="{{ route('wms.pallet.ver.index') }}" class="px-4 py-2.5 border border-gray-300 rounded-lg text-gray-700">LIMPIAR</a>
                    </div>
                </div>
            </form>

            <div class="bg-white shadow rounded-xl overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50 border-b">
                            <tr class="text-left text-gray-600">
                                <th class="px-4 py-3">HU</th>
                                <th class="px-4 py-3">Almacén</th>
                                <th class="px-4 py-3">Formato</th>
                                <th class="px-4 py-3 text-right">Cantidad</th>
                                <th class="px-4 py-3">Tipo</th>
                                <th class="px-4 py-3">Estado</th>
                                <th class="px-4 py-3">Ubicación</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($hus as $hu)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3">
                                        <a href="{{ url('wms/pallet-ver/hu/'.$hu->id) }}" class="font-mono font-bold text-blue-700 hover:underline">{{ $hu->numero }}</a>
                                        @if($hu->entrega)
                                            <div class="text-xs text-gray-500 mt-1">{{ $hu->entrega->documento_id }} · Folio {{ $hu->entrega->folio_fisico }}</div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">{{ $hu->almacen?->codigo ?? '-' }}</td>
                                    <td class="px-4 py-3 font-semibold">{{ $hu->formato }}</td>
                                    <td class="px-4 py-3 text-right">{{ number_format((int) $hu->cantidad_total) }} / {{ number_format((int) $hu->capacidad_estandar) }}</td>
                                    <td class="px-4 py-3">{{ $hu->tipo }}</td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-800">{{ $hu->estado }}</span>
                                    </td>
                                    <td class="px-4 py-3">{{ $hu->ubicacion_id ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="px-4 py-10 text-center text-gray-500">No existen HU que coincidan con los filtros.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($hus->hasPages())
                    <div class="p-4 border-t">{{ $hus->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
