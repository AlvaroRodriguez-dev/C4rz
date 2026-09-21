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

            <div class="bg-white shadow rounded-lg p-5" x-data="evaluacionForm()">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-4">
                    <div>
                        <h3 class="font-semibold text-gray-800">Evaluación de requerimientos</h3>
                        <p class="text-sm text-gray-500">La suma de decisiones debe cubrir exactamente la cantidad solicitada.</p>
                    </div>
                    <span class="text-sm font-semibold" :class="todoResuelto ? 'text-green-700' : 'text-amber-700'" x-text="resumenGeneral"></span>
                </div>

                <div class="space-y-5">
                    @foreach($solicitud->detalles as $detalle)
                        <div class="border rounded-lg p-4">
                            <div class="flex flex-col sm:flex-row sm:justify-between gap-2 mb-3">
                                <div>
                                    <p class="font-semibold">{{ $detalle->descripcion_solicitada }}</p>
                                    <p class="text-sm text-gray-500">{{ $detalle->tipo_item }} · {{ $detalle->tipoActivo?->descripcion ?? '—' }}</p>
                                </div>
                                <div class="text-sm">
                                    Solicitada: <strong>{{ $detalle->cantidad }}</strong>
                                    · Evaluada: <strong x-text="sumaDetalle({{ $detalle->id }})"></strong>
                                    · Pendiente: <strong x-text="{{ $detalle->cantidad }} - sumaDetalle({{ $detalle->id }})"></strong>
                                </div>
                            </div>

                            <div class="space-y-3">
                                <template x-for="decision in decisiones.filter(d => d.detalleId === {{ $detalle->id }})" :key="decision.key">
                                    <div class="grid grid-cols-1 md:grid-cols-12 gap-3 bg-gray-50 rounded-lg p-3">
                                        <input type="hidden" :name="'detalles[' + decision.key + '][solicitud_detalle_id]'" value="{{ $detalle->id }}">

                                        <div class="md:col-span-4">
                                            <label class="text-xs font-semibold text-gray-500">RESULTADO *</label>
                                            <select :name="'detalles[' + decision.key + '][resultado]'" x-model="decision.resultado" required class="mt-1 w-full rounded-lg border-gray-300">
                                                @foreach(['REASIGNACION','REPARACION','STOCK','COMPRA','MEJORA','REEMPLAZO','OTRO'] as $resultado)
                                                    <option value="{{ $resultado }}">{{ $resultado }}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="md:col-span-3">
                                            <label class="text-xs font-semibold text-gray-500">CANTIDAD *</label>
                                            <input type="number" min="1" :max="cantidadMaxima(decision)" :name="'detalles[' + decision.key + '][cantidad]'" x-model.number="decision.cantidad" required class="mt-1 w-full rounded-lg border-gray-300">
                                        </div>

                                        <div class="md:col-span-4">
                                            <label class="text-xs font-semibold text-gray-500">OBSERVACIONES</label>
                                            <input :name="'detalles[' + decision.key + '][observaciones]'" x-model="decision.observaciones" class="mt-1 w-full rounded-lg border-gray-300">
                                        </div>

                                        <div class="md:col-span-1 flex items-end justify-end">
                                            <button type="button" @click="quitar(decision.key)" x-show="cantidadDecisiones({{ $detalle->id }}) > 1" class="text-red-600 text-sm">Quitar</button>
                                        </div>
                                    </div>
                                </template>
                            </div>

                            <button type="button" @click="agregar({{ $detalle->id }}, {{ $detalle->cantidad }})" class="mt-3 text-sm text-blue-700 hover:text-blue-900 font-medium">
                                + Dividir decisión
                            </button>
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
