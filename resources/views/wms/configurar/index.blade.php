<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            WMS - Configurar Cajas x Pallet
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-2xl mx-auto px-4">

            @if (session('success'))
                <div class="mb-4 p-3 bg-green-100 text-green-800 rounded-lg text-sm">
                    {{ session('success') }}
                </div>
            @endif

            <div class="flex justify-between items-center mb-4">
                <a href="{{ route('wms.index') }}" class="text-sm text-gray-600">&larr; Volver</a>
                <a href="{{ route('wms.configurar.create') }}"
                   class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-5 py-3 rounded-xl shadow">
                    + AGREGAR
                </a>
            </div>

            <div class="bg-white shadow rounded-xl overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-100 text-gray-700">
                        <tr>
                            <th class="p-3 text-left">Código</th>
                            <th class="p-3 text-left">Descripción</th>
                            <th class="p-3 text-right">Cajas x Pallet</th>
                            <th class="p-3"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($configuraciones as $c)
                            <tr class="border-t">
                                <td class="p-3 font-mono font-semibold">{{ $c->codigo }}</td>
                                <td class="p-3">{{ $c->descripcion }}</td>
                                <td class="p-3 text-right">{{ $c->cajas_x_pallet }}</td>
                                <td class="p-3 text-right">
                                    <form action="{{ route('wms.configurar.destroy', $c->codigo) }}" method="POST"
                                          onsubmit="return confirm('¿Eliminar esta configuración?');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="text-red-600 text-xs font-semibold">Eliminar</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="p-6 text-center text-gray-500">
                                    No hay configuraciones registradas.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</x-app-layout>