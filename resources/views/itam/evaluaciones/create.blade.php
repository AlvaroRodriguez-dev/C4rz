<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800">Evaluación técnica</h2>
                <p class="text-sm text-gray-500">{{ $solicitud->numero }}</p>
            </div>
            <a href="{{ route('itam.solicitudes.show', $solicitud) }}" class="text-blue-600">Volver a solicitud</a>
        </div>
    </x-slot>

    <div class="py-6 max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        @if($errors->any())
            <div class="mb-4 p-4 bg-red-100 text-red-700 rounded-lg">
                <ul class="list-disc ml-5">
                    @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
        @endif

        @if($evaluacionActual)
            <div class="mb-5 p-4 bg-blue-50 border border-blue-200 text-blue-800 rounded-lg">
                Esta solicitud ya tiene una evaluación registrada. Al guardar una nueva evaluación se conservará el historial anterior.
                <div class="mt-2 text-sm">
                    Última evaluación: #{{ $evaluacionActual->id }} ·
                    {{ $evaluacionActual->evaluador?->name ?? 'Usuario' }} ·
                    {{ $evaluacionActual->fecha_evaluacion?->format('d/m/Y H:i') }}
                </div>
            </div>
        @endif

        <form method="POST" action="{{ route('itam.solicitudes.evaluaciones.store', $solicitud) }}" class="space-y-5">
            @csrf

            <div class="bg-white shadow rounded-lg p-5">
                <h3 class="font-semibold text-gray-800 mb-4">Datos de la solicitud</h3>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 text-sm">
                    <div><p class="text-xs text-gray-500 uppercase">Solicitante</p><p class="font-semibold">{{ $solicitud->solicitante_id }}</p></div>
                    <div><p class="text-xs text-gray-500 uppercase">Fecha</p><p>{{ $solicitud->fecha_solicitud?->format('d/m/Y') }}</p></div>
                    <div><p class="text-xs text-gray-500 uppercase">Prioridad</p><p>{{ $solicitud->prioridad }}</p></div>
                    <div><p class="text-xs text-gray-500 uppercase">Estado</p><p>{{ str_replace('_', ' ', $solicitud->estado) }}</p></div>
                    <div class="md:col-span-4"><p class="text-xs text-gray-500 uppercase">Motivo</p><p>{{ $solicitud->motivo ?: '—' }}</p></div>
                    <div class="md:col-span-4"><p class="text-xs text-gray-500 uppercase">Descripción</p><p>{{ $solicitud->descripcion ?: '—' }}</p></div>
                </div>
            </div>

            <div class="bg-white shadow rounded-lg p-5">
                <h3 class="font-semibold text-gray-800 mb-4">Evaluación de requerimientos</h3>
                <div class="space-y-4">
                    @foreach($solicitud->detalles as $index => $detalle)
                        <div class="border rounded-lg p-4">
                            <input type="hidden" name="detalles[{{ $index }}][solicitud_detalle_id]" value="{{ $detalle->id }}">

                            <div class="grid grid-cols-1 md:grid-cols-12 gap-3">
                                <div class="md:col-span-3">
                                    <p class="text-xs text-gray-500 uppercase">Requerimiento</p>
                                    <p class="font-semibold">{{ $detalle->descripcion_solicitada }}</p>
                                    <p class="text-sm text-gray-500">{{ $detalle->tipo_item }} · {{ $detalle->tipoActivo?->descripcion ?? '—' }}</p>
                                </div>

                                <div class="md:col-span-2">
                                    <label class="text-xs font-semibold text-gray-500">CANTIDAD SOLICITADA</label>
                                    <p class="mt-2 font-semibold">{{ $detalle->cantidad }}</p>
                                </div>

                                <div class="md:col-span-3">
                                    <label class="text-xs font-semibold text-gray-500">RESULTADO *</label>
                                    <select name="detalles[{{ $index }}][resultado]" required class="mt-1 w-full rounded-lg border-gray-300">
                                        @foreach(['REASIGNACION','REPARACION','STOCK','COMPRA','MEJORA','REEMPLAZO','OTRO'] as $resultado)
                                            <option value="{{ $resultado }}" @selected(old("detalles.$index.resultado") === $resultado)>{{ $resultado }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="md:col-span-2">
                                    <label class="text-xs font-semibold text-gray-500">CANTIDAD *</label>
                                    <input type="number" name="detalles[{{ $index }}][cantidad]" value="{{ old("detalles.$index.cantidad", $detalle->cantidad) }}" min="1" max="{{ $detalle->cantidad }}" required class="mt-1 w-full rounded-lg border-gray-300">
                                </div>

                                <div class="md:col-span-2">
                                    <label class="text-xs font-semibold text-gray-500">OBSERVACIONES</label>
                                    <input name="detalles[{{ $index }}][observaciones]" value="{{ old("detalles.$index.observaciones") }}" class="mt-1 w-full rounded-lg border-gray-300">
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="bg-white shadow rounded-lg p-5 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Justificación técnica</label>
                    <textarea name="justificacion" rows="4" class="mt-1 w-full rounded-lg border-gray-300">{{ old('justificacion') }}</textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Observaciones</label>
                    <textarea name="observaciones" rows="3" class="mt-1 w-full rounded-lg border-gray-300">{{ old('observaciones') }}</textarea>
                </div>
            </div>

            <div class="flex justify-end gap-3">
                <a href="{{ route('itam.solicitudes.show', $solicitud) }}" class="px-4 py-2 rounded-lg border bg-white">Cancelar</a>
                <button class="px-5 py-2 bg-red-700 hover:bg-red-800 text-white rounded-lg font-semibold">Registrar evaluación</button>
            </div>
        </form>
    </div>
</x-app-layout>
