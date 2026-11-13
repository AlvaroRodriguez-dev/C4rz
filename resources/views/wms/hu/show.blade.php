<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">WMS - Detalle de HU</h2>
    </x-slot>

    <div class="py-4 px-3 sm:py-6 sm:px-4">
        <div class="max-w-5xl mx-auto">
            <div class="mb-4">
                <a href="{{ route('wms.pallet.ver.index') }}" class="text-sm text-gray-600 inline-flex items-center gap-1">&larr; Volver a HU</a>
            </div>

            <div class="bg-white shadow rounded-xl p-5 mb-4">
                <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
                    <div>
                        <p class="text-xs text-gray-500 uppercase tracking-wide">Unidad de Manipulación</p>
                        <h1 class="font-mono text-2xl sm:text-3xl font-bold text-gray-800 mt-1">{{ $hu->numero }}</h1>
                    </div>
                    <span class="inline-flex self-start px-3 py-1.5 rounded-full text-sm font-semibold bg-blue-100 text-blue-800">{{ $hu->estado }}</span>
                </div>

                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-5 pt-4 border-t">
                    <div><p class="text-xs text-gray-500">Almacén</p><p class="font-semibold">{{ $hu->almacen?->codigo ?? '-' }}{{ $hu->almacen?->nombre ? ' - '.$hu->almacen->nombre : '' }}</p></div>
                    <div><p class="text-xs text-gray-500">Formato</p><p class="font-semibold">{{ $hu->formato }}</p></div>
                    <div><p class="text-xs text-gray-500">Cantidad</p><p class="font-semibold">{{ number_format((int) $hu->cantidad_total) }} / {{ number_format((int) $hu->capacidad_estandar) }}</p></div>
                    <div><p class="text-xs text-gray-500">Tipo</p><p class="font-semibold">{{ $hu->tipo }}</p></div>
                </div>
            </div>

            @if($hu->entrega)
                <div class="bg-white shadow rounded-xl p-5 mb-4">
                    <h2 class="font-bold text-gray-800 mb-3">Origen de producción</h2>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div><p class="text-xs text-gray-500">Documento</p><p class="font-semibold">{{ $hu->entrega->documento_id }}</p></div>
                        <div><p class="text-xs text-gray-500">Folio físico</p><p class="font-semibold">{{ $hu->entrega->folio_fisico }}</p></div>
                        <div><p class="text-xs text-gray-500">Fecha recepción</p><p class="font-semibold">{{ $hu->entrega->fecha_recepcion?->format('d/m/Y') ?? '-' }}</p></div>
                        <div><p class="text-xs text-gray-500">Estado entrega</p><p class="font-semibold">{{ $hu->entrega->estado }}</p></div>
                    </div>
                </div>
            @endif

            <div class="bg-white shadow rounded-xl overflow-hidden">
                <div class="px-5 py-4 border-b">
                    <h2 class="font-bold text-gray-800">Contenido del HU</h2>
                    <p class="text-sm text-gray-500">Detalle real registrado durante la paletización.</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50 border-b">
                            <tr class="text-left text-gray-600">
                                <th class="px-4 py-3">Código</th>
                                <th class="px-4 py-3">Descripción</th>
                                <th class="px-4 py-3">Lote</th>
                                <th class="px-4 py-3">Formato</th>
                                <th class="px-4 py-3">Calidad</th>
                                <th class="px-4 py-3 text-right">Cantidad</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($hu->detalles as $detalle)
                                <tr>
                                    <td class="px-4 py-3 font-mono font-semibold">{{ $detalle->codigo }}</td>
                                    <td class="px-4 py-3">{{ $detalle->descripcion }} @if($detalle->descripcion2) {{ $detalle->descripcion2 }} @endif</td>
                                    <td class="px-4 py-3">{{ $detalle->lote ?? 'S/L' }}</td>
                                    <td class="px-4 py-3">{{ $detalle->formato }}</td>
                                    <td class="px-4 py-3">{{ $detalle->calidad ?? '-' }}</td>
                                    <td class="px-4 py-3 text-right font-bold">{{ number_format((int) $detalle->cantidad) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="px-4 py-8 text-center text-gray-500">Este HU no tiene detalle registrado.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="px-5 py-4 border-t bg-gray-50 flex justify-between font-semibold">
                    <span>Total</span>
                    <span>{{ number_format((int) $hu->detalles->sum('cantidad')) }} cajas</span>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
