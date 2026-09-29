<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">WMS - Paletización de Producción</h2>
    </x-slot>

    <div class="py-4 px-3 sm:py-6 sm:px-4">
        <div class="max-w-7xl mx-auto">
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

            @if ($pendienteTotal > 0)
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
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h3 class="font-semibold text-blue-900 mb-2">Paletización automática</h3>
                            <p class="text-sm text-blue-800">
                                El sistema calcula y crea los pallets automáticamente. El usuario no arma pallets ni define cantidades manualmente.
                            </p>
                        </div>
                        <div class="text-right whitespace-nowrap">
                            <div class="text-xs text-blue-700">Pallets previstos</div>
                            <div class="text-2xl font-bold text-blue-900">{{ count($palletsPrevistos) }}</div>
                        </div>
                    </div>

                    <ul class="text-sm text-blue-800 space-y-1 list-disc pl-5 mt-3">
                        <li>No se mezclan formatos.</li>
                        <li>No se mezclan productos, lotes, tonos ni calibres.</li>
                        <li>Cada pallet respeta la capacidad configurada para su formato.</li>
                        <li>Los pallets completos se registran como <strong>COMPLETO</strong>.</li>
                        <li>El remanente se registra como <strong>SALDO</strong>.</li>
                        <li>Los números de pallet se generan automáticamente según el almacén.</li>
                    </ul>
                </div>

                <div class="bg-white shadow rounded-xl overflow-hidden mb-4">
                    <div class="px-4 py-3 border-b bg-gray-50">
                        <h3 class="font-semibold">Plan de pallets</h3>
                        <p class="text-sm text-gray-500">Vista previa calculada con las cantidades conciliadas. Todavía no consume correlativos.</p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-100 text-gray-600">
                                <tr>
                                    <th class="text-center p-3">#</th>
                                    <th class="text-left p-3">Producto</th>
                                    <th class="text-left p-3">Lote</th>
                                    <th class="text-left p-3">Tono</th>
                                    <th class="text-left p-3">Calibre</th>
                                    <th class="text-left p-3">Formato</th>
                                    <th class="text-right p-3">Capacidad</th>
                                    <th class="text-right p-3">Cajas</th>
                                    <th class="text-left p-3">Tipo</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($palletsPrevistos as $index => $pallet)
                                    @php($ref = $pallet['detalle_referencia'])
                                    <tr class="border-t">
                                        <td class="p-3 text-center font-semibold">{{ $index + 1 }}</td>
                                        <td class="p-3 font-mono">{{ $ref['codigo'] }}</td>
                                        <td class="p-3 font-mono">{{ $ref['lote'] ?: '—' }}</td>
                                        <td class="p-3">{{ $ref['tono'] ?: '—' }}</td>
                                        <td class="p-3">{{ $ref['calibre'] ?: '—' }}</td>
                                        <td class="p-3 font-semibold">{{ $pallet['formato'] }}</td>
                                        <td class="p-3 text-right">{{ number_format($pallet['capacidad']) }}</td>
                                        <td class="p-3 text-right font-semibold">{{ number_format($pallet['cantidad_total']) }}</td>
                                        <td class="p-3">
                                            <span class="px-2 py-1 rounded-full text-xs font-semibold {{ $pallet['tipo'] === 'SALDO' ? 'bg-amber-100 text-amber-800' : 'bg-green-100 text-green-800' }}">
                                                {{ $pallet['tipo'] }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="p-6 text-center text-gray-500">No existen pallets pendientes de generar.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <form method="POST" action="{{ route('wms.paletizacion.store', $entrega) }}" id="formPaletizacion">
                    @csrf
                    <div class="flex flex-col sm:flex-row sm:justify-end gap-3">
                        <a href="{{ route('wms.paletizacion.index') }}" class="px-5 py-3 border rounded-lg text-center font-semibold text-gray-700">CANCELAR</a>
                        <button type="submit" id="btnGenerar" class="px-5 py-3 bg-green-600 hover:bg-green-700 text-white rounded-lg font-semibold">
                            CONFIRMAR Y GENERAR {{ count($palletsPrevistos) }} PALLETS
                        </button>
                    </div>
                </form>
            @else
                <div class="bg-green-50 border border-green-200 rounded-xl p-5 mb-4">
                    <h3 class="font-semibold text-green-900 text-lg">Paletización completada</h3>
                    <p class="text-sm text-green-800 mt-1">No existen cantidades pendientes de paletizar para esta entrega.</p>
                </div>
            @endif

            <div class="bg-white shadow rounded-xl overflow-hidden">
                <div class="px-4 py-3 border-b bg-gray-50">
                    <h3 class="font-semibold">HU generados para esta entrega</h3>
                    <p class="text-sm text-gray-500">Estos registros serán utilizados posteriormente para recuperar el ingreso WMS.</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-100 text-gray-600">
                            <tr>
                                <th class="text-left p-3">HU / Pallet</th>
                                <th class="text-left p-3">Formato</th>
                                <th class="text-left p-3">Tipo</th>
                                <th class="text-right p-3">Capacidad</th>
                                <th class="text-right p-3">Cantidad</th>
                                <th class="text-left p-3">Ubicación</th>
                                <th class="text-left p-3">Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($hus as $hu)
                                <tr class="border-t">
                                    <td class="p-3 font-mono font-semibold whitespace-nowrap">{{ $hu->numero }}</td>
                                    <td class="p-3">{{ $hu->formato ?: '—' }}</td>
                                    <td class="p-3">{{ $hu->tipo ?: '—' }}</td>
                                    <td class="p-3 text-right">{{ $hu->capacidad_estandar ? number_format($hu->capacidad_estandar) : '—' }}</td>
                                    <td class="p-3 text-right font-semibold">{{ number_format($hu->cantidad_total) }}</td>
                                    <td class="p-3">{{ $hu->ubicacion?->codigo ?? 'PENDIENTE' }}</td>
                                    <td class="p-3">{{ $hu->estado ?: '—' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="p-6 text-center text-gray-500">Todavía no existen HU generados para esta entrega.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        const formulario = document.getElementById('formPaletizacion');
        if (formulario) {
            formulario.addEventListener('submit', function () {
                const boton = document.getElementById('btnGenerar');
                boton.disabled = true;
                boton.textContent = 'GENERANDO PALLETS...';
            });
        }
    </script>
</x-app-layout>
