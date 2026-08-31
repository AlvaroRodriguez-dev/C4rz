<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            📸 Registro de Asistencia
        </h2>
    </x-slot>

    <div class="py-6 max-w-lg mx-auto px-4">

        @if (isset($sinLicense) && $sinLicense)
            <div class="bg-red-50 border border-red-200 rounded-xl p-6 text-center">
                <p class="text-red-600 font-semibold text-lg mb-2">⚠️ Sin empleado asignado</p>
                <p class="text-red-500 text-sm">Tu usuario no tiene un número de empleado asignado.
                    Contacta al administrador del sistema.</p>
            </div>
        @else
            {{-- Datos del empleado --}}
            @if ($personal)
                <div class="bg-white rounded-xl shadow mb-4 px-5 py-3 flex items-center gap-3">
                    <div
                        class="w-10 h-10 rounded-full bg-indigo-100 flex items-center justify-center
                        text-indigo-700 font-bold text-lg">
                        {{ strtoupper(substr($personal->NAME, 0, 1)) }}
                    </div>
                    <div>
                        <p class="font-semibold text-gray-800">
                            {{ $personal->NAME }} {{ $personal->LASTNAME }}
                        </p>
                        <p class="text-xs text-gray-400">CI: {{ $personal->LICENSE }}</p>
                    </div>
                </div>
            @endif

            {{-- Registros del día --}}
            <div class="bg-white rounded-xl shadow mb-4 px-5 py-4">
                <p class="text-sm font-semibold text-gray-600 mb-3">
                    📋 Registros de hoy — <span id="fechaHoy"></span>
                </p>
                <div id="listaRegistros" class="space-y-2">
                    @forelse($registrosHoy as $r)
                        <div class="flex items-center gap-3 text-sm">
                            <span
                                class="px-2 py-0.5 rounded text-xs font-bold
                            {{ $r->tipo === 'INGRESO' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                {{ $r->tipo }}
                            </span>
                            <span class="text-gray-600 font-mono">
                                {{ \Carbon\Carbon::parse($r->fecha_servidor)->format('H:i:s') }}
                            </span>
                            <img src="{{ asset('storage/' . $r->foto) }}"
                                class="w-8 h-8 rounded-full object-cover border border-gray-200"
                                onerror="this.src='https://ui-avatars.com/api/?name={{ urlencode($r->tipo) }}&size=32'">
                        </div>
                    @empty
                        <p class="text-gray-400 text-xs text-center py-2">
                            Sin registros por ahora
                        </p>
                    @endforelse
                </div>
            </div>

            {{-- Cámara --}}
            <div class="bg-white rounded-xl shadow p-4">

                {{-- Alerta de ubicación --}}
                <div id="alertaUbicacion"
                    class="hidden mb-3 bg-red-50 border border-red-200 rounded-lg p-3 text-sm text-red-700">
                    ⚠️ Debes otorgar permiso de ubicación para registrar tu asistencia.
                </div>

                {{-- Video de cámara --}}
                <div class="relative rounded-xl overflow-hidden bg-black mb-4" style="aspect-ratio: 1/1;">
                    <video id="video" autoplay playsinline muted
                        class="w-full h-full object-cover scale-x-[-1]"></video>
                    <canvas id="canvas" class="hidden"></canvas>

                    {{-- Preview de foto tomada --}}
                    <img id="preview" class="hidden absolute inset-0 w-full h-full object-cover">

                    {{-- Indicador de ubicación --}}
                    <div id="indicadorUbicacion"
                        class="absolute top-2 right-2 bg-black bg-opacity-50 text-white
                            text-xs px-2 py-1 rounded-full flex items-center gap-1">
                        <span id="iconoUbicacion">📍</span>
                        <span id="textoUbicacion">Obteniendo...</span>
                    </div>
                </div>

                {{-- Botones --}}
                <div class="grid grid-cols-2 gap-3 mb-3">
                    <button id="btnIngreso" onclick="registrar('INGRESO')"
                        class="bg-green-500 hover:bg-green-600 disabled:opacity-40
                               disabled:cursor-not-allowed text-white font-bold py-3
                               rounded-xl transition flex items-center justify-center gap-2">
                        ✅ INGRESO
                    </button>
                    <button id="btnSalida" onclick="registrar('SALIDA')"
                        class="bg-red-500 hover:bg-red-600 disabled:opacity-40
                               disabled:cursor-not-allowed text-white font-bold py-3
                               rounded-xl transition flex items-center justify-center gap-2">
                        🚪 SALIDA
                    </button>
                </div>

                {{-- Repetir foto --}}
                <button id="btnRepetir" onclick="repetirFoto()"
                    class="hidden w-full bg-gray-200 hover:bg-gray-300 text-gray-700
                           font-semibold py-2 rounded-xl transition text-sm">
                    🔄 Tomar otra foto
                </button>

                {{-- Spinner --}}
                <div id="spinner" class="hidden mt-3 flex items-center justify-center gap-2 text-gray-500">
                    <svg class="animate-spin h-5 w-5 text-indigo-600" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                            stroke-width="4" />
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z" />
                    </svg>
                    <span id="spinnerMsg">Registrando...</span>
                </div>

                {{-- Resultado --}}
                <div id="resultado" class="hidden mt-3"></div>
            </div>

        @endif
    </div>

    <script>
        // ── Estado global ────────────────────────────────────────────────
        let stream = null;
        let fotoBase64 = null;
        let latitud = null;
        let longitud = null;
        let ubicacionOk = false;

        const token = '{{ csrf_token() }}';
        const urlRegistrar = '{{ route('asistencia.registrar') }}';

        // ── Fecha de hoy ─────────────────────────────────────────────────
        document.getElementById('fechaHoy').textContent =
            new Date().toLocaleDateString('es-BO', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });

        // ── Iniciar cámara frontal ───────────────────────────────────────
        async function iniciarCamara() {
            try {
                stream = await navigator.mediaDevices.getUserMedia({
                    video: {
                        facingMode: 'user',
                        width: 400,
                        height: 400
                    },
                    audio: false,
                });
                document.getElementById('video').srcObject = stream;
            } catch (e) {
                document.getElementById('resultado').innerHTML = `
                <div class="bg-red-50 border border-red-200 rounded-lg p-3 text-sm text-red-700">
                    ❌ No se pudo acceder a la cámara: ${e.message}
                </div>`;
                document.getElementById('resultado').classList.remove('hidden');
            }
        }

        // ── Obtener ubicación ────────────────────────────────────────────
        function iniciarUbicacion() {
            if (!navigator.geolocation) {
                document.getElementById('textoUbicacion').textContent = 'No disponible';
                return;
            }

            navigator.geolocation.watchPosition(
                pos => {
                    latitud = pos.coords.latitude;
                    longitud = pos.coords.longitude;
                    ubicacionOk = true;
                    document.getElementById('iconoUbicacion').textContent = '✅';
                    document.getElementById('textoUbicacion').textContent =
                        latitud.toFixed(4) + ', ' + longitud.toFixed(4);
                    document.getElementById('alertaUbicacion').classList.add('hidden');
                },
                err => {
                    ubicacionOk = false;
                    document.getElementById('iconoUbicacion').textContent = '❌';
                    document.getElementById('textoUbicacion').textContent = 'Sin permiso';
                    document.getElementById('alertaUbicacion').classList.remove('hidden');
                }, {
                    enableHighAccuracy: true,
                    timeout: 10000
                }
            );
        }

        // ── Tomar foto y registrar ───────────────────────────────────────
        async function registrar(tipo) {
            if (!ubicacionOk) {
                document.getElementById('alertaUbicacion').classList.remove('hidden');
                return;
            }

            const video = document.getElementById('video');
            const canvas = document.getElementById('canvas');
            const ctx = canvas.getContext('2d');

            // Capturar frame actual de la cámara
            canvas.width = 200;
            canvas.height = 200;
            ctx.save();
            ctx.scale(-1, 1); // espejo horizontal
            ctx.drawImage(video, -200, 0, 200, 200);
            ctx.restore();

            fotoBase64 = canvas.toDataURL('image/jpeg', 0.7);

            // Mostrar preview
            const preview = document.getElementById('preview');
            preview.src = fotoBase64;
            preview.classList.remove('hidden');
            video.classList.add('hidden');
            document.getElementById('btnRepetir').classList.remove('hidden');

            // Deshabilitar botones durante envío
            document.getElementById('btnIngreso').disabled = true;
            document.getElementById('btnSalida').disabled = true;
            document.getElementById('spinner').classList.remove('hidden');
            document.getElementById('spinnerMsg').textContent =
                'Registrando ' + tipo + '...';
            document.getElementById('resultado').classList.add('hidden');

            // Obtener dirección aproximada (reverse geocoding gratuito)
            let direccion = null;
            try {
                const geo = await fetch(
                    `https://nominatim.openstreetmap.org/reverse?lat=${latitud}&lon=${longitud}&format=json`
                );
                const geoData = await geo.json();
                direccion = geoData.display_name ?? null;
            } catch {}

            try {
                const resp = await fetch(urlRegistrar, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': token
                    },
                    body: JSON.stringify({
                        tipo,
                        foto: fotoBase64,
                        fecha_cliente: new Date().toISOString(),
                        latitud,
                        longitud,
                        direccion,
                    }),
                });

                const data = await resp.json();
                document.getElementById('spinner').classList.add('hidden');

                if (data.success) {
                    // Actualizar lista de registros del día
                    actualizarListaRegistros(data.registrosHoy);

                    document.getElementById('resultado').innerHTML = `
                    <div class="bg-green-50 border border-green-200 rounded-lg p-3
                                text-sm text-green-700 text-center font-semibold">
                        ✅ ${data.message} — ${data.hora}
                    </div>`;

                    // Volver a cámara después de 2s
                    setTimeout(() => repetirFoto(), 2000);
                } else {
                    document.getElementById('btnIngreso').disabled = false;
                    document.getElementById('btnSalida').disabled = false;
                    document.getElementById('resultado').innerHTML = `
                    <div class="bg-red-50 border border-red-200 rounded-lg p-3
                                text-sm text-red-700">
                        ❌ ${data.message}
                    </div>`;
                }
                document.getElementById('resultado').classList.remove('hidden');

            } catch (e) {
                document.getElementById('spinner').classList.add('hidden');
                document.getElementById('btnIngreso').disabled = false;
                document.getElementById('btnSalida').disabled = false;
                document.getElementById('resultado').innerHTML = `
                <div class="bg-red-50 border border-red-200 rounded-lg p-3 text-sm text-red-700">
                    ❌ Error: ${e.message}
                </div>`;
                document.getElementById('resultado').classList.remove('hidden');
            }
        }

        // ── Volver a cámara ──────────────────────────────────────────────
        function repetirFoto() {
            document.getElementById('preview').classList.add('hidden');
            document.getElementById('video').classList.remove('hidden');
            document.getElementById('btnRepetir').classList.add('hidden');
            document.getElementById('btnIngreso').disabled = false;
            document.getElementById('btnSalida').disabled = false;
            document.getElementById('resultado').classList.add('hidden');
            fotoBase64 = null;
        }

        // ── Actualizar lista de registros del día ────────────────────────
        function actualizarListaRegistros(registros) {
            const lista = document.getElementById('listaRegistros');
            if (!registros || registros.length === 0) return;

            lista.innerHTML = registros.map(r => `
            <div class="flex items-center gap-3 text-sm">
                <span class="px-2 py-0.5 rounded text-xs font-bold
                    ${r.tipo === 'INGRESO'
                      ? 'bg-green-100 text-green-700'
                      : 'bg-red-100 text-red-700'}">
                    ${r.tipo}
                </span>
                <span class="text-gray-600 font-mono">${r.hora}</span>
                <img src="${r.foto}"
                     class="w-8 h-8 rounded-full object-cover border border-gray-200">
            </div>`).join('');
        }

        // ── Iniciar todo ─────────────────────────────────────────────────
        iniciarCamara();
        iniciarUbicacion();
    </script>
</x-app-layout>
