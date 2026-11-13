<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <h2 class="font-semibold text-xl text-gray-800">Solicitudes de TI</h2>
            @can('it.solicitudes.create')
                <a href="{{ route('itam.solicitudes.create') }}" class="inline-flex justify-center px-4 py-2 bg-red-700 hover:bg-red-800 text-white rounded-lg text-sm font-semibold">+ Nueva solicitud</a>
            @endcan
        </div>
    </x-slot>

    <div class="py-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        @if(session('success'))
            <div class="mb-4 p-4 bg-green-100 text-green-700 rounded-lg">{{ session('success') }}</div>
        @endif

        <form method="GET" class="bg-white shadow rounded-lg p-4 mb-4 grid grid-cols-1 sm:grid-cols-3 gap-3">
            <input name="buscar" value="{{ request('buscar') }}" placeholder="Número, motivo o descripción" class="rounded-lg border-gray-300">
            <select name="estado" class="rounded-lg border-gray-300">
                <option value="">Todos los estados</option>
                @foreach(['PENDIENTE','EN_EVALUACION','EN_COMPRA','ATENDIDA','RECHAZADA','CERRADA'] as $estado)
                    <option value="{{ $estado }}" @selected(request('estado') === $estado)>{{ str_replace('_', ' ', $estado) }}</option>
                @endforeach
            </select>
            <button class="rounded-lg bg-gray-800 text-white px-4 py-2">Buscar</button>
        </form>

        <div class="hidden md:block bg-white shadow rounded-lg overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50"><tr>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Número</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Fecha</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Ubicación</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Prioridad</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Estado</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Acción</th>
                </tr></thead>
                <tbody class="divide-y divide-gray-200">
                @forelse($solicitudes as $solicitud)
                    <tr>
                        <td class="px-6 py-4 font-semibold">{{ $solicitud->numero }}</td>
                        <td class="px-6 py-4">{{ $solicitud->fecha_solicitud?->format('d/m/Y') }}</td>
                        <td class="px-6 py-4">{{ $solicitud->ubicacion?->descripcion ?? '—' }}</td>
                        <td class="px-6 py-4">{{ $solicitud->prioridad }}</td>
                        <td class="px-6 py-4">{{ str_replace('_', ' ', $solicitud->estado) }}</td>
                        <td class="px-6 py-4 text-right"><a class="text-blue-600 font-medium" href="{{ route('itam.solicitudes.show', $solicitud) }}">Ver</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-6 py-8 text-center text-gray-400">No hay solicitudes registradas.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="md:hidden space-y-3">
            @forelse($solicitudes as $solicitud)
                <a href="{{ route('itam.solicitudes.show', $solicitud) }}" class="block bg-white shadow rounded-lg p-4">
                    <div class="flex justify-between"><span class="font-semibold">{{ $solicitud->numero }}</span><span class="text-xs px-2 py-1 bg-gray-100 rounded-full">{{ $solicitud->estado }}</span></div>
                    <p class="text-sm text-gray-500 mt-2">{{ $solicitud->fecha_solicitud?->format('d/m/Y') }} · {{ $solicitud->prioridad }}</p>
                    <p class="text-sm mt-1">{{ $solicitud->ubicacion?->descripcion ?? 'Sin ubicación' }}</p>
                </a>
            @empty
                <div class="bg-white shadow rounded-lg p-8 text-center text-gray-400">No hay solicitudes registradas.</div>
            @endforelse
        </div>

        <div class="mt-4">{{ $solicitudes->links() }}</div>
    </div>
</x-app-layout>
