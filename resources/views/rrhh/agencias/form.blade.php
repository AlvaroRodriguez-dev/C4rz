<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $agencia->id ? '✏️ Editar Agencia' : '🏢 Nueva Agencia' }}
        </h2>
    </x-slot>

    <div class="py-8 max-w-2xl mx-auto px-4">
        <div class="bg-white rounded-xl shadow p-6">

            <form method="POST"
                  action="{{ $agencia->id
                             ? route('rrhh.agencias.update', $agencia)
                             : route('rrhh.agencias.store') }}">
                @csrf
                @if($agencia->id) @method('PUT') @endif

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

                    {{-- Código --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Código <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="codigo"
                               value="{{ old('codigo', $agencia->codigo) }}"
                               maxlength="20" required
                               class="w-full border-gray-300 rounded-lg shadow-sm
                                      focus:ring-indigo-500 focus:border-indigo-500
                                      @error('codigo') border-red-400 @enderror">
                        @error('codigo')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Nombre --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Nombre <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="nombre"
                               value="{{ old('nombre', $agencia->nombre) }}"
                               maxlength="150" required
                               class="w-full border-gray-300 rounded-lg shadow-sm
                                      focus:ring-indigo-500 focus:border-indigo-500
                                      @error('nombre') border-red-400 @enderror">
                        @error('nombre')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Latitud --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Latitud <span class="text-red-500">*</span>
                        </label>
                        <input type="number" name="latitud" id="latitud"
                               value="{{ old('latitud', $agencia->latitud) }}"
                               step="0.0000001" required
                               placeholder="-17.7834000"
                               class="w-full border-gray-300 rounded-lg shadow-sm
                                      focus:ring-indigo-500 focus:border-indigo-500
                                      @error('latitud') border-red-400 @enderror">
                        @error('latitud')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Longitud --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Longitud <span class="text-red-500">*</span>
                        </label>
                        <input type="number" name="longitud" id="longitud"
                               value="{{ old('longitud', $agencia->longitud) }}"
                               step="0.0000001" required
                               placeholder="-63.1821000"
                               class="w-full border-gray-300 rounded-lg shadow-sm
                                      focus:ring-indigo-500 focus:border-indigo-500
                                      @error('longitud') border-red-400 @enderror">
                        @error('longitud')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Tolerancia --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Tolerancia (metros) <span class="text-red-500">*</span>
                        </label>
                        <input type="number" name="tolerancia"
                               value="{{ old('tolerancia', $agencia->tolerancia ?? 100) }}"
                               min="10" max="5000" required
                               class="w-full border-gray-300 rounded-lg shadow-sm
                                      focus:ring-indigo-500 focus:border-indigo-500
                                      @error('tolerancia') border-red-400 @enderror">
                        <p class="text-xs text-gray-400 mt-1">
                            Radio en metros dentro del cual se permite el marcaje.
                        </p>
                        @error('tolerancia')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Activo (solo en edición) --}}
                    @if($agencia->id)
                    <div class="flex items-center gap-3 mt-2">
                        <input type="checkbox" name="activo" id="activo" value="1"
                               {{ old('activo', $agencia->activo) ? 'checked' : '' }}
                               class="w-4 h-4 text-indigo-600 rounded">
                        <label for="activo" class="text-sm font-medium text-gray-700">
                            Agencia activa
                        </label>
                    </div>
                    @endif

                </div>

                {{-- Botón obtener coordenadas actuales --}}
                <div class="mt-4">
                    <button type="button" onclick="obtenerCoordenadas()"
                            class="text-sm text-indigo-600 hover:text-indigo-800
                                   font-medium flex items-center gap-1">
                        📍 Usar mi ubicación actual como coordenadas
                    </button>
                    <p id="msgUbicacion" class="text-xs text-gray-400 mt-1 hidden"></p>
                </div>

                {{-- Mapa de previsualización --}}
                <div class="mt-4">
                    <p class="text-xs font-medium text-gray-500 mb-2">
                        Vista previa en mapa
                        <span class="text-gray-400 font-normal">
                            (se actualiza al ingresar coordenadas)
                        </span>
                    </p>
                    <div id="mapaPreview"
                         class="w-full rounded-lg overflow-hidden border border-gray-200"
                         style="height: 220px; background: #f3f4f6;">
                        <p class="text-center text-gray-400 text-sm pt-20">
                            Ingresa coordenadas para ver el mapa
                        </p>
                    </div>
                </div>

                <div class="mt-6 flex gap-3">
                    <button type="submit"
                            class="bg-indigo-600 hover:bg-indigo-700 text-white
                                   font-semibold py-2 px-6 rounded-lg transition">
                        {{ $agencia->id ? 'Actualizar' : 'Guardar' }}
                    </button>
                    <a href="{{ route('rrhh.agencias.index') }}"
                       class="bg-gray-200 hover:bg-gray-300 text-gray-700
                              font-semibold py-2 px-6 rounded-lg transition">
                        Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script>
    function obtenerCoordenadas() {
        const msg = document.getElementById('msgUbicacion');
        msg.textContent = 'Obteniendo ubicación...';
        msg.classList.remove('hidden');

        navigator.geolocation.getCurrentPosition(
            pos => {
                document.getElementById('latitud').value =
                    pos.coords.latitude.toFixed(7);
                document.getElementById('longitud').value =
                    pos.coords.longitude.toFixed(7);
                msg.textContent = '✅ Coordenadas obtenidas correctamente.';
                actualizarMapa(pos.coords.latitude, pos.coords.longitude);
            },
            err => {
                msg.textContent = '❌ No se pudo obtener la ubicación: ' + err.message;
            },
            { enableHighAccuracy: true }
        );
    }

    function actualizarMapa(lat, lng) {
        if (!lat || !lng) return;
        const url = `https://maps.google.com/maps?q=${lat},${lng}&z=16&output=embed`;
        document.getElementById('mapaPreview').innerHTML =
            `<iframe src="${url}" width="100%" height="220"
                     style="border:0;" allowfullscreen loading="lazy"></iframe>`;
    }

    // Actualizar mapa al cambiar coordenadas manualmente
    ['latitud', 'longitud'].forEach(id => {
        document.getElementById(id).addEventListener('change', () => {
            const lat = parseFloat(document.getElementById('latitud').value);
            const lng = parseFloat(document.getElementById('longitud').value);
            if (lat && lng) actualizarMapa(lat, lng);
        });
    });

    // Cargar mapa si ya hay coordenadas (edición)
    window.addEventListener('load', () => {
        const lat = parseFloat(document.getElementById('latitud').value);
        const lng = parseFloat(document.getElementById('longitud').value);
        if (lat && lng) actualizarMapa(lat, lng);
    });
    </script>
</x-app-layout>