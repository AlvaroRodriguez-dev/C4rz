<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">WMS - Paletización de Producción</h2>
    </x-slot>

    <div class="py-4 px-3 sm:py-6 sm:px-4">
        <div class="max-w-6xl mx-auto">
            <div class="flex items-center justify-between gap-3 mb-4">
                <a href="{{ route('wms.paletizacion.index') }}" class="text-sm text-gray-600">&larr; Volver a entregas</a>
                <span class="px-3 py-1.5 rounded-full text-xs font-semibold {{ $entrega->estado === 'CON_DIFERENCIA' ? 'bg-amber-100 text-amber-800' : 'bg-green-100 text-green-800' }}">
                    {{ $entrega->estado }}
                </span>
            </div>

            @if (session('success'))
                <div class="mb-4 p-3 rounded-lg text-sm bg-green-100 text-green-800">{{ session('success') }}</div>
            @endif

            @if (session('error'))
                <div class="mb-4 p-3 rounded-lg text-sm bg-red-100 text-red-800">{{ session('error') }}</div>
            @endif

            <div class="bg-white shadow rounded-xl p-4 sm:p-5 mb-4">
                <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                    <div>
                        <div class="text-xs text-gray-500">Documento WMS</div>
                        <div class="font-mono font-semibold">{{ $entrega->documento?->id_documento ?? '—' }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-gray-500">Folio RG-CB-36</div>
                        <div class="font-semibold">{{ $entrega->folio_fisico ?? '—' }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-gray-500">Almacén operativo</div>
                        <div class="font-semibold">{{ $entrega->almacen?->codigo }} · {{ $entrega->almacen?->nombre }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-gray-500">Fecha</div>
                        <div>{{ optional($entrega->fecha_entrega)->format('d/m/Y') }}</div>
                    </div>
                </div>
            </div>

            <div class="bg-white shadow rounded-xl p-4 sm:p-5 mb-4">
                <div class="mb-4">
                    <h3 class="font-semibold text-lg">Detalle conciliado</h3>
                    <p class="text-sm text-gray-500">Estas cantidades son la base para la generación automática de pallets.</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-100 text-gray-600">
                            <tr>
                                <th class="text-left p-3">Producto</th>
                                <th class="text-left p-3">Lote</th>
                                <th class="text-left p-3">Tono</th>
                                <th class="text-left p-3">Calibre</th>
                                <th class="text-left p-3">Formato</th>
                                <th class="text-right p-3">Físico</th>
                                <th class="text-right p-3">Paletizado</th>
                                <th class="text-right p-3">Pendiente</th>
                                <th class="text-right p-3">Capacidad</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($detalles as $detalle)
                                <tr class="border-t">
                                    <td class="p-3">
                                        <div class="font-mono font-semibold">{{ $detalle['codigo'] }}</div>
                                        <div class="text-gray-500">{{ $detalle['descripcion'] }}</div>
                                    </td>
                                    <td class="p-3 font-mono">{{ $detalle['lote'] ?: '—' }}</td>
                                    <td class="p-3">{{ $detalle['tono'] ?: '—' }}</td>
                                    <td class="p-3">{{ $detalle['calibre'] ?: '—' }}</td>
                                    <td class="p-3 font-semibold">{{ $detalle['formato'] }}</td>
                                    <td class="p-3 text-right">{{ number_format($detalle['cantidad_fisica']) }}</td>
                                    <td class="p-3 text-right">{{ number_format($detalle['cantidad_paletizada']) }}</td>
                                    <td class="p-3 text-right font-semibold">{{ number_format($detalle['cantidad_pendiente']) }}</td>
                                    <td class="p-3 text-right">{{ $detalle['capacidad'] ? number_format($detalle['capacidad']) : 'SIN CONFIG.' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 sm:p-5 mb-4">
                <h3 class="font-semibold text-blue-900 mb-2">Generación automática de pallets</h3>
                <p class="text-sm text-blue-800 mb-3">
                    El sistema creará automáticamente los pallets necesarios según la cantidad pendiente y la capacidad configurada para cada formato.
                </p>
                <ul class="text-sm text-blue-800 space-y-1 list-disc pl-5">
                    <li>No se mezclan formatos.</li>
                    <li>No se mezclan productos, lotes, tonos ni calibres.</li>
                    <li>Los pallets completos se registran como <strong>COMPLETO</strong>.</li>
                    <li>El remanente se registra como <strong>SALDO</strong>.</li>
                    <li>Los números de pallet se generan automáticamente según el almacén.</li>
                </ul>
            </div>

            <form method="POST" action="{{ route('wms.paletizacion.store', $entrega) }}" id="formPaletizacion">
                @csrf
                <div class="flex flex-col sm:flex-row sm:justify-end gap-3">
                    <a href="{{ route('wms.paletizacion.index') }}" class="px-5 py-3 border rounded-lg text-center font-semibold text-gray-700">CANCELAR</a>
                    <button type="submit" id="btnGenerar" class="px-5 py-3 bg-green-600 hover:bg-green-700 text-white rounded-lg font-semibold">
                        GENERAR PALETS AUTOMÁTICAMENTE
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.getElementById('formPaletizacion').addEventListener('submit', function () {
            const boton = document.getElementById('btnGenerar');
            boton.disabled = true;
            boton.textContent = 'GENERANDO PALETS...';
        });
    </script>
</x-app-layout>
