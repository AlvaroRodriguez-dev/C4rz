<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-800">WMS - Almacenes</h2>
                <p class="text-sm text-gray-500">Administración de almacenes y subalmacenes</p>
            </div>
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

                <a href="{{ route('wms.almacenes.create') }}"
                   class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-5 py-3 rounded-xl shadow">
                    + NUEVO ALMACÉN
                </a>
            </div>

            <div class="bg-white shadow rounded-xl overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-100 text-gray-700">
                            <tr>
                                <th class="p-3 text-left">Código</th>
                                <th class="p-3 text-left">Nombre</th>
                                <th class="p-3 text-left">Tipo</th>
                                <th class="p-3 text-left">Padre</th>
                                <th class="p-3 text-left">Prefijo</th>
                                <th class="p-3 text-center">Estado</th>
                                <th class="p-3 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($almacenes as $almacen)
                                <tr class="border-t">
                                    <td class="p-3 font-mono font-semibold">{{ $almacen->codigo }}</td>
                                    <td class="p-3">{{ $almacen->nombre }}</td>
                                    <td class="p-3">{{ $almacen->tipo }}</td>
                                    <td class="p-3">
                                        {{ $almacen->padre?->codigo ?? '—' }}
                                    </td>
                                    <td class="p-3 font-mono">
                                        {{ $almacen->prefijo_documento ?? '—' }}
                                    </td>
                                    <td class="p-3 text-center">
                                        @if ($almacen->activo)
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
                                        <a href="{{ route('wms.almacenes.edit', $almacen) }}"
                                           class="text-blue-600 hover:text-blue-800 font-semibold text-xs">
                                            EDITAR
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="p-6 text-center text-gray-500">
                                        No hay almacenes registrados.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
