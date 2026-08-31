<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Asignación de Agencias por Usuario
        </h2>
    </x-slot>

    <div class="py-8 max-w-5xl mx-auto px-4">
        <div class="bg-white rounded-xl shadow p-6">

            {{-- Selector de usuario --}}
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Selecciona un empleado
                </label>
                <select id="selectUsuario"
                    class="w-full border-gray-300 rounded-lg shadow-sm
                               focus:ring-indigo-500 focus:border-indigo-500 select2-usuario">
                    <option value="">— Seleccione 00 —</option>
                    @foreach ($usuarios as $u)
                        <option value="{{ $u->id }}"
                            data-agencias="{{ $u->rrhhAgencias->pluck('id')->join(',') }}">
                            {{ $u->name }}
                            @if ($u->license)
                                — CI: {{ $u->license }}
                            @else
                                — (sin empleado asignado)
                            @endif
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Agencias disponibles --}}
            <div id="panelAgencias" class="hidden">
                <p class="text-sm font-medium text-gray-700 mb-3">
                    Agencias habilitadas para marcar:
                </p>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3" id="listaAgencias">
                    @foreach ($agencias as $a)
                        <label
                            class="flex items-start gap-3 p-3 border border-gray-200
                                      rounded-lg cursor-pointer hover:bg-indigo-50
                                      hover:border-indigo-300 transition"
                            id="agencia-label-{{ $a->id }}">
                            <input type="checkbox" class="chk-agencia mt-0.5 w-4 h-4 text-indigo-600 rounded"
                                value="{{ $a->id }}">
                            <div>
                                <p class="font-semibold text-gray-800 text-sm">
                                    {{ $a->nombre }}
                                </p>
                                <p class="text-xs text-gray-400">
                                    Código: {{ $a->codigo }} ·
                                    Tolerancia: {{ $a->tolerancia }}m
                                </p>
                                <a href="https://www.google.com/maps?q={{ $a->latitud }},{{ $a->longitud }}"
                                    target="_blank" class="text-xs text-indigo-500 hover:underline">
                                    📍 Ver en mapa
                                </a>
                            </div>
                        </label>
                    @endforeach
                </div>

                {{-- Botón guardar --}}
                <div class="mt-5 flex items-center gap-4">
                    <button id="btnGuardar" onclick="guardarAsignacion()"
                        class="bg-indigo-600 hover:bg-indigo-700 text-white
                                   font-semibold py-2 px-6 rounded-lg transition">
                        💾 Guardar asignación
                    </button>
                    <div id="msgGuardar" class="hidden text-sm font-medium"></div>
                </div>
            </div>

            {{-- Estado vacío --}}
            <div id="panelVacio" class="text-center py-10 text-gray-400 text-sm">
                Selecciona un empleado para gestionar sus agencias asignadas.
            </div>
        </div>
    </div>

    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        const token = '{{ csrf_token() }}';
        const urlGuardar = '{{ route('rrhh.agencias.asignaciones.guardar') }}';

        $(document).ready(function() {
            $('.select2-usuario').select2({
                placeholder: '— Ahora Seleccione un empleado —',
                allowClear: true,
                width: '100%',
            });

            $('#selectUsuario').on('change', function() {
                const userId = $(this).val();
                const opt = this.options[this.selectedIndex];
                const asignadas = opt.dataset.agencias ?
                    opt.dataset.agencias.split(',').map(Number) :
                    [];

                if (!userId) {
                    document.getElementById('panelAgencias').classList.add('hidden');
                    document.getElementById('panelVacio').classList.remove('hidden');
                    return;
                }

                // Marcar checkboxes según agencias asignadas
                document.querySelectorAll('.chk-agencia').forEach(chk => {
                    chk.checked = asignadas.includes(parseInt(chk.value));
                });

                document.getElementById('panelVacio').classList.add('hidden');
                document.getElementById('panelAgencias').classList.remove('hidden');
                document.getElementById('msgGuardar').classList.add('hidden');
            });
        });

        async function guardarAsignacion() {
            const userId = document.getElementById('selectUsuario').value;
            if (!userId) return;

            const agenciaIds = Array.from(
                document.querySelectorAll('.chk-agencia:checked')
            ).map(c => parseInt(c.value));

            const btn = document.getElementById('btnGuardar');
            const msg = document.getElementById('msgGuardar');

            btn.disabled = true;
            btn.textContent = '⏳ Guardando...';
            msg.classList.add('hidden');

            try {
                const resp = await fetch(urlGuardar, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': token,
                    },
                    body: JSON.stringify({
                        user_id: userId,
                        agencia_ids: agenciaIds
                    }),
                });
                const data = await resp.json();

                msg.classList.remove('hidden', 'text-green-600', 'text-red-500');
                if (data.success) {
                    msg.classList.add('text-green-600');
                    msg.textContent = `✅ ${data.message} (${data.total} agencias)`;
                } else {
                    msg.classList.add('text-red-500');
                    msg.textContent = '❌ ' + (data.message ?? 'Error al guardar.');
                }
            } catch (e) {
                msg.classList.remove('hidden');
                msg.classList.add('text-red-500');
                msg.textContent = '❌ Error: ' + e.message;
            } finally {
                btn.disabled = false;
                btn.textContent = '💾 Guardar asignación';
            }
        }
    </script>
</x-app-layout>
