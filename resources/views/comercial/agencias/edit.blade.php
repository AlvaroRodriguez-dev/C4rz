<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">Editar Agencia</h2>
    </x-slot>

    <div class="py-6 sm:py-8 max-w-xl mx-auto px-4 sm:px-6 lg:px-8">
        <form action="{{ route('comercial.agencias.update', $agencia) }}" method="POST" class="bg-white shadow rounded-lg p-4 sm:p-6 space-y-4">
            @csrf @method('PUT')

            <div>
                <label class="block text-sm font-medium text-gray-700">Código</label>
                <input type="text" name="codigo" value="{{ old('codigo', $agencia->codigo) }}"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500">
                @error('codigo') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Descripción</label>
                <input type="text" name="descripcion" value="{{ old('descripcion', $agencia->descripcion) }}"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500">
                @error('descripcion') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Ciudad</label>
                <input type="text" name="ciudad" value="{{ old('ciudad', $agencia->ciudad) }}"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500">
                @error('ciudad') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Dirección</label>
                <textarea name="direccion" rows="2"
                          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500">{{ old('direccion', $agencia->direccion) }}</textarea>
                @error('direccion') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Enlace de Google Maps (opcional)</label>
                <input type="url" name="url_maps" value="{{ old('url_maps', $agencia->url_maps) }}" placeholder="https://maps.app.goo.gl/..."
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500">
                <p class="text-xs text-gray-400 mt-1">Copia el enlace "Compartir" desde Google Maps ubicando el pin exacto de la agencia.</p>
                @error('url_maps') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2 pt-2">
                <a href="{{ route('comercial.agencias.index') }}" class="px-4 py-2 bg-gray-200 rounded-lg text-center">Cancelar</a>
                <button type="submit" class="px-4 py-2 bg-red-700 hover:bg-red-800 text-white rounded-lg">Actualizar</button>
            </div>
        </form>
    </div>
</x-app-layout>