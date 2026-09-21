<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800">{{ $solicitud->numero }}</h2>
                <p class="text-sm text-gray-500">Solicitud de TI</p>
            </div>
            <div class="flex gap-3">
                @if(in_array($solicitud->estado, ['PENDIENTE', 'EN_EVALUACION']))
                    @can('it.solicitudes.evaluate')
                        <a href="{{ route('itam.solicitudes.evaluaciones.create', $solicitud) }}" class="px-4 py-2 bg-red-700 hover:bg-red-800 text-white rounded-lg text-sm font-semibold">Evaluar solicitud</a>
                    @endcan
                @endif
                <a href="{{ route('itam.solicitudes.index') }}" class="px-4 py-2 rounded-lg border bg-white text-gray-700">Volver</a>
            </div>
        </div>
    </x-slot>

    <div class="py-6 max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-5">
        @if(session('success'))
            <div class="p-4 bg-green-100 text-green-700 rounded-lg">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="p-4 bg-red-100 text-red-700 rounded-lg">{{ session('error') }}</div>
        @endif

        <div class="bg-white shadow rounded-lg p-5 grid grid-cols-1 md:grid-cols-3 gap-4">
            <div><p class="text-xs text-gray-500 uppercase">Solicitante</p><p class="font-semibold">{{ $personal ? $personal->lastname.', '.$personal->name : 'No encontrado' }}</p></div>
            <div><p class="text-xs text-gray-500 uppercase">Licencia</p><p>{{ $personal->licence_id ?? '—' }}</p></div>
            <div><p class="text-xs text-gray-500 uppercase">Fecha</p><p>{{ $solicitud->fecha_solicitud?->format('d/m/Y') }}</p></div>
            <div><p class="text-xs text-gray-500 uppercase">Prioridad</p><p>{{ $solicitud->prioridad }}</p></div>
            <div><p class="text-xs text-gray-500 uppercase">Estado</p><p>{{ str_replace('_',' ',$solicitud->estado) }}</p></div>
            <div><p class="text-xs text-gray-500 uppercase">Ubicación</p><p>{{ $solicitud->ubicacion?->descripcion ?? '—' }}</p></div>
            <div class="md:col-span-3"><p class="text-xs text-gray-500 uppercase">Motivo</p><p>{{ $solicitud->motivo ?: '—' }}</p></div>
            <div class="md:col-span-3"><p class="text-xs text-gray-500 uppercase">Descripción</p><p>{{ $solicitud->descripcion ?: '—' }}</p></div>
        </div>

        <div class="bg-white shadow rounded-lg overflow-hidden">
            <div class="p-5 border-b"><h3 class="font-semibold">Requerimientos</h3></div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50"><tr>
                        <th class="px-5 py-3 text-left text-xs uppercase text-gray-500">Tipo</th>
                        <th class="px-5 py-3 text-left text-xs uppercase text-gray-500">Descripción</th>
                        <th class="px-5 py-3 text-left text-xs uppercase text-gray-500">Cantidad</th>
                        <th class="px-5 py-3 text-left text-xs uppercase text-gray-500">Estado</th>
                        <th class="px-5 py-3 text-left text-xs uppercase text-gray-500">Especificaciones</th>
                    </tr></thead>
                    <tbody class="divide-y">
                        @foreach($solicitud->detalles as $detalle)
                            <tr>
                                <td class="px-5 py-4">{{ $detalle->tipo_item }}</td>
                                <td class="px-5 py-4">{{ $detalle->descripcion_solicitada }}</td>
                                <td class="px-5 py-4">{{ $detalle->cantidad }}</td>
                                <td class="px-5 py-4">{{ $detalle->estado }}</td>
                                <td class="px-5 py-4">{{ $detalle->especificaciones ?: '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="bg-white shadow rounded-lg p-5">
            <h3 class="font-semibold mb-4">Historial de evaluación técnica</h3>
            <p class="text-sm text-gray-500">Las evaluaciones se conservarán como historial. Desde aquí se podrá consultar la evaluación vigente y las anteriores.</p>
        </div>
    </div>
</x-app-layout>
