<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">WMS - Galpones</h2>
            <p class="text-sm text-gray-500">Administración de galpones y preparación de posiciones</p>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-6xl mx-auto px-3">

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
                <a href="{{ route('wms.index') }}" class="text-sm text-gray-600 hover:text-gray-900">
                    &larr; Volver al WMS
                </a>

                <a href="{{ route('wms.galpones.create') }}"
                   class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-5 py-3 rounded-xl shadow">
                    + NUEVO GALPÓN
                </a>
            </div>

            @if ($almacenes->isEmpty())
                <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 rounded-xl p-4">
                    No existen almacenes principales activos para administrar galpones.
                </div>
            @else
                <div class="bg-white shadow rounded-xl p-4 mb-4">
                    <form method="GET" action="{{ route('wms.galpones.index') }}" class="flex flex-col sm:flex-row gap-3 sm:items-end">
                        <div class="flex-1">
                            <label for="almacen" class="block text-sm font-semibold text-gray-700 mb-1">
                                Almacén principal
                            </label>
                            <select id="almacen" name="almacen"
                                    class="w-full rounded-lg border-gray-300"
                                    onchange="this.form.submit()">
                                @foreach ($almacenes as $item)
                                    <option value="{{ $item->id }}" @selected($almacen?->id === $item->id)>
                                        {{ $item->codigo }} - {{ $item->nombre }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="text-sm text-gray-500 pb-2">
                            {{ $galpones->count() }} galpón(es)
                        </div>
                    </form>
                </div>

                <div class="bg-white shadow rounded-xl overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-100 text-gray-700">
                                <tr>
                                    <th class="p-3 text-left">Código</th>
                                    <th class="p-3 text-left">Nombre</th>
                                    <th class="p-3 text-center">Tramos</th>
                                    <th class="p-3 text-center">Posiciones</th>
                                    <th class="p-3 text-center">Estado</th>
                                    <th class="p-3 text-right">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($galpones as $galpon)
                                    <tr class="border-t">
                                        <td class="p-3 font-mono font-semibold">{{ $galpon->codigo }}</td>
                                        <td class="p-3">{{ $galpon->nombre }}</td>
                                        <td class="p-3 text-center">{{ $galpon->rangos_count }}</td>
                                        <td class="p-3 text-center">{{ $galpon->ubicaciones_count }}</td>
                                        <td class="p-3 text-center">
                                            @if ($galpon->activo)
                                                <span class="inline-flex px-2 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800">
                                                    ACTIVO
                                                </span>
                                            @else
                                                <span class="inline-flex px-2 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-700">
                                                    INACTIVO
                                                </span>
                                            @endif
                                        </td>
                                        <td class="p-3 text-right">
                                            <div class="flex flex-wrap gap-2 justify-end">
                                                <a href="{{ route('wms.galpones.rangos.index', $galpon) }}"
                                                   class="text-indigo-600 hover:text-indigo-800 font-semibold text-xs">
                                                    TRAMOS
                                                </a>
                                                <a href="{{ route('wms.galpones.edit', $galpon) }}"
                                                   class="text-blue-600 hover:text-blue-800 font-semibold text-xs">
                                                    EDITAR
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="p-6 text-center text-gray-500">
                                            No hay galpones registrados para este almacén.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
