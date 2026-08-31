<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3">
            <h2 class="font-semibold text-xl text-gray-800">Contactos Comerciales</h2>
            <a href="{{ route('comercial.contactos.create') }}"
               class="inline-flex justify-center px-4 py-2 bg-red-700 hover:bg-red-800 text-white rounded-lg text-sm font-semibold">
                + Nuevo Contacto
            </a>
        </div>
    </x-slot>

    <div class="py-6 sm:py-8 max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        @if(session('success'))
            <div class="mb-4 p-4 bg-green-100 text-green-700 rounded-lg text-sm">{{ session('success') }}</div>
        @endif

        {{-- Buscador --}}
        <form method="GET" action="{{ route('comercial.contactos.index') }}"
              class="bg-white shadow rounded-lg p-4 mb-4 flex flex-col sm:flex-row gap-3">
            <div class="flex-1">
                <input type="text" name="buscar" value="{{ request('buscar') }}"
                       placeholder="Buscar por nombre, cargo, teléfono o agencia..."
                       class="block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 text-sm">
            </div>
            <div class="sm:w-48">
                <select name="estado" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 text-sm">
                    <option value="">Todos los estados</option>
                    <option value="activo" @selected(request('estado') === 'activo')>Activo</option>
                    <option value="inactivo" @selected(request('estado') === 'inactivo')>Inactivo</option>
                </select>
            </div>
            <div class="flex gap-2">
                <button type="submit"
                        class="px-4 py-2 bg-red-700 hover:bg-red-800 text-white rounded-lg text-sm font-semibold whitespace-nowrap">
                    Buscar
                </button>
                @if(request('buscar') || request('estado'))
                    <a href="{{ route('comercial.contactos.index') }}"
                       class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg text-sm font-semibold whitespace-nowrap">
                        Limpiar
                    </a>
                @endif
            </div>
        </form>

        @if(request('buscar') || request('estado'))
            <p class="text-sm text-gray-500 mb-4">
                {{ $contactos->total() }} resultado(s) encontrado(s).
            </p>
        @endif

        {{-- Vista tabla: solo desktop --}}
        <div class="hidden md:block bg-white shadow rounded-lg overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Foto</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Nombre</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Cargo</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Agencia</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Teléfono</th>
                        <th class="px-6 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Estado</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($contactos as $contacto)
                        <tr>
                            <td class="px-6 py-4">
                                @if($contacto->foto_url)
                                    <img src="{{ $contacto->foto_url }}" class="w-10 h-10 rounded-full object-cover">
                                @else
                                    <div class="w-10 h-10 rounded-full bg-gray-200 flex items-center justify-center text-gray-400 text-xs">N/A</div>
                                @endif
                            </td>
                            <td class="px-6 py-4 font-medium">{{ $contacto->nombre }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ $contacto->cargo }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ $contacto->agencia->descripcion }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ $contacto->telefono }}</td>
                            <td class="px-6 py-4 text-center">
                                @if($contacto->activo)
                                    <span class="px-2 py-1 text-xs bg-green-100 text-green-700 rounded-full">Activo</span>
                                @else
                                    <span class="px-2 py-1 text-xs bg-gray-100 text-gray-500 rounded-full">Inactivo</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right space-x-2 whitespace-nowrap">
                                <a href="{{ route('comercial.contactos.show', $contacto) }}"
                                   class="text-gray-600 hover:text-gray-900 font-medium">Ver/QR</a>
                                <a href="{{ route('comercial.contactos.edit', $contacto) }}"
                                   class="text-yellow-600 hover:text-yellow-800 font-medium">Editar</a>
                                <form action="{{ route('comercial.contactos.destroy', $contacto) }}" method="POST"
                                      class="inline" onsubmit="return confirm('¿Eliminar este contacto?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-800 font-medium">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-8 text-center text-gray-400">
                                @if(request('buscar') || request('estado'))
                                    No se encontraron contactos con esos filtros.
                                @else
                                    No hay contactos registrados.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Vista cards: solo mobile/tablet --}}
        <div class="md:hidden space-y-3">
            @forelse($contactos as $contacto)
                <div class="bg-white shadow rounded-lg p-4">
                    <div class="flex items-center gap-3">
                        @if($contacto->foto_url)
                            <img src="{{ $contacto->foto_url }}" class="w-12 h-12 rounded-full object-cover shrink-0">
                        @else
                            <div class="w-12 h-12 rounded-full bg-gray-200 flex items-center justify-center text-gray-400 text-xs shrink-0">N/A</div>
                        @endif
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center justify-between gap-2">
                                <p class="font-semibold text-gray-800 truncate">{{ $contacto->nombre }}</p>
                                @if($contacto->activo)
                                    <span class="shrink-0 px-2 py-0.5 text-xs bg-green-100 text-green-700 rounded-full">Activo</span>
                                @else
                                    <span class="shrink-0 px-2 py-0.5 text-xs bg-gray-100 text-gray-500 rounded-full">Inactivo</span>
                                @endif
                            </div>
                            <p class="text-sm text-gray-500 truncate">{{ $contacto->cargo }}</p>
                        </div>
                    </div>

                    <div class="mt-3 text-sm text-gray-600 space-y-1">
                        <p><span class="text-gray-400">Agencia:</span> {{ $contacto->agencia->descripcion }}</p>
                        <p><span class="text-gray-400">Teléfono:</span> {{ $contacto->telefono }}</p>
                    </div>

                    <div class="mt-3 pt-3 border-t flex justify-end gap-4 text-sm">
                        <a href="{{ route('comercial.contactos.show', $contacto) }}" class="text-gray-600 font-medium">Ver/QR</a>
                        <a href="{{ route('comercial.contactos.edit', $contacto) }}" class="text-yellow-600 font-medium">Editar</a>
                        <form action="{{ route('comercial.contactos.destroy', $contacto) }}" method="POST"
                              onsubmit="return confirm('¿Eliminar este contacto?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-red-600 font-medium">Eliminar</button>
                        </form>
                    </div>
                </div>
            @empty
                <div class="bg-white shadow rounded-lg p-8 text-center text-gray-400">
                    @if(request('buscar') || request('estado'))
                        No se encontraron contactos con esos filtros.
                    @else
                        No hay contactos registrados.
                    @endif
                </div>
            @endforelse
        </div>

        <div class="mt-4">{{ $contactos->links() }}</div>
    </div>
</x-app-layout>
