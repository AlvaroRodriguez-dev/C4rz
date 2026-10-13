<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">WMS - Corrección por pallet observado</h2>
    </x-slot>

    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-rc.0/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-rc.0/js/select2.min.js"></script>
    <style>
        .select2-container { width: 100% !important; }
        .select2-container .select2-selection--single { height: 42px !important; display:flex; align-items:center; border-radius:.5rem !important; border-color:#d1d5db !important; }
        .select2-container--default .select2-selection--single .select2-selection__rendered { line-height:42px !important; padding-left:12px !important; font-size:.875rem; }
        .select2-container--default .select2-selection--single .select2-selection__arrow { height:40px !important; }
    </style>

    <div class="py-4 px-3 sm:py-6 sm:px-4">
        <div class="max-w-7xl mx-auto space-y-5">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <a href="{{ route('wms.produccion.liberacion.create') }}" class="text-sm text-gray-600">&larr; Volver a liberaciones</a>
                    <h1 class="text-2xl font-bold text-gray-900 mt-2">Corrección de discrepancias por pallet</h1>
                    <p class="text-sm text-gray-600 mt-1">
                        Documento: <span class="font-mono font-semibold">{{ $entrega->documento?->id_documento ?? '—' }}</span>
                        · Las correcciones se guardan individualmente. No se modifican las líneas generales de la liberación.
                    </p>
                </div>
            </div>

            @if (session('success'))
                <div class="rounded-lg bg-green-50 border border-green-200 text-green-800 p-3 text-sm">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="rounded-lg bg-red-50 border border-red-200 text-red-800 p-3 text-sm">{{ session('error') }}</div>
            @endif
            @if ($errors->any())
                <div class="rounded-lg bg-red-50 border border-red-200 text-red-800 p-3 text-sm">
                    <p class="font-semibold">No se pudo guardar la corrección:</p>
                    <ul class="list-disc ml-5 mt-1">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif

            <div class="rounded-xl border border-blue-200 bg-blue-50 p-4 text-sm text-blue-900">
                <strong>Importante:</strong> cada formulario corresponde a un solo pallet. Corrija únicamente los detalles físicos de ese pallet y registre el motivo.
                Si cambia el producto, la calidad o el lote, el sistema sustituirá el identificador del pallet y emitirá uno nuevo para conservar la trazabilidad.
            </div>

            @forelse ($discrepancias as $huId => $incidencias)
                @php
                    $hu = $incidencias->first()?->hu;
                    $detallesHu = $hu?->detalles ?? collect();
                @endphp
                @if ($hu && $hu->estado !== 'SUSTITUIDO')
                    <section class="bg-white shadow rounded-xl overflow-hidden">
                        <div class="p-4 sm:p-5 border-b bg-gray-50 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                            <div>
                                <div class="text-xs text-gray-500">PALLET OBSERVADO</div>
                                <h2 class="font-mono text-xl font-bold text-gray-900">{{ $hu->numero }}</h2>
                                <p class="text-sm text-gray-600 mt-1">Formato {{ $hu->formato }} · {{ $hu->cantidad_total }} cajas · Capacidad {{ $hu->capacidad_estandar }}</p>
                            </div>
                            <span class="inline-flex rounded-full bg-orange-100 text-orange-800 px-3 py-1 text-xs font-semibold">{{ $incidencias->count() }} discrepancia(s) pendiente(s)</span>
                        </div>

                        <div class="p-4 sm:p-5">
                            <h3 class="font-semibold text-gray-800 mb-3">Observaciones registradas por Almacén</h3>
                            <div class="overflow-x-auto mb-5">
                                <table class="w-full text-sm">
                                    <thead class="bg-gray-100 text-gray-600">
                                        <tr><th class="p-2 text-left">Tipo</th><th class="p-2 text-left">Detalle</th><th class="p-2 text-left">Esperado</th><th class="p-2 text-left">Observado</th><th class="p-2 text-left">Observación</th></tr>
                                    </thead>
                                    <tbody>
                                    @foreach ($incidencias as $d)
                                        <tr class="border-t">
                                            <td class="p-2">{{ ['CANTIDAD' => 'Cantidad', 'TONO' => 'Tono', 'CALIBRE' => 'Calibre', 'CALIDAD_PRODUCTO' => 'Calidad / producto'][$d->tipo] ?? $d->tipo }}</td>
                                            <td class="p-2">{{ $d->detalle?->codigo ?? 'Pallet completo' }}</td>
                                            <td class="p-2">{{ $d->valor_esperado ?? '—' }}</td>
                                            <td class="p-2 font-semibold">{{ $d->valor_observado }}</td>
                                            <td class="p-2">{{ $d->observacion ?? '—' }}</td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <form method="POST" action="{{ route('wms.produccion.liberacion.correccion.discrepancias.guardar', $entrega) }}" class="space-y-4">
                                @csrf
                                <input type="hidden" name="hu_id" value="{{ $hu->id }}">
                                <h3 class="font-semibold text-gray-800">Corregir contenido de este pallet</h3>
                                <p class="text-xs text-gray-500">Los campos mostrados pertenecen exclusivamente a {{ $hu->numero }}. Para discrepancias de cantidad, ajuste las cantidades de sus detalles de modo que representen lo encontrado físicamente.</p>

                                <div class="space-y-3">
                                    @foreach ($detallesHu as $detalle)
                                        @php
                                            $partesLoteActual = explode('-', (string) $detalle->lote);
                                            $sufijoLoteActual = (string) end($partesLoteActual);
                                            $tonoActual = strlen($sufijoLoteActual) >= 5 && $sufijoLoteActual !== 'NNNNN' ? (int) substr($sufijoLoteActual, 0, 3) : $detalle->entregaDetalle?->tono;
                                            $calibreActual = strlen($sufijoLoteActual) >= 5 && $sufijoLoteActual !== 'NNNNN' ? (int) substr($sufijoLoteActual, 3, 2) : $detalle->entregaDetalle?->calibre;
                                        @endphp
                                        <div class="border rounded-xl p-3 sm:p-4">
                                            <input type="hidden" name="detalles[{{ $detalle->id }}][id]" value="{{ $detalle->id }}">
                                            <div class="text-xs text-gray-500 mb-3">Detalle #{{ $detalle->id }} · Lote actual: {{ $detalle->lote }}</div>
                                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
                                                <div class="lg:col-span-2">
                                                    <label class="block text-xs font-semibold text-gray-600 mb-1">Código de producto</label>
                                                    <select name="detalles[{{ $detalle->id }}][codigo]" class="producto-correccion w-full border-gray-300 rounded-lg font-mono" data-calidad-select="calidad-{{ $detalle->id }}" data-formato="{{ $hu->formato }}" required>
                                                        <option value="{{ $detalle->codigo }}" selected>{{ $detalle->codigo }} · {{ $detalle->descripcion }}</option>
                                                    </select>
                                                </div>
                                                <div>
                                                    <label class="block text-xs font-semibold text-gray-600 mb-1">Calidad</label>
                                                    <select id="calidad-{{ $detalle->id }}" name="detalles[{{ $detalle->id }}][calidad]" class="calidad-correccion w-full border-gray-300 rounded-lg" required>
                                                        @foreach (['EXTRA', 'COMERCIAL', 'ECONOMICO'] as $calidad)
                                                            <option value="{{ $calidad }}" @selected(old('detalles.'.$detalle->id.'.calidad', $detalle->calidad) === $calidad)>{{ $calidad }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div>
                                                    <label class="block text-xs font-semibold text-gray-600 mb-1">Cantidad física</label>
                                                    <input name="detalles[{{ $detalle->id }}][cantidad]" type="number" min="0" step="1" required value="{{ old('detalles.'.$detalle->id.'.cantidad', $detalle->cantidad) }}" class="w-full border-gray-300 rounded-lg">
                                                </div>
                                                <div class="grid grid-cols-2 gap-2">
                                                    <div>
                                                        <label class="block text-xs font-semibold text-gray-600 mb-1">Tono</label>
                                                        <input name="detalles[{{ $detalle->id }}][tono]" type="number" min="0" max="999" value="{{ old('detalles.'.$detalle->id.'.tono', $tonoActual) }}" class="w-full border-gray-300 rounded-lg">
                                                    </div>
                                                    <div>
                                                        <label class="block text-xs font-semibold text-gray-600 mb-1">Calibre</label>
                                                        <input name="detalles[{{ $detalle->id }}][calibre]" type="number" min="0" max="99" value="{{ old('detalles.'.$detalle->id.'.calibre', $calibreActual) }}" class="w-full border-gray-300 rounded-lg">
                                                    </div>
                                                </div>
                                            </div>
                                            <p class="text-xs text-gray-500 mt-2">{{ $detalle->descripcion }} {{ $detalle->descripcion2 }}</p>
                                        </div>
                                    @endforeach
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-1">Motivo de la corrección</label>
                                    <textarea name="observacion_correccion" rows="2" required minlength="3" maxlength="1000" class="w-full border-gray-300 rounded-lg" placeholder="Explique qué se corrigió y por qué.">{{ old('observacion_correccion') }}</textarea>
                                </div>
                                <div class="flex flex-col sm:flex-row justify-end gap-2">
                                    <button type="submit" onclick="return confirm('¿Guardar únicamente la corrección del pallet {{ $hu->numero }}? Se conservará el historial y el pallet afectado quedará pendiente de reverificación.')" class="px-5 py-2.5 rounded-lg bg-orange-600 hover:bg-orange-700 text-white font-semibold">GUARDAR CORRECCIÓN DE ESTE PALLET</button>
                                </div>
                            </form>
                        </div>
                    </section>
                @endif
            @empty
                <div class="bg-white rounded-xl shadow p-6 text-center text-gray-600">No existen discrepancias pendientes de corrección.</div>
            @endforelse

            <div class="flex justify-end">
                <a href="{{ route('wms.produccion.liberacion.create') }}" class="px-5 py-2.5 rounded-lg bg-gray-100 text-gray-700 font-semibold">VOLVER A LIBERACIONES</a>
            </div>
        </div>
    </div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (!window.jQuery || !jQuery.fn.select2) return;

    jQuery('.producto-correccion').each(function () {
        const select = jQuery(this);
        const calidad = document.getElementById(select.data('calidad-select'));
        const formato = select.data('formato');

        select.select2({
            placeholder: 'Buscar producto por código o descripción...',
            minimumInputLength: 1,
            width: '100%',
            ajax: {
                url: @json(route('wms.produccion.liberacion.productos.buscar')),
                dataType: 'json',
                delay: 300,
                data: params => ({ q: params.term || '', formato: formato, calidad: calidad.value }),
                processResults: data => ({ results: data.results || [] }),
                cache: true
            }
        });

        select.on('select2:opening', function (e) {
            if (!calidad.value) {
                e.preventDefault();
                alert('Seleccione primero la calidad del producto.');
            }
        });

        calidad.addEventListener('change', function () {
            select.val(null).trigger('change');
        });
    });
});
</script>
</x-app-layout>
