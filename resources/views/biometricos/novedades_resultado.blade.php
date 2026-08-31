<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between flex-wrap gap-2">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                📋 Novedades — {{ $usuario->name }} [{{ $usuario->user_id }}]
            </h2>
            <a href="{{ route('biometricos.novedades') }}"
               class="text-sm bg-gray-200 hover:bg-gray-300 text-gray-700 py-1 px-4 rounded-lg">
                ← Nueva consulta
            </a>
        </div>
    </x-slot>

    <div class="py-8 max-w-6xl mx-auto px-4">

        {{-- Encabezado del período --}}
        <div class="bg-white rounded-xl shadow mb-4 px-6 py-3 text-sm text-gray-600 flex flex-wrap gap-6">
            <span>📅 Período:
                <strong>
                    {{ \Carbon\Carbon::createFromDate($anio, $mes, 1)->translatedFormat('F Y') }}
                </strong>
            </span>
            <span>👤 Usuario: <strong>{{ $usuario->name }}</strong></span>
            <span>🏢 Lugar: <strong>{{ $usuario->biometrico->agencia ?? 'Todos' }}</strong></span>
            <span>📊 Total marcajes:
                <strong>{{ collect($dias)->sum('total') }}</strong>
            </span>
        </div>

        {{-- Tabla de novedades --}}
        <div class="bg-white rounded-xl shadow overflow-hidden">
            <table class="min-w-full text-sm">
                <thead class="bg-indigo-600 text-white">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold w-28">Día</th>
                        <th class="px-4 py-3 text-left font-semibold w-32">Lugar</th>
                        <th class="px-4 py-3 text-left font-semibold">Registros</th>
                        <th class="px-4 py-3 text-center font-semibold w-14">Total</th>
                        <th class="px-4 py-3 text-center font-semibold w-44">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($dias as $d)
                        <tr class="{{ $d['es_fin_semana'] ? 'bg-gray-50' : 'bg-white' }}
                                   hover:bg-indigo-50 transition">

                            {{-- Día --}}
                            <td class="px-4 py-2 whitespace-nowrap font-medium
                                {{ $d['es_fin_semana'] ? 'text-gray-400' : 'text-gray-800' }}">
                                {{ $d['dia_semana'] }} {{ str_pad($d['dia'], 2, '0', STR_PAD_LEFT) }}
                            </td>

                            {{-- Lugar de Registro --}}
                            <td class="px-4 py-2 text-gray-600 text-xs">
                                @if(count($d['marcajes']) > 0)
                                    @foreach(collect($d['marcajes'])->pluck('biometrico')->unique() as $lugar)
                                        <span class="block">{{ $lugar }}</span>
                                    @endforeach
                                @else
                                    <span class="text-gray-300">—</span>
                                @endif
                            </td>

                            {{-- Registros --}}
                            <td class="px-4 py-2">
                                <div class="flex flex-wrap gap-1">
                                    @forelse($d['marcajes'] as $m)
                                        <span class="bg-indigo-100 text-indigo-700 text-xs
                                                     font-mono px-2 py-0.5 rounded">
                                            🕐 {{ $m['hora'] }}
                                        </span>
                                    @empty
                                        <span class="text-gray-300 text-xs">Sin registros</span>
                                    @endforelse
                                </div>
                            </td>

                            {{-- Total --}}
                            <td class="px-4 py-2 text-center">
                                @if($d['total'] > 0)
                                    <span class="inline-flex items-center justify-center w-7 h-7
                                          rounded-full bg-indigo-100 text-indigo-700 text-xs font-bold">
                                        {{ $d['total'] }}
                                    </span>
                                @else
                                    <span class="text-gray-300 text-xs">0</span>
                                @endif
                            </td>

                            {{-- Acciones --}}
                            <td class="px-4 py-2 text-center" id="accion-{{ $d['dia'] }}">
                                @if($d['ticket_id'])
                                    {{-- Ya tiene boleta — mostrar como enlace al modal --}}
                                    <button
                                        onclick="verBoleta('{{ $d['ticket_id'] }}')"
                                        class="inline-flex items-center gap-1 bg-green-100
                                               hover:bg-green-200 text-green-700 text-xs
                                               font-semibold px-3 py-1 rounded-full transition">
                                        ✅ {{ $d['ticket_id'] }}
                                    </button>
                                @else
                                    {{-- Botón buscar boleta --}}
                                    <button
                                        onclick="buscarBoleta(
                                            '{{ $usuario->user_id }}',
                                            '{{ $d['fecha'] }}',
                                            '{{ $request->biometrico_id ?? '' }}',
                                            {{ $d['dia'] }}
                                        )"
                                        class="bg-blue-500 hover:bg-blue-600 text-white text-xs
                                               font-semibold px-3 py-1 rounded-lg transition"
                                        id="btn-{{ $d['dia'] }}">
                                        🔍 Buscar Boleta
                                    </button>
                                    <div id="resultado-{{ $d['dia'] }}" class="mt-1"></div>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            <a href="{{ route('biometricos.novedades') }}"
               class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold py-2 px-5 rounded-lg">
                Nueva consulta
            </a>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════ --}}
    {{-- MODAL DETALLE BOLETA                                          --}}
    {{-- ══════════════════════════════════════════════════════════════ --}}
    <div id="modalBoleta"
         class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black bg-opacity-50 p-4">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">

            {{-- Header modal --}}
            <div class="flex items-center justify-between px-6 py-4 border-b bg-indigo-600 rounded-t-xl">
                <h3 class="text-white font-semibold text-lg">📄 Detalle de Boleta</h3>
                <button onclick="cerrarModal()"
                        class="text-white hover:text-indigo-200 text-2xl font-bold leading-none">
                    &times;
                </button>
            </div>

            {{-- Contenido del modal --}}
            <div id="modalContenido" class="px-6 py-5">
                {{-- Spinner de carga --}}
                <div id="modalSpinner" class="flex items-center justify-center py-12">
                    <svg class="animate-spin h-8 w-8 text-indigo-600" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10"
                                stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor"
                              d="M4 12a8 8 0 018-8v8H4z"/>
                    </svg>
                    <span class="ml-3 text-gray-500">Cargando boleta...</span>
                </div>
                {{-- Datos dinámicos --}}
                <div id="modalDatos" class="hidden"></div>
            </div>
        </div>
    </div>

    <script>
    const token       = '{{ csrf_token() }}';
    const urlBuscar   = '{{ route("biometricos.novedades.buscar-boleta") }}';
    const urlVerBoleta= '{{ route("biometricos.novedades.ver-boleta") }}';

    // ── Buscar boleta ────────────────────────────────────────────────
    async function buscarBoleta(userId, fecha, biometricoId, dia) {
        const btn = document.getElementById('btn-' + dia);
        const div = document.getElementById('resultado-' + dia);

        btn.disabled = true;
        btn.textContent = '⏳ Buscando...';
        btn.classList.add('opacity-60');
        div.innerHTML = '';

        try {
            const resp = await fetch(urlBuscar, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token },
                body: JSON.stringify({ user_id: userId, fecha, biometrico_id: biometricoId || null }),
            });
            const data = await resp.json();

            if (data.success) {
                // Reemplazar celda completa con enlace al modal
                document.getElementById('accion-' + dia).innerHTML = `
                    <button onclick="verBoleta('${data.ticket_id}')"
                            class="inline-flex items-center gap-1 bg-green-100 hover:bg-green-200
                                   text-green-700 text-xs font-semibold px-3 py-1 rounded-full transition">
                        ✅ ${data.ticket_id}
                    </button>`;
            } else {
                btn.disabled = false;
                btn.textContent = '🔍 Buscar Boleta';
                btn.classList.remove('opacity-60');
                div.innerHTML = `<span class="text-red-500 text-xs">❌ ${data.message}</span>`;
            }
        } catch (e) {
            btn.disabled = false;
            btn.textContent = '🔍 Buscar Boleta';
            btn.classList.remove('opacity-60');
            div.innerHTML = `<span class="text-red-500 text-xs">❌ Error: ${e.message}</span>`;
        }
    }

    // ── Ver boleta en modal ──────────────────────────────────────────
    async function verBoleta(ticketId) {
        // Mostrar modal con spinner
        document.getElementById('modalBoleta').classList.remove('hidden');
        document.getElementById('modalSpinner').classList.remove('hidden');
        document.getElementById('modalDatos').classList.add('hidden');
        document.getElementById('modalDatos').innerHTML = '';

        try {
            const resp = await fetch(urlVerBoleta, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token },
                body: JSON.stringify({ ticket_id: ticketId }),
            });
            const data = await resp.json();

            document.getElementById('modalSpinner').classList.add('hidden');

            if (!data.success) {
                document.getElementById('modalDatos').innerHTML = `
                    <div class="text-center py-8 text-red-500">
                        ❌ ${data.message}
                    </div>`;
                document.getElementById('modalDatos').classList.remove('hidden');
                return;
            }

            const b  = data.boleta;
            const p  = data.personal;
            const s  = data.seccion;
            const d  = data.detalle;

            // ── Construir HTML del detalle ───────────────────────────
            let htmlDetalle = '';
            if (d && d.length > 0) {
                d.forEach((item, i) => {
                    htmlDetalle += `
                        <div class="border border-gray-200 rounded-lg p-4 ${i > 0 ? 'mt-3' : ''}">
                            <p class="text-xs font-semibold text-gray-500 mb-2">
                                Período ${i + 1}
                            </p>
                            <div class="grid grid-cols-2 gap-x-6 gap-y-1 text-sm">
                                <div class="flex justify-between border-b pb-1">
                                    <span class="text-gray-500">Fecha inicio:</span>
                                    <span class="font-medium">${item.DATEI ?? '—'}</span>
                                </div>
                                <div class="flex justify-between border-b pb-1">
                                    <span class="text-gray-500">Fecha fin:</span>
                                    <span class="font-medium">${item.DATEF ?? '—'}</span>
                                </div>
                                <div class="flex justify-between border-b pb-1">
                                    <span class="text-gray-500">Hora inicio 1:</span>
                                    <span class="font-medium">${item.HOURI1 ?? '—'}</span>
                                </div>
                                <div class="flex justify-between border-b pb-1">
                                    <span class="text-gray-500">Hora fin 1:</span>
                                    <span class="font-medium">${item.HOURF1 ?? '—'}</span>
                                </div>
                                <div class="flex justify-between border-b pb-1">
                                    <span class="text-gray-500">Hora inicio 2:</span>
                                    <span class="font-medium">${item.HOURI2 ?? '—'}</span>
                                </div>
                                <div class="flex justify-between border-b pb-1">
                                    <span class="text-gray-500">Hora fin 2:</span>
                                    <span class="font-medium">${item.HOURF2 ?? '—'}</span>
                                </div>
                                <div class="flex justify-between border-b pb-1">
                                    <span class="text-gray-500">Total horas:</span>
                                    <span class="font-medium">${item.TOTALH ?? '—'}</span>
                                </div>
                                <div class="flex justify-between border-b pb-1">
                                    <span class="text-gray-500">Total días:</span>
                                    <span class="font-medium">${item.TOTALD ?? '—'}</span>
                                </div>
                            </div>
                        </div>`;
                });
            } else {
                htmlDetalle = `<p class="text-gray-400 text-sm text-center py-4">
                                Sin detalle de períodos.</p>`;
            }

            document.getElementById('modalDatos').innerHTML = `
                {{-- Nro. Boleta --}}
                <div class="flex items-center justify-between mb-5">
                    <div>
                        <span class="text-xs text-gray-400 uppercase tracking-wide">Nro. Boleta</span>
                        <p class="text-2xl font-bold text-indigo-700">${b.ID}</p>
                    </div>
                    <span class="px-3 py-1 rounded-full text-xs font-semibold
                          ${b.STATE === 'A' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600'}">
                        ${b.STATE === 'A' ? '✅ Aprobado' : b.STATE ?? '—'}
                    </span>
                </div>

                {{-- Datos del trabajador --}}
                <div class="bg-gray-50 rounded-lg p-4 mb-4">
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">
                        Datos del Trabajador
                    </p>
                    <div class="grid grid-cols-2 gap-x-6 gap-y-2 text-sm">
                        <div>
                            <span class="text-gray-400 text-xs">Nombre completo</span>
                            <p class="font-semibold text-gray-800">
                                ${p ? (p.NAME + ' ' + p.LASTNAME) : '—'}
                            </p>
                        </div>
                        <div>
                            <span class="text-gray-400 text-xs">C.I.</span>
                            <p class="font-semibold text-gray-800">
                                ${p ? (p.LICENSE + (p.CICIUDAD ? ' ' + p.CICIUDAD : '')) : '—'}
                            </p>
                        </div>
                        <div>
                            <span class="text-gray-400 text-xs">Sección</span>
                            <p class="font-semibold text-gray-800">
                                ${s ? s.DESCRIPTION : '—'}
                            </p>
                        </div>
                        <div>
                            <span class="text-gray-400 text-xs">Fecha de boleta</span>
                            <p class="font-semibold text-gray-800">${b.DATE ?? '—'}</p>
                        </div>
                    </div>
                </div>

                {{-- Observación --}}
                ${b.OBSERVATION ? `
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3 mb-4 text-sm">
                    <p class="text-xs font-semibold text-yellow-600 mb-1">📝 Observación</p>
                    <p class="text-gray-700">${b.OBSERVATION}</p>
                </div>` : ''}

                {{-- Detalle de períodos --}}
                <div>
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">
                        Detalle de Períodos
                    </p>
                    ${htmlDetalle}
                </div>`;

            document.getElementById('modalDatos').classList.remove('hidden');

        } catch (e) {
            document.getElementById('modalSpinner').classList.add('hidden');
            document.getElementById('modalDatos').innerHTML = `
                <div class="text-center py-8 text-red-500">
                    ❌ Error al cargar la boleta: ${e.message}
                </div>`;
            document.getElementById('modalDatos').classList.remove('hidden');
        }
    }

    // ── Cerrar modal ─────────────────────────────────────────────────
    function cerrarModal() {
        document.getElementById('modalBoleta').classList.add('hidden');
        document.getElementById('modalDatos').innerHTML = '';
    }

    // Cerrar modal al hacer clic fuera
    document.getElementById('modalBoleta').addEventListener('click', function (e) {
        if (e.target === this) cerrarModal();
    });

    // Cerrar modal con ESC
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') cerrarModal();
    });
    </script>
</x-app-layout>