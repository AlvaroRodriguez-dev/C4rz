<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3">
            <h2 class="font-semibold text-xl text-gray-800">Agencias</h2>
            <a href="{{ route('comercial.agencias.create') }}"
               class="inline-flex justify-center px-4 py-2 bg-red-700 hover:bg-red-800 text-white rounded-lg text-sm font-semibold">
                + Nueva Agencia
            </a>
        </div>
    </x-slot>

    <div class="py-6 sm:py-8 max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        @if(session('success'))
            <div class="mb-4 p-4 bg-green-100 text-green-700 rounded-lg text-sm">{{ session('success') }}</div>
        @endif

        {{-- Vista tabla: solo desktop --}}
        <div class="hidden md:block bg-white shadow rounded-lg overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Código</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Descripción</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Ciudad</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Dirección</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Url</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($agencias as $agencia)
                        <tr>
                            <td class="px-6 py-4 font-medium">{{ $agencia->codigo }}</td>
                            <td class="px-6 py-4">{{ $agencia->descripcion }}</td>
                            <td class="px-6 py-4">{{ $agencia->ciudad }}</td>
                            <td class="px-6 py-4 text-gray-500">{{ $agencia->direccion }}</td>
                            <td class="px-6 py-4 text-gray-500">{{ $agencia->url_maps }}</td>
                            <td class="px-6 py-4 text-right space-x-2 whitespace-nowrap">
                                <a href="{{ route('comercial.agencias.edit', $agencia) }}"
                                   class="text-yellow-600 hover:text-yellow-800 font-medium">Editar</a>
                                <form action="{{ route('comercial.agencias.destroy', $agencia) }}" method="POST"
                                      class="inline" onsubmit="return confirm('¿Eliminar esta agencia?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-800 font-medium">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-gray-400">No hay agencias registradas.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Vista cards: solo mobile/tablet --}}
        <div class="md:hidden space-y-3">
            @forelse($agencias as $agencia)
                <div class="bg-white shadow rounded-lg p-4">
                    <div class="flex justify-between items-start gap-2">
                        <div class="min-w-0">
                            <p class="text-xs text-gray-400 uppercase font-semibold">{{ $agencia->codigo }}</p>
                            <p class="font-semibold text-gray-800 truncate">{{ $agencia->descripcion }}</p>
                        </div>
                        <span class="shrink-0 px-2 py-1 text-xs bg-gray-100 text-gray-600 rounded-full">{{ $agencia->ciudad }}</span>
                    </div>
                    <p class="text-sm text-gray-500 mt-2">{{ $agencia->direccion }}</p>
                    <div class="mt-3 pt-3 border-t flex justify-end gap-4 text-sm">
                        <a href="{{ route('comercial.agencias.edit', $agencia) }}" class="text-yellow-600 font-medium">Editar</a>
                        <form action="{{ route('comercial.agencias.destroy', $agencia) }}" method="POST"
                              onsubmit="return confirm('¿Eliminar esta agencia?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-red-600 font-medium">Eliminar</button>
                        </form>
                    </div>
                </div>
            @empty
                <div class="bg-white shadow rounded-lg p-8 text-center text-gray-400">No hay agencias registradas.</div>
            @endforelse
        </div>

        <div class="mt-4">{{ $agencias->links() }}</div>
    </div>
</x-app-layout>
