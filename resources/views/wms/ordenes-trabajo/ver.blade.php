<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">WMS - Órdenes de Trabajo (Ver)</h2>
    </x-slot>

    <div class="py-4 px-3 sm:py-6 sm:px-4">
        <div class="max-w-3xl mx-auto">
            <a href="{{ route('wms.index') }}" class="text-sm text-gray-600 mb-3 inline-flex items-center gap-1">&larr; Volver</a>

            <div class="bg-white shadow rounded-xl p-4 mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Buscar</label>
                <div class="relative">
                    <input type="text" id="buscador"
                        class="w-full border-gray-300 rounded-xl p-3 pl-10 text-base"
                        placeholder="N° de OT, N° de nota, glosa o fecha...">
                    <svg class="w-5 h-5 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z" />
                    </svg>
                </div>
                <p id="totalResultados" class="text-xs text-gray-500 mt-2"></p>
            </div>

            <div id="listado" class="space-y-3 mb-4"></div>

            <div id="sinResultados" class="hidden text-center text-gray-500 py-10">
                No se encontraron Órdenes de Trabajo con ese criterio.
            </div>

            <div id="paginacion" class="flex items-center justify-between gap-3 mt-4"></div>
        </div>
    </div>

    <script>
        const routeBuscar = "{{ route('wms.ordenes.trabajo.ver.buscar') }}";

        let paginaActual = 1;
        let timeoutBusqueda = null;

        document.addEventListener('DOMContentLoaded', () => {
            cargarListado();

            document.getElementById('buscador').addEventListener('input', function () {
                clearTimeout(timeoutBusqueda);
                timeoutBusqueda = setTimeout(() => {
                    paginaActual = 1;
                    cargarListado();
                }, 350);
            });
        });

        function cargarListado() {
            const q = document.getElementById('buscador').value.trim();
            const listado = document.getElementById('listado');
            const sinResultados = document.getElementById('sinResultados');

            listado.innerHTML = '<p class="text-center text-gray-400 py-6 text-sm">Cargando...</p>';
            sinResultados.classList.add('hidden');

            fetch(`${routeBuscar}?q=${encodeURIComponent(q)}&page=${paginaActual}`)
                .then(res => res.json())
                .then(data => {
                    listado.innerHTML = '';
                    document.getElementById('totalResultados').textContent = `${data.total} Orden(es) de Trabajo`;

                    if (data.data.length === 0) {
                        sinResultados.classList.remove('hidden');
                        renderPaginacion(data);
                        return;
                    }

                    data.data.forEach(ot => {
                        listado.insertAdjacentHTML('beforeend', renderTarjeta(ot));
                    });

                    renderPaginacion(data);
                });
        }

        function renderTarjeta(ot) {
            const esCompletada = ot.estado === 'completada';
            const badge = esCompletada
                ? '<span class="text-[11px] font-semibold text-green-700 bg-green-100 rounded-full px-2 py-0.5">COMPLETADA</span>'
                : '<span class="text-[11px] font-semibold text-orange-700 bg-orange-100 rounded-full px-2 py-0.5">PENDIENTE</span>';

            return `
                <div class="bg-white border border-gray-200 rounded-xl p-4 shadow-sm">
                    <div class="flex justify-between items-start gap-3">
                        <div class="min-w-0">
                            <div class="flex items-center gap-2 mb-1">
                                <p class="text-sm font-semibold text-gray-800">OT #${ot.id}</p>
                                ${badge}
                            </div>
                            <p class="text-xs text-gray-500">Nota Tipo ${ot.tipo_registro} #${ot.id_registro}</p>
                            ${ot.glosa ? `<p class="text-xs text-gray-400">${ot.glosa}</p>` : ''}
                            <p class="text-xs text-gray-400 mt-1">
                                Generada: ${ot.creado}${ot.completada ? ` · Completada: ${ot.completada}` : ''}
                            </p>
                            <p class="text-xs text-gray-500 mt-1">
                                Verificados: <span class="font-semibold text-gray-700">${ot.lineas_chequeadas}/${ot.total_lineas}</span> pallets
                            </p>
                        </div>
                        <a href="${ot.ticket_url}" target="_blank"
                           class="shrink-0 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-lg px-3 py-2 flex items-center gap-1">
                            📄 PDF
                        </a>
                    </div>
                </div>
            `;
        }

        function renderPaginacion(data) {
            const cont = document.getElementById('paginacion');

            if (data.last_page <= 1) {
                cont.innerHTML = '';
                return;
            }

            cont.innerHTML = `
                <button id="btnAnterior"
                    class="flex-1 py-3 rounded-xl border border-gray-300 font-medium text-sm ${data.current_page <= 1 ? 'opacity-40 pointer-events-none' : 'bg-white'}">
                    &larr; Anterior
                </button>
                <span class="text-sm text-gray-500 whitespace-nowrap">${data.current_page} / ${data.last_page}</span>
                <button id="btnSiguiente"
                    class="flex-1 py-3 rounded-xl border border-gray-300 font-medium text-sm ${data.current_page >= data.last_page ? 'opacity-40 pointer-events-none' : 'bg-white'}">
                    Siguiente &rarr;
                </button>
            `;

            document.getElementById('btnAnterior')?.addEventListener('click', () => {
                if (paginaActual > 1) {
                    paginaActual--;
                    cargarListado();
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                }
            });

            document.getElementById('btnSiguiente')?.addEventListener('click', () => {
                paginaActual++;
                cargarListado();
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });
        }
    </script>
</x-app-layout>