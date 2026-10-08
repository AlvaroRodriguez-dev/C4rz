<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Flujo de Efectivo</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            @if ($errors->any())
                <div class="bg-red-100 border border-red-300 text-red-700 rounded-lg p-4">
                    <ul class="list-disc pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="bg-white shadow-sm rounded-lg p-6">
                <form method="POST" action="{{ route('flujo-efectivo.generar') }}">
                    @csrf
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Fecha inicial</label>
                            <input type="date" name="fecha_inicial" value="{{ $filtros['fecha_inicial'] }}"
                                class="w-full rounded-md border-gray-300 shadow-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Fecha final</label>
                            <input type="date" name="fecha_final" value="{{ $filtros['fecha_final'] }}"
                                class="w-full rounded-md border-gray-300 shadow-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Periodicidad</label>
                            <select name="periodicidad" class="w-full rounded-md border-gray-300 shadow-sm">
                                <option value="diario" @selected($filtros['periodicidad'] === 'diario')>Diario</option>
                                <option value="semanal" @selected($filtros['periodicidad'] === 'semanal')>Semanal</option>
                                <option value="mensual" @selected($filtros['periodicidad'] === 'mensual')>Mensual</option>
                            </select>
                        </div>
                        <div class="flex items-end">
                            <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                <input type="checkbox" name="incluir_in" value="1" @checked($filtros['incluir_in'])
                                    class="rounded border-gray-300 text-indigo-600">
                                Incluir comprobantes IN
                            </label>
                        </div>
                    </div>
                    <div class="mt-5 flex justify-end">
                        <button class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold px-5 py-2 rounded-md">
                            Generar reporte
                        </button>
                    </div>
                </form>
            </div>

            @if ($reporte)
                <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                    @foreach ([
                        ['label'=>'Saldo inicial','value'=>$reporte['saldo_inicial'],'class'=>'text-gray-900'],
                        ['label'=>'Ingresos','value'=>$reporte['ingresos'],'class'=>'text-green-700'],
                        ['label'=>'Egresos','value'=>$reporte['egresos'],'class'=>'text-red-700'],
                        ['label'=>'Transferencias','value'=>$reporte['transferencias'],'class'=>'text-amber-700'],
                        ['label'=>'Pendiente','value'=>$reporte['sin_clasificar'],'class'=>'text-orange-700'],
                    ] as $card)
                        <div class="bg-white shadow-sm rounded-lg p-5">
                            <div class="text-xs uppercase tracking-wide text-gray-500">{{ $card['label'] }}</div>
                            <div class="mt-2 text-2xl font-bold {{ $card['class'] }}">
                                Bs {{ number_format($card['value'], 2, ',', '.') }}
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="bg-white shadow-sm rounded-lg p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-800">Resumen del flujo</h3>
                            <p class="text-sm text-gray-500">
                                Saldo final calculado: <strong>Bs {{ number_format($reporte['saldo_final'], 2, ',', '.') }}</strong>
                            </p>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="border-b text-left text-gray-500">
                                    <th class="py-3 pr-4">Período</th>
                                    <th class="py-3 pr-4 text-right">Ingresos</th>
                                    <th class="py-3 pr-4 text-right">Egresos</th>
                                    <th class="py-3 text-right">Flujo neto</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($reporte['periodos'] as $periodo)
                                    <tr class="border-b last:border-0">
                                        <td class="py-3 pr-4">{{ $periodo['periodo'] }}</td>
                                        <td class="py-3 pr-4 text-right text-green-700">Bs {{ number_format($periodo['ingresos'], 2, ',', '.') }}</td>
                                        <td class="py-3 pr-4 text-right text-red-700">Bs {{ number_format($periodo['egresos'], 2, ',', '.') }}</td>
                                        <td class="py-3 text-right font-semibold {{ $periodo['neto'] >= 0 ? 'text-green-700' : 'text-red-700' }}">
                                            Bs {{ number_format($periodo['neto'], 2, ',', '.') }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="py-8 text-center text-gray-400">Sin movimientos.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="bg-white shadow-sm rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">Efectivo por grupo de cuenta</h3>
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead class="text-gray-500 border-b">
                                    <tr>
                                        <th class="py-2 text-left">Cuenta</th>
                                        <th class="py-2 text-right">Ingresos</th>
                                        <th class="py-2 text-right">Egresos</th>
                                        <th class="py-2 text-right">Transferencias</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($reporte['cuentas'] as $cuenta)
                                        <tr class="border-b last:border-0">
                                            <td class="py-2">{{ $cuenta['cuenta'] }}</td>
                                            <td class="py-2 text-right">Bs {{ number_format($cuenta['ingresos'], 2, ',', '.') }}</td>
                                            <td class="py-2 text-right">Bs {{ number_format($cuenta['egresos'], 2, ',', '.') }}</td>
                                            <td class="py-2 text-right">Bs {{ number_format($cuenta['transferencias'], 2, ',', '.') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="bg-white shadow-sm rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">Apertura 2604T001</h3>
                        <div class="space-y-3 text-sm">
                            <div class="flex justify-between"><span>Disponible</span><strong>Bs {{ number_format($reporte['apertura_detalle']['disponible'],2,',','.') }}</strong></div>
                            <div class="flex justify-between"><span>Inversiones / plazo</span><strong>Bs {{ number_format($reporte['apertura_detalle']['inversiones'],2,',','.') }}</strong></div>
                            <div class="flex justify-between"><span>Restringido</span><strong>Bs {{ number_format($reporte['apertura_detalle']['restringido'],2,',','.') }}</strong></div>
                        </div>
                        <p class="mt-4 text-xs text-gray-500">
                            En esta V1 el saldo inicial se toma del asiento de apertura informado para la gestión 2026.
                        </p>
                    </div>
                </div>

                <div class="bg-white shadow-sm rounded-lg p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-800">Detalle de movimientos</h3>
                        <span class="text-xs text-gray-500">IN: {{ $filtros['incluir_in'] ? 'incluidos' : 'excluidos' }}</span>
                    </div>
                    <div class="overflow-x-auto max-h-[36rem]">
                        <table class="min-w-full text-xs">
                            <thead class="text-gray-500 border-b sticky top-0 bg-white">
                                <tr>
                                    <th class="py-2 text-left">Fecha</th>
                                    <th class="py-2 text-left">CBTE</th>
                                    <th class="py-2 text-left">Cuenta</th>
                                    <th class="py-2 text-left">Detalle</th>
                                    <th class="py-2 text-left">Tipo</th>
                                    <th class="py-2 text-left">Categoría</th>
                                    <th class="py-2 text-right">Importe</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($reporte['movimientos'] as $mov)
                                    <tr class="border-b">
                                        <td class="py-2 pr-2 whitespace-nowrap">{{ \Carbon\Carbon::parse($mov->FECHA)->format('d/m/Y') }}</td>
                                        <td class="py-2 pr-2 whitespace-nowrap">{{ $mov->CBTE }}</td>
                                        <td class="py-2 pr-2 whitespace-nowrap">{{ $mov->NCTA }}</td>
                                        <td class="py-2 pr-2">{{ $mov->DETALLE }}</td>
                                        <td class="py-2 pr-2">{{ $mov->tipo_flujo }}</td>
                                        <td class="py-2 pr-2">{{ $mov->categoria_flujo }}</td>
                                        <td class="py-2 pr-2 text-right whitespace-nowrap">Bs {{ number_format($mov->importe, 2, ',', '.') }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="7" class="py-8 text-center text-gray-400">Sin movimientos.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
