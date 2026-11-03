<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">
            WMS - DISCREPANCIAS DE RECEPCIÓN
        </h2>
    </x-slot>

    <div class="py-6 px-4">
        <div class="max-w-7xl mx-auto">
            <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                <a href="{{ route('wms.recepciones.create') }}"
                   class="text-sm text-gray-600 hover:text-blue-600">
                    &larr; Volver a ingreso por recepción
                </a>
                <span class="text-sm text-gray-500">
                    Almacén: <strong>{{ $codigoAlmacen }}</strong>
                </span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-5">
                <div class="rounded-xl bg-white shadow p-5">
                    <p class="text-sm text-gray-500">Discrepancias pendientes de revisión</p>
                    <p class="mt-1 text-3xl font-semibold text-amber-600">{{ $pendientes }}</p>
                </div>
                <div class="rounded-xl bg-white shadow p-5">
                    <p class="text-sm text-gray-500">Registros mostrados por página</p>
                    <p class="mt-1 text-3xl font-semibold text-gray-800">{{ $discrepancias->count() }}</p>
                </div>
            </div>

            <div class="rounded-xl bg-white shadow overflow-hidden">
                <div class="p-5 border-b">
                    <h3 class="font-semibold text-lg">Historial de discrepancias</h3>
                    <p class="text-sm text-gray-500 mt-1">
                        Consulta de solo lectura. En esta etapa, las discrepancias no bloquean el ingreso y no se aprueban ni modifican desde esta pantalla.
                    </p>
                </div>

                @if ($discrepancias->isEmpty())
                    <div class="p-8 text-center text-gray-500">
                        No existen discrepancias registradas para este almacén.
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-gray-50 text-left text-gray-600">
                                <tr>
                                    <th class="px-4 py-3">Fecha</th>
                                    <th class="px-4 py-3">Recepción SAS</th>
                                    <th class="px-4 py-3">Pallet</th>
                                    <th class="px-4 py-3">Producto</th>
                                    <th class="px-4 py-3">Tipo</th>
                                    <th class="px-4 py-3">Dato SAS</th>
                                    <th class="px-4 py-3">Dato físico</th>
                                    <th class="px-4 py-3">Observación</th>
                                    <th class="px-4 py-3">Estado</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($discrepancias as $discrepancia)
                                    @php
                                        $ingreso = $discrepancia->ingreso;
                                        $esCantidad = $discrepancia->tipo === 'exceso_cantidad';
                                    @endphp
                                    <tr class="align-top">
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            {{ optional($discrepancia->created_at)->format('d/m/Y H:i') }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            {{ $ingreso->doc_origen ?? '—' }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            {{ $ingreso->pallet ?? '—' }}
                                        </td>
                                        <td class="px-4 py-3 min-w-48">
                                            <div class="font-semibold">{{ $ingreso->codigo ?? '—' }}</div>
                                            <div class="text-xs text-gray-500">{{ $ingreso->descrip ?? '' }}</div>
                                            <div class="text-xs text-gray-500">{{ $ingreso->descrip1 ?? '' }}</div>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            {{ $esCantidad ? 'Exceso de cantidad' : 'Discrepancia de lote' }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            @if ($esCantidad)
                                                {{ $discrepancia->cantidad_origen }} cajas
                                            @else
                                                Lote: {{ $discrepancia->lote_origen ?: 'Sin lote' }}<br>
                                                {{ $discrepancia->cantidad_origen }} cajas
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            @if ($esCantidad)
                                                {{ $discrepancia->cantidad_fisica }} cajas
                                            @else
                                                Lote: {{ $discrepancia->lote_fisico ?: 'Sin lote' }}<br>
                                                {{ $discrepancia->cantidad_fisica }} cajas
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 min-w-56 whitespace-normal">
                                            {{ $discrepancia->observacion }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            @if ($discrepancia->estado_revision === 'pendiente')
                                                <span class="inline-flex rounded-full bg-amber-100 px-2 py-1 text-xs font-semibold text-amber-800">
                                                    Pendiente de revisión
                                                </span>
                                            @elseif ($discrepancia->estado_revision === 'aceptada')
                                                <span class="inline-flex rounded-full bg-green-100 px-2 py-1 text-xs font-semibold text-green-800">
                                                    Aceptada
                                                </span>
                                            @elseif ($discrepancia->estado_revision === 'observada')
                                                <span class="inline-flex rounded-full bg-blue-100 px-2 py-1 text-xs font-semibold text-blue-800">
                                                    Observada
                                                </span>
                                            @else
                                                <span class="inline-flex rounded-full bg-gray-100 px-2 py-1 text-xs font-semibold text-gray-700">
                                                    {{ ucfirst($discrepancia->estado_revision ?? 'Sin estado') }}
                                                </span>
                                            @endif
                                            @if ($discrepancia->revisor)
                                                <div class="mt-1 text-xs text-gray-500">
                                                    Revisó: {{ $discrepancia->revisor->name }}
                                                </div>
                                            @endif
                                            @if ($discrepancia->comentario_revision)
                                                <div class="mt-1 text-xs text-gray-500">
                                                    {{ $discrepancia->comentario_revision }}
                                                </div>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="p-4 border-t">
                        {{ $discrepancias->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
