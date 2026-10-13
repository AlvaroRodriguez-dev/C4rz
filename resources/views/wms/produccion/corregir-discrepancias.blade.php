<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">WMS - Corrección de liberación observada</h2>
    </x-slot>
    <div class="py-4 px-3 sm:py-6 sm:px-4"><div class="max-w-6xl mx-auto">
        <div class="flex items-center justify-between gap-3 mb-4">
            <a href="{{ route('wms.produccion.liberacion.create') }}" class="text-sm text-gray-600">&larr; Volver a liberaciones</a>
            <span class="text-xs text-orange-700 font-semibold">Pendiente de corrección</span>
        </div>

        @if (session('error'))
            <div class="mb-4 rounded-lg bg-red-100 border border-red-300 text-red-800 p-3">{{ session('error') }}</div>
        @endif
        @if ($errors->any())
            <div class="mb-4 rounded-lg bg-red-100 border border-red-300 text-red-800 p-3">
                <div class="font-semibold mb-1">Revise los datos ingresados:</div>
                <ul class="list-disc pl-5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        <div class="bg-white shadow rounded-xl p-4 sm:p-5 mb-5">
            <h1 class="text-xl font-bold text-gray-900">Liberación {{ $entrega->documento?->id_documento ?? $entrega->id }}</h1>
            <p class="text-sm text-gray-600 mt-1">Almacén registró las diferencias físicas. Corrija los datos de la liberación original según la revisión realizada. La corrección se guardará con trazabilidad y los pallets se recalcularán antes del ingreso al SAS-ERP.</p>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mt-4 text-sm">
                <div><div class="text-xs text-gray-500">Origen</div><div>{{ $entrega->origen }}</div></div>
                <div><div class="text-xs text-gray-500">Fecha</div><div>{{ optional($entrega->fecha_entrega)->format('d/m/Y') }}</div></div>
                <div><div class="text-xs text-gray-500">Formato</div><div>{{ $entrega->formato }}</div></div>
                <div><div class="text-xs text-gray-500">Estado</div><div class="font-semibold">{{ $entrega->estado }}</div></div>
            </div>
        </div>

        <div class="bg-white shadow rounded-xl p-4 sm:p-5 mb-5">
            <h2 class="font-bold text-gray-900 mb-3">Discrepancias reportadas por Almacén</h2>
            @if ($discrepancias->isEmpty())
                <p class="text-sm text-gray-500">No existen discrepancias pendientes de corrección.</p>
            @else
                <div class="overflow-x-auto"><table class="w-full text-sm">
                    <thead class="bg-gray-100 text-gray-600"><tr><th class="p-3 text-left">Pallet</th><th class="p-3 text-left">Tipo</th><th class="p-3 text-left">Detalle</th><th class="p-3 text-left">Esperado</th><th class="p-3 text-left">Observado</th><th class="p-3 text-left">Observación</th><th class="p-3 text-left">Reportado</th></tr></thead>
                    <tbody>
                    @foreach ($discrepancias as $d)
                        <tr class="border-t">
                            <td class="p-3 font-mono font-semibold">{{ $d->hu?->numero ?? '—' }}</td>
                            <td class="p-3">{{ ['CANTIDAD' => 'Cantidad', 'TONO' => 'Tono', 'CALIBRE' => 'Calibre', 'CALIDAD_PRODUCTO' => 'Calidad / producto'][$d->tipo] ?? $d->tipo }}</td>
                            <td class="p-3">{{ $d->detalle?->codigo ?? 'Pallet completo' }}</td>
                            <td class="p-3">{{ $d->valor_esperado ?? '—' }}</td>
                            <td class="p-3 font-semibold">{{ $d->valor_observado }}</td>
                            <td class="p-3">{{ $d->observacion ?? '—' }}</td>
                            <td class="p-3">{{ optional($d->reportado_at)->format('d/m/Y H:i') ?? '—' }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table></div>
            @endif
        </div>

        <form method="POST" action="{{ route('wms.produccion.liberacion.correccion.discrepancias.guardar', $entrega) }}" class="bg-white shadow rounded-xl p-4 sm:p-5">
            @csrf
            <div class="mb-4">
                <h2 class="font-bold text-gray-900">Corregir líneas de la liberación</h2>
                <p class="text-xs text-gray-500 mt-1">Ajuste únicamente los datos que correspondan. Para calidad/producto, el código debe corresponder a la calidad y formato seleccionados. Los campos tono y calibre aplican a calidad EXTRA.</p>
            </div>
            <div class="space-y-4">
                @foreach ($entrega->detalles as $i => $linea)
                    <div class="border rounded-xl p-3 sm:p-4">
                        <input type="hidden" name="lineas[{{ $i }}][id]" value="{{ $linea->id }}">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-sm font-bold text-gray-800">Línea {{ $linea->orden }} · Formato {{ $linea->formato }}</span>
                            <span class="text-xs text-gray-500">Lote actual: {{ $linea->lote }}</span>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
                            <div class="lg:col-span-2">
                                <label class="block text-xs font-semibold text-gray-600 mb-1">Código de producto</label>
                                <input name="lineas[{{ $i }}][codigo]" value="{{ old('lineas.'.$i.'.codigo', $linea->codigo) }}" required maxlength="30" class="w-full border-gray-300 rounded-lg font-mono">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1">Calidad</label>
                                <select name="lineas[{{ $i }}][calidad]" class="w-full border-gray-300 rounded-lg" required>
                                    @foreach (['EXTRA', 'COMERCIAL', 'ECONOMICO'] as $calidad)
                                        <option value="{{ $calidad }}" @selected(old('lineas.'.$i.'.calidad', $linea->calidad) === $calidad)>{{ $calidad }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1">Cantidad declarada</label>
                                <input name="lineas[{{ $i }}][cantidad_declarada]" type="number" min="0" step="1" required value="{{ old('lineas.'.$i.'.cantidad_declarada', $linea->cantidad_declarada) }}" class="w-full border-gray-300 rounded-lg">
                            </div>
                            <div class="grid grid-cols-2 gap-2">
                                <div><label class="block text-xs font-semibold text-gray-600 mb-1">Tono</label><input name="lineas[{{ $i }}][tono]" type="number" min="0" max="999" value="{{ old('lineas.'.$i.'.tono', $linea->tono) }}" class="w-full border-gray-300 rounded-lg"></div>
                                <div><label class="block text-xs font-semibold text-gray-600 mb-1">Calibre</label><input name="lineas[{{ $i }}][calibre]" type="number" min="0" max="99" value="{{ old('lineas.'.$i.'.calibre', $linea->calibre) }}" class="w-full border-gray-300 rounded-lg"></div>
                            </div>
                        </div>
                        <p class="text-xs text-gray-500 mt-2">{{ $linea->descripcion }} {{ $linea->descripcion2 }}</p>
                    </div>
                @endforeach
            </div>
            <div class="flex flex-col sm:flex-row justify-end gap-2 mt-5">
                <a href="{{ route('wms.produccion.liberacion.create') }}" class="px-5 py-2.5 rounded-lg bg-gray-100 text-gray-700 font-semibold text-center">CANCELAR</a>
                <button type="submit" onclick="return confirm('¿Guardar la corrección y reconstruir los pallets? Los pallets con composición distinta serán sustituidos y los nuevos deberán reverificarse.')" class="px-5 py-2.5 rounded-lg bg-orange-600 hover:bg-orange-700 text-white font-semibold">GUARDAR CORRECCIÓN Y RECALCULAR PALLETS</button>
            </div>
        </form>
    </div></div>
</x-app-layout>
