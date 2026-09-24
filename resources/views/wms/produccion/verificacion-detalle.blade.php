<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">WMS - Verificar Entrega de Producción</h2>
    </x-slot>

    <div class="py-4 px-3 sm:py-6 sm:px-4">
        <div class="max-w-5xl mx-auto">
            <a href="{{ route('wms.produccion.verificacion.index') }}" class="text-sm text-gray-600 mb-3 inline-flex items-center gap-1">&larr; Volver a entregas</a>

            <div id="alertBox" class="hidden mb-4 p-3 rounded-lg text-sm"></div>

            <div class="bg-white shadow rounded-xl p-4 sm:p-5 mb-4">
                <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
                    <div>
                        <p class="text-xs text-gray-500 uppercase">Documento WMS</p>
                        <p class="text-xl font-bold font-mono">{{ $entrega->documento?->id_documento ?? '—' }}</p>
                        <p class="text-sm text-gray-500 mt-1">SAS: <span class="font-mono">{{ $entrega->rdocum_sas }}</span></p>
                    </div>
                    <div class="text-left sm:text-right">
                        <span id="estado" class="inline-block px-3 py-1 rounded-full text-xs font-bold">{{ $entrega->estado }}</span>
                        <p class="text-sm mt-2">Declarado: <strong id="totalDeclarado">{{ $entrega->total_declarado }}</strong></p>
                        <p class="text-sm">Físico: <strong id="totalFisico">{{ $entrega->total_fisico }}</strong></p>
                    </div>
                </div>

                <div class="mt-4 flex flex-col sm:flex-row gap-2">
                    @if($entrega->estado === 'PENDIENTE_VERIFICACION')
                        <button id="btnIniciar" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-4 py-3 rounded-lg">
                            INICIAR VERIFICACIÓN
                        </button>
                    @endif
                    @if($entrega->estado === 'EN_VERIFICACION')
                        <button id="btnConciliar" class="bg-green-600 hover:bg-green-700 text-white font-semibold px-4 py-3 rounded-lg">
                            CONCILIAR ENTREGA
                        </button>
                    @endif
                </div>
            </div>

            <div class="space-y-3">
                @foreach($entrega->detalles as $detalle)
                    <div class="bg-white shadow rounded-xl p-4"
                         data-detalle="{{ $detalle->id }}"
                         data-declarada="{{ $detalle->cantidad_declarada }}">
                        <div class="grid grid-cols-1 lg:grid-cols-12 gap-3 items-center">
                            <div class="lg:col-span-4 min-w-0">
                                <p class="font-mono font-semibold text-gray-800">{{ $detalle->codigo }}</p>
                                <p class="text-sm text-gray-600">{{ $detalle->descripcion }}</p>
                                <p class="text-xs text-gray-500 mt-1">
                                    Lote: <span class="font-mono">{{ $detalle->lote }}</span>
                                    · Calidad: {{ $detalle->calidad ?? '—' }}
                                </p>
                            </div>
                            <div class="lg:col-span-2">
                                <p class="text-xs text-gray-500">DECLARADA</p>
                                <p class="text-lg font-bold">{{ $detalle->cantidad_declarada }}</p>
                            </div>
                            <div class="lg:col-span-2">
                                <label class="text-xs text-gray-500 block">FÍSICA</label>
                                <input type="number" min="0"
                                       value="{{ $detalle->cantidad_fisica }}"
                                       class="cantidad-fisica w-full border-gray-300 rounded-lg text-lg font-semibold"
                                       {{ $entrega->estado === 'EN_VERIFICACION' && in_array($detalle->estado, ['PENDIENTE'], true) ? '' : 'disabled' }}>
                            </div>
                            <div class="lg:col-span-2">
                                <p class="text-xs text-gray-500">DIFERENCIA</p>
                                <p class="diferencia text-lg font-bold">—</p>
                            </div>
                            <div class="lg:col-span-2 min-w-0">
                                <span class="estado-detalle inline-flex max-w-full items-center justify-center px-2 py-1 rounded-full text-xs font-semibold whitespace-nowrap overflow-hidden text-ellipsis {{ $detalle->estado === 'CON_DIFERENCIA' ? 'bg-red-100 text-red-700' : ($detalle->estado === 'CONCILIADO' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700') }}">
                                    {{ $detalle->estado }}
                                </span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <script>
        const routeIniciar = "{{ url('wms/produccion-verificacion') }}/{{ $entrega->id }}/iniciar";
        const routeConciliar = "{{ url('wms/produccion-verificacion') }}/{{ $entrega->id }}/conciliar";
        const csrf = "{{ csrf_token() }}";

        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('[data-detalle]').forEach(configurarDetalle);

            const btnIniciar = document.getElementById('btnIniciar');
            if (btnIniciar) btnIniciar.addEventListener('click', iniciar);

            const btnConciliar = document.getElementById('btnConciliar');
            if (btnConciliar) btnConciliar.addEventListener('click', conciliar);
        });

        function configurarDetalle(card) {
            const input = card.querySelector('.cantidad-fisica');
            const declarada = Number(card.dataset.declarada);
            const diferencia = card.querySelector('.diferencia');

            const pintar = () => {
                if (input.value === '') {
                    diferencia.textContent = '—';
                    return;
                }
                const valor = Number(input.value);
                const diff = valor - declarada;
                diferencia.textContent = (diff > 0 ? '+' : '') + diff;
                diferencia.className = 'diferencia text-lg font-bold ' + (diff === 0 ? 'text-green-600' : 'text-red-600');
            };

            input.addEventListener('input', pintar);
            pintar();

            input.addEventListener('change', async () => {
                if (input.disabled || input.value === '') return;

                try {
                    const res = await fetch("{{ url('wms/produccion-verificacion/detalle') }}/" + card.dataset.detalle + "/cantidad", {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrf,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ cantidad_fisica: Number(input.value) })
                    });

                    const data = await res.json();
                    if (!res.ok) throw new Error(data.message || 'No fue posible guardar la cantidad.');

                    const estadoBadge = card.querySelector('.estado-detalle');
                    estadoBadge.textContent = data.detalle.estado;
                    estadoBadge.className = 'estado-detalle inline-flex max-w-full items-center justify-center px-2 py-1 rounded-full text-xs font-semibold whitespace-nowrap overflow-hidden text-ellipsis ' + claseEstadoDetalle(data.detalle.estado);
                } catch (e) {
                    mostrarAlerta(e.message, 'error');
                }
            });
        }

        async function iniciar() {
            const res = await fetch(routeIniciar, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }
            });
            const data = await res.json();

            if (!res.ok) return mostrarAlerta(data.message || 'No fue posible iniciar la verificación.', 'error');

            location.reload();
        }

        async function conciliar() {
            if (!confirm('¿Confirmas la conciliación de esta entrega? Después de conciliar no se podrán modificar sus cantidades.')) return;

            const res = await fetch(routeConciliar, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }
            });
            const data = await res.json();

            if (!res.ok) return mostrarAlerta(data.message || 'No fue posible conciliar.', 'error');

            mostrarAlerta(data.message, data.entrega.estado === 'CONCILIADA' ? 'success' : 'warning');
            setTimeout(() => location.reload(), 900);
        }

        function claseEstadoDetalle(estado) {
            return {
                CON_DIFERENCIA: 'bg-red-100 text-red-700',
                CONCILIADO: 'bg-green-100 text-green-700',
                PENDIENTE: 'bg-gray-100 text-gray-700'
            }[estado] ?? 'bg-gray-100 text-gray-700';
        }

        function mostrarAlerta(mensaje, tipo) {
            const box = document.getElementById('alertBox');
            box.className = 'mb-4 p-3 rounded-lg text-sm ' +
                (tipo === 'success' ? 'bg-green-100 text-green-800' :
                 tipo === 'warning' ? 'bg-yellow-100 text-yellow-800' :
                 'bg-red-100 text-red-800');
            box.textContent = mensaje;
            box.classList.remove('hidden');
        }
    </script>
</x-app-layout>
