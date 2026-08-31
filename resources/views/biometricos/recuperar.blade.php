<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            📥 Recuperar Datos — Biométricos
        </h2>
    </x-slot>

    <div class="py-8 max-w-3xl mx-auto px-4">
        <div class="bg-white rounded-xl shadow p-6">

            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-1">Biométrico</label>
                <select id="biometrico_id"
                        class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">— Seleccione un biométrico —</option>
                    @foreach($biometricos as $b)
                        <option value="{{ $b->id }}"
                                data-sinc-usuarios="{{ $b->ultima_sinc_usuarios ? $b->ultima_sinc_usuarios->format('d/m/Y H:i:s') : 'Nunca' }}"
                                data-sinc-registros="{{ $b->ultima_sinc_registros ? $b->ultima_sinc_registros->format('d/m/Y H:i:s') : 'Nunca' }}">
                            {{ $b->agencia }} — {{ $b->descripcion }} ({{ $b->ip }})
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Panel de última sincronización --}}
            <div id="panelSinc" class="hidden mb-6 grid grid-cols-2 gap-3">
                <div class="bg-blue-50 border border-blue-200 rounded-lg px-4 py-3 text-sm">
                    <p class="text-blue-500 font-medium mb-0.5">👤 Última sinc. de usuarios</p>
                    <p id="txtSincUsuarios" class="text-blue-800 font-semibold">—</p>
                </div>
                <div class="bg-green-50 border border-green-200 rounded-lg px-4 py-3 text-sm">
                    <p class="text-green-500 font-medium mb-0.5">🕐 Última sinc. de registros</p>
                    <p id="txtSincRegistros" class="text-green-800 font-semibold">—</p>
                </div>
            </div>

            <div class="flex gap-4">
                <button id="btnUsuarios"
                        class="flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-black font-semibold py-2 px-5 rounded-lg transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M17 20h5v-2a4 4 0 00-5-5M9 20H4v-2a4 4 0 015-5m4-4a4 4 0 110-8 4 4 0 010 8z"/>
                    </svg>
                    Recuperar Usuarios
                </button>
                <button id="btnRegistros" class="flex items-center gap-2 bg-green-600 hover:bg-green-700 text-black font-semibold py-2 px-5 rounded-lg transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    Recuperar Registros
                </button>
            </div>

            {{-- Spinner --}}
            <div id="spinner" class="hidden mt-5 flex items-center gap-2 text-gray-500">
                <svg class="animate-spin h-5 w-5 text-indigo-600" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                </svg>
                <span id="spinnerMsg">Conectando al biométrico, por favor espere...</span>
            </div>

            {{-- Resultado --}}
            <div id="resultado" class="hidden mt-5 p-4 rounded-lg text-sm font-medium"></div>
        </div>
    </div>

    <script>
    const token = '{{ csrf_token() }}';

    // ── Mostrar panel de sincronización al seleccionar biométrico ────
    document.getElementById('biometrico_id').addEventListener('change', function () {
        const opt    = this.options[this.selectedIndex];
        const panel  = document.getElementById('panelSinc');
        if (!this.value) { panel.classList.add('hidden'); return; }

        document.getElementById('txtSincUsuarios').textContent  = opt.dataset.sincUsuarios;
        document.getElementById('txtSincRegistros').textContent = opt.dataset.sincRegistros;
        panel.classList.remove('hidden');
        document.getElementById('resultado').classList.add('hidden');
    });

    // ── Acción de recuperación ───────────────────────────────────────
    function llamarEndpoint(url, mensajeSpinner, campoSinc) {
        const bioId = document.getElementById('biometrico_id').value;
        if (!bioId) { alert('Seleccione un biométrico primero.'); return; }

        document.getElementById('spinner').classList.remove('hidden');
        document.getElementById('spinnerMsg').textContent = mensajeSpinner;
        document.getElementById('resultado').classList.add('hidden');
        document.getElementById('btnUsuarios').disabled = true;
        document.getElementById('btnRegistros').disabled = true;

        fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token },
            body: JSON.stringify({ biometrico_id: bioId })
        })
        .then(r => r.json())
        .then(data => {
            const div = document.getElementById('resultado');
            div.classList.remove('hidden', 'bg-green-100', 'text-green-800', 'bg-red-100', 'text-red-800');

            if (data.success) {
                div.classList.add('bg-green-100', 'text-green-800');
                div.innerHTML = `✅ ${data.message}<br>
                    <span class="font-normal">
                        Total en dispositivo: <strong>${data.total}</strong> &nbsp;|&nbsp;
                        Insertados: <strong>${data.insertados}</strong> &nbsp;|&nbsp;
                        ${data.actualizados !== undefined
                            ? 'Actualizados: <strong>' + data.actualizados + '</strong>'
                            : 'Duplicados omitidos: <strong>' + data.duplicados + '</strong>'}
                    </span>`;

                // Actualizar panel de sincronización en tiempo real
                if (data.ultima_sinc) {
                    document.getElementById(campoSinc).textContent = data.ultima_sinc;
                }
            } else {
                div.classList.add('bg-red-100', 'text-red-800');
                div.innerHTML = `❌ ${data.message}`;
            }
        })
        .catch(e => {
            const div = document.getElementById('resultado');
            div.classList.remove('hidden');
            div.classList.add('bg-red-100', 'text-red-800');
            div.innerHTML = `❌ Error de conexión: ${e.message}`;
        })
        .finally(() => {
            document.getElementById('spinner').classList.add('hidden');
            document.getElementById('btnUsuarios').disabled = false;
            document.getElementById('btnRegistros').disabled = false;
        });
    }

    document.getElementById('btnUsuarios').addEventListener('click', () =>
        llamarEndpoint(
            '{{ route("biometricos.recuperar.usuarios") }}',
            'Recuperando usuarios del biométrico...',
            'txtSincUsuarios'
        ));

    document.getElementById('btnRegistros').addEventListener('click', () =>
        llamarEndpoint(
            '{{ route("biometricos.recuperar.registros") }}',
            'Recuperando registros de marcaje (puede tomar unos segundos)...',
            'txtSincRegistros'
        ));
    </script>
</x-app-layout>