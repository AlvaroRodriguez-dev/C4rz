<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">WMS - Tramos de posiciones</h2>
            <p class="text-sm text-gray-500">
                {{ $galpon->almacen->codigo }} - {{ $galpon->almacen->nombre }}
                · {{ $galpon->codigo }} - {{ $galpon->nombre }}
            </p>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-5xl mx-auto px-3">
            @if (session('success'))
                <div class="mb-4 rounded-xl bg-green-100 border border-green-300 text-green-800 p-3">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-4 rounded-xl bg-red-100 border border-red-300 text-red-800 p-3">
                    <ul class="list-disc list-inside text-sm">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="flex flex-wrap gap-2 justify-between items-center mb-4">
                <a href="{{ route('wms.galpones.index', ['almacen' => $galpon->almacen_id]) }}"
                   class="text-sm text-gray-600 hover:text-gray-900">
                    &larr; Volver a galpones
                </a>
                <a href="{{ route('wms.galpones.rangos.create', $galpon) }}"
                   class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-5 py-3 rounded-xl shadow">
                    + AGREGAR TRAMO
                </a>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-5">
                <div class="bg-white shadow rounded-xl p-4">
                    <div class="text-xs uppercase tracking-wide text-gray-500">Galpón</div>
                    <div class="mt-1 text-xl font-bold text-gray-800">{{ $galpon->codigo }}</div>
                    <div class="text-sm text-gray-500">{{ $galpon->nombre }}</div>
                </div>
                <div class="bg-white shadow rounded-xl p-4">
                    <div class="text-xs uppercase tracking-wide text-gray-500">Tramos configurados</div>
                    <div class="mt-1 text-xl font-bold text-gray-800">{{ $rangos->count() }}</div>
                    <div class="text-sm text-gray-500">La numeración puede tener saltos.</div>
                </div>
                <div class="bg-white shadow rounded-xl p-4">
                    <div class="text-xs uppercase tracking-wide text-gray-500">Posiciones del galpón</div>
                    <div class="mt-1 text-xl font-bold text-gray-800">{{ $totalPosiciones }}</div>
                    <div class="text-sm text-gray-500">Existentes en el maestro.</div>
                </div>
            </div>

            <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 mb-5 text-sm text-blue-800">
                <strong>Regla de numeración:</strong>
                un galpón puede tener varios tramos. Por ejemplo, G2 puede tener
                21–30 y posteriormente 41–42. Las posiciones ya creadas no se renumeran ni se eliminan.
            </div>

            <div class="bg-white shadow rounded-xl overflow-hidden">
                <div class="px-4 py-3 border-b bg-gray-50">
                    <h3 class="font-semibold text-gray-800">Tramos configurados</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-100 text-gray-700">
                            <tr>
                                <th class="p-3 text-center">#</th>
                                <th class="p-3 text-right">Desde</th>
                                <th class="p-3 text-right">Hasta</th>
                                <th class="p-3 text-right">Cantidad</th>
                                <th class="p-3 text-right">Posiciones generadas</th>
                                <th class="p-3 text-center">Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($rangos as $indice => $rango)
                                <tr class="border-t">
                                    <td class="p-3 text-center font-semibold">{{ $indice + 1 }}</td>
                                    <td class="p-3 text-right font-mono">{{ $rango->desde }}</td>
                                    <td class="p-3 text-right font-mono">{{ $rango->hasta }}</td>
                                    <td class="p-3 text-right font-semibold">
                                        {{ number_format($rango->cantidad(), 0, ',', '.') }}
                                    </td>
                                    <td class="p-3 text-right">
                                        {{ number_format($rango->posiciones_generadas, 0, ',', '.') }}
                                    </td>
                                    <td class="p-3 text-center">
                                        @if ($rango->activo)
                                            <span class="inline-flex px-2 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800">
                                                ACTIVO
                                            </span>
                                        @else
                                            <span class="inline-flex px-2 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-700">
                                                INACTIVO
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="p-8 text-center text-gray-500">
                                        Este galpón todavía no tiene tramos configurados.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-4 text-xs text-gray-500">
                Los tramos son históricos: no se editan para cambiar su numeración.
                Para ampliar un galpón se agrega un nuevo tramo.
            </div>
        </div>
    </div>
</x-app-layout>
