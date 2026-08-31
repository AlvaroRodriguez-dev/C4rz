<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">Editar Contacto</h2>
    </x-slot>

    <div class="py-6 sm:py-8 max-w-xl mx-auto px-4 sm:px-6 lg:px-8">
        <form action="{{ route('comercial.contactos.update', $contacto) }}" method="POST" enctype="multipart/form-data"
              class="bg-white shadow rounded-lg p-4 sm:p-6 space-y-4">
            @csrf @method('PUT')

            @if($contacto->foto_url)
                <div class="flex justify-center">
                    <img src="{{ $contacto->foto_url }}" class="w-20 h-20 rounded-full object-cover">
                </div>
            @endif

            <div>
                <label class="block text-sm font-medium text-gray-700">Nombre completo</label>
                <input type="text" name="nombre" value="{{ old('nombre', $contacto->nombre) }}"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500">
                @error('nombre') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Cargo</label>
                <input type="text" name="cargo" value="{{ old('cargo', $contacto->cargo) }}"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500">
                @error('cargo') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Agencia</label>
                <select name="agencia_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500">
                    @foreach($agencias as $agencia)
                        <option value="{{ $agencia->id }}" @selected(old('agencia_id', $contacto->agencia_id) == $agencia->id)>
                            {{ $agencia->codigo }} - {{ $agencia->descripcion }} ({{ $agencia->ciudad }})
                        </option>
                    @endforeach
                </select>
                @error('agencia_id') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Teléfono</label>
                    <input type="text" name="telefono" value="{{ old('telefono', $contacto->telefono) }}"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500">
                    @error('telefono') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Email</label>
                    <input type="email" name="email" value="{{ old('email', $contacto->email) }}"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500">
                    @error('email') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Foto (dejar en blanco para no modificar)</label>
                <input type="file" name="foto" accept="image/*"
                       class="mt-1 block w-full text-sm text-gray-600 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-red-50 file:text-red-700 hover:file:bg-red-100">
                @error('foto') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="flex items-center gap-2">
                <input type="checkbox" name="activo" value="1" @checked(old('activo', $contacto->activo))
                       class="rounded border-gray-300 text-red-600 focus:ring-red-500">
                <label class="text-sm font-medium text-gray-700">Activo (visible públicamente)</label>
            </div>

            <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2 pt-2">
                <a href="{{ route('comercial.contactos.index') }}" class="px-4 py-2 bg-gray-200 rounded-lg text-center">Cancelar</a>
                <button type="submit" class="px-4 py-2 bg-red-700 hover:bg-red-800 text-white rounded-lg">Actualizar</button>
            </div>
        </form>
    </div>
</x-app-layout>
