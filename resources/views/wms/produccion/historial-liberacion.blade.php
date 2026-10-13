<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">WMS - Historial de la liberación</h2>
    </x-slot>

    <div class="py-5 px-3 sm:px-5">
        <div class="max-w-7xl mx-auto space-y-5">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <a href="{{ route('wms.produccion.liberacion.create') }}" class="text-sm text-gray-600 hover:text-gray-900">&larr; Volver a liberaciones</a>
                    <h1 class="text-2xl font-bold text-gray-900 mt-2">Historial de cambios, verificaciones y correcciones</h1>
                    <p class="text-sm text-gray-500 mt-1">Documento: <span class="font-mono font-semibold">{{ $entrega->documento?->id_documento ?? ('Liberación #'.$entrega->id) }}</span></p>
                </div>
                <span class="px-3 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-800">{{ $entrega->estado }}</span>
            </div>

            <section class="bg-white shadow rounded-xl p-4 sm:p-5">
                <h3 class="font-semibold text-gray-900 mb-3">Datos de la liberación</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 text-sm">
                    <div><div class="text-gray-500 text-xs">Folio físico</div><div class="font-medium">{{ $entrega->folio_fisico ?: '—' }}</div></div>
                    <div><div class="text-gray-500 text-xs">Almacén</div><div class="font-medium">{{ $entrega->almacen?->nombre ?? $entrega->almacen?->codigo ?? '—' }}</div></div>
                    <div><div class="text-gray-500 text-xs">Fecha de liberación</div><div class="font-medium">{{ optional($entrega->fecha_entrega)->format('d/m/Y') ?? '—' }}</div></div>
                    <div><div class="text-gray-500 text-xs">Última actualización del registro</div><div class="font-medium">{{ optional($entrega->updated_at)->format('d/m/Y H:i:s') ?? '—' }}</div></div>
                    <div><div class="text-gray-500 text-xs">Registrado por</div><div class="font-medium">{{ $creador?->name ?? 'Usuario no disponible' }}</div></div>
                    <div><div class="text-gray-500 text-xs">Última actualización por</div><div class="font-medium">{{ $ultimoEditor?->name ?? 'Usuario no disponible' }}</div></div>
                    <div><div class="text-gray-500 text-xs">Cajas declaradas</div><div class="font-medium">{{ $entrega->total_declarado ?? 0 }}</div></div>
                    <div><div class="text-gray-500 text-xs">Cajas físicas registradas</div><div class="font-medium">{{ $entrega->total_fisico ?? '—' }}</div></div>
                </div>
                @if($entrega->observaciones)
                    <div class="mt-4 border-t pt-3"><div class="text-xs text-gray-500 mb-1">Observaciones de la liberación</div><p class="text-sm whitespace-pre-line">{{ $entrega->observaciones }}</p></div>
                @endif
                <p class="text-xs text-gray-500 mt-4">La fecha y el usuario de última actualización corresponden al estado actual del registro; el detalle de los eventos se muestra en las secciones siguientes.</p>
            </section>

            <section class="bg-white shadow rounded-xl overflow-hidden">
                <div class="p-4 border-b bg-gray-50">
                    <h3 class="font-semibold text-gray-900">Verificaciones registradas</h3>
                    <p class="text-sm text-gray-500">Se muestra el resultado de las verificaciones que permanecen registradas actualmente.</p>
                </div>
                @if($verificaciones->isEmpty())
                    <div class="p-5 text-sm text-gray-500">No hay verificaciones registradas para esta liberación.</div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-100 text-gray-600"><tr><th class="p-3 text-left">Fecha y hora</th><th class="p-3 text-left">Pallet</th><th class="p-3 text-left">Usuario</th><th class="p-3 text-right">Esperado</th><th class="p-3 text-right">Verificado</th><th class="p-3 text-right">Diferencia</th><th class="p-3 text-left">Resultado</th><th class="p-3 text-left">Observación</th></tr></thead>
                            <tbody>
                                @foreach($verificaciones as $v)
                                    <tr class="border-t align-top">
                                        <td class="p-3 whitespace-nowrap">{{ optional($v->verificado_at)->format('d/m/Y H:i:s') ?? optional($v->created_at)->format('d/m/Y H:i:s') ?? '—' }}</td>
                                        <td class="p-3 font-mono">{{ $v->hu?->numero ?? 'Pallet histórico no disponible' }}</td>
                                        <td class="p-3">{{ $v->verificadoPor?->name ?? 'Usuario no disponible' }}</td>
                                        <td class="p-3 text-right">{{ $v->cantidad_esperada }}</td>
                                        <td class="p-3 text-right">{{ $v->cantidad_verificada }}</td>
                                        <td class="p-3 text-right font-semibold">{{ $v->diferencia > 0 ? '+' : '' }}{{ $v->diferencia }}</td>
                                        <td class="p-3"><span class="px-2 py-1 rounded-full text-xs {{ $v->resultado === 'CONFIRMADO' ? 'bg-green-100 text-green-800' : 'bg-orange-100 text-orange-800' }}">{{ $v->resultado }}</span></td>
                                        <td class="p-3 min-w-48">{{ $v->observacion ?: '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>

            <section class="bg-white shadow rounded-xl overflow-hidden">
                <div class="p-4 border-b bg-gray-50">
                    <h3 class="font-semibold text-gray-900">Discrepancias y correcciones</h3>
                    <p class="text-sm text-gray-500">Incluye lo observado por Almacén, el estado de la incidencia y los datos de corrección que quedaron guardados.</p>
                </div>
                @if($discrepancias->isEmpty())
                    <div class="p-5 text-sm text-gray-500">No se registraron discrepancias ni correcciones.</div>
                @else
                    <div class="divide-y">
                        @foreach($discrepancias as $d)
                            @php
                                $correccion = is_string($d->valor_corregido) ? json_decode($d->valor_corregido, true) : $d->valor_corregido;
                                $anterior = is_string($d->valor_anterior) ? json_decode($d->valor_anterior, true) : $d->valor_anterior;
                                $verificacionAnterior = is_string($d->verificacion_anterior) ? json_decode($d->verificacion_anterior, true) : $d->verificacion_anterior;
                            @endphp
                            <article class="p-4 sm:p-5">
                                <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-2">
                                    <div>
                                        <div class="font-semibold text-gray-900">{{ str_replace('_', ' ', $d->tipo) }} · <span class="font-mono">{{ $d->hu?->numero ?? 'Pallet histórico no disponible' }}</span></div>
                                        <div class="text-xs text-gray-500 mt-1">Reportado: {{ optional($d->reportado_at)->format('d/m/Y H:i:s') ?? '—' }} por {{ $d->reportadoPor?->name ?? 'Usuario no disponible' }}</div>
                                    </div>
                                    <span class="self-start px-2 py-1 rounded-full text-xs font-semibold {{ $d->estado === 'CORREGIDA' ? 'bg-green-100 text-green-800' : 'bg-orange-100 text-orange-800' }}">{{ str_replace('_', ' ', $d->estado) }}</span>
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mt-3 text-sm">
                                    <div><div class="text-xs text-gray-500">Esperado</div><div>{{ $d->valor_esperado ?? '—' }}</div></div>
                                    <div><div class="text-xs text-gray-500">Observado</div><div class="font-semibold">{{ $d->valor_observado }}</div></div>
                                    <div><div class="text-xs text-gray-500">Observación</div><div>{{ $d->observacion ?: '—' }}</div></div>
                                </div>
                                @if($d->corregido_at || $correccion)
                                    <div class="mt-4 rounded-lg border border-green-200 bg-green-50 p-3">
                                        <div class="font-semibold text-green-900 text-sm">Corrección registrada</div>
                                        <div class="text-xs text-green-800 mt-1">{{ optional($d->corregido_at)->format('d/m/Y H:i:s') ?? 'Fecha no disponible' }} · {{ $d->corregidoPor?->name ?? 'Usuario no disponible' }}</div>
                                        @if(is_array($correccion))
                                            @if(!empty($correccion['observacion_correccion']))
                                                <p class="text-sm mt-2"><span class="font-medium">Motivo:</span> {{ $correccion['observacion_correccion'] }}</p>
                                            @endif
                                            @if(!empty($correccion['pallet_anterior']) || !empty($correccion['pallet_actual']))
                                                <p class="text-sm mt-1"><span class="font-medium">Identificador:</span> {{ $correccion['pallet_anterior'] ?? '—' }} &rarr; {{ $correccion['pallet_actual'] ?? '—' }}</p>
                                            @endif
                                            @if(isset($correccion['cantidad_total_observada']))
                                                <p class="text-sm mt-1"><span class="font-medium">Cantidad física total registrada:</span> {{ $correccion['cantidad_total_observada'] }}</p>
                                            @endif
                                            @if(isset($correccion['detalles']))
                                                <details class="mt-2"><summary class="cursor-pointer text-sm font-medium">Ver detalle corregido</summary><pre class="text-xs whitespace-pre-wrap mt-2">{{ json_encode($correccion['detalles'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre></details>
                                            @endif
                                        @else
                                            <p class="text-sm mt-2">{{ $d->valor_corregido }}</p>
                                        @endif
                                    </div>
                                @endif
                                @if(is_array($anterior) || is_array($verificacionAnterior))
                                    <details class="mt-3">
                                        <summary class="cursor-pointer text-sm text-gray-600 font-medium">Ver datos anteriores y verificación previa</summary>
                                        @if(is_array($anterior))
                                            <div class="text-xs text-gray-500 mt-2 mb-1">Contenido anterior</div>
                                            <pre class="text-xs whitespace-pre-wrap rounded bg-gray-50 border p-3">{{ json_encode($anterior, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                        @endif
                                        @if(is_array($verificacionAnterior))
                                            <div class="text-xs text-gray-500 mt-3 mb-1">Verificación anterior</div>
                                            <pre class="text-xs whitespace-pre-wrap rounded bg-gray-50 border p-3">{{ json_encode($verificacionAnterior, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                        @endif
                                    </details>
                                @endif
                            </article>
                        @endforeach
                    </div>
                @endif
            </section>
        </div>
    </div>
</x-app-layout>
