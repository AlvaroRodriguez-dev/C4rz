<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            WMS - Salidas Registradas
        </h2>
    </x-slot>

    <div class="py-4 px-3 sm:py-6 sm:px-4">
        <div class="max-w-3xl mx-auto">

            <a href="{{ route('wms.index') }}" class="text-sm text-gray-600 mb-3 inline-flex items-center gap-1">&larr;
                Volver</a>

            {{-- Buscador --}}
            <div class="bg-white shadow rounded-xl p-4 mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Buscar</label>
                <div class="relative">
                    <input type="text" id="buscador" class="w-full border-gray-300 rounded-xl p-3 pl-10 text-base"
                        placeholder="Fecha, producto, cantidad, ubicación, usuario...">
                    <svg class="w-5 h-5 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z" />
                    </svg>
                </div>
                <p id="totalResultados" class="text-xs text-gray-500 mt-2"></p>
            </div>

            {{-- Listado --}}
            <div id="listado" class="space-y-3 mb-4"></div>

            <div id="sinResultados" class="hidden text-center text-gray-500 py-10">
                No se encontraron salidas con ese criterio.
            </div>

            {{-- Paginación --}}
            <div id="paginacion" class="flex items-center justify-between gap-3 mt-4"></div>

        </div>
    </div>

    <script>
        const routeBuscar = "{{ route('wms.salidas.ver.buscar') }}";

        let paginaActual = 1;
        let timeoutBusqueda = null;

        document.addEventListener('DOMContentLoaded', () => {
            cargarListado();

            document.getElementById('buscador').addEventListener('input', function() {
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
                    document.getElementById('totalResultados').textContent = `${data.total} registro(s) encontrado(s)`;

                    if (data.data.length === 0) {
                        sinResultados.classList.remove('hidden');
                        renderPaginacion(data);
                        return;
                    }

                    data.data.forEach(item => {
                        listado.insertAdjacentHTML('beforeend', renderTarjeta(item));
                    });

                    renderPaginacion(data);
                });
        }

        function renderTarjeta(item) {
            const esAjuste = item.tipo_ingreso === 'ajuste';

            const badge = esAjuste ?
                `<span class="inline-block text-[10px] font-semibold text-orange-700 bg-orange-100 rounded-full px-2 py-0.5 mb-1">⚠️ AJUSTE (sin nota)</span>` :
                `<span class="inline-block text-[10px] font-semibold text-gray-500 bg-gray-100 rounded-full px-2 py-0.5 mb-1">NOTA DE SALIDA</span>`;

            return `
                <div class="bg-white border ${esAjuste ? 'border-orange-300' : 'border-gray-200'} rounded-xl p-4 shadow-sm">
                    <div class="flex items-start justify-between gap-3 mb-2">
                        <div class="min-w-0">
                            ${badge}
                            <p class="text-xs text-gray-400">Nota: ${item.rdocum} · ${item.rfecha}</p>
                            <p class="font-mono font-semibold text-gray-800 text-sm mt-0.5">${item.codigo}</p>
                            <p class="text-sm text-gray-600 leading-snug">${item.descripcion}</p>
                            ${item.clote ? `<p class="text-xs text-gray-400 mt-0.5">Lote: ${item.clote}</p>` : ''}
                            ${esAjuste && item.motivo ? `<p class="text-xs text-orange-600 mt-1 italic">Motivo: ${item.motivo}</p>` : ''}
                        </div>
                        <div class="text-right shrink-0">
                            <p class="text-[11px] text-gray-500">Cantidad</p>
                            <p class="text-xl font-bold text-gray-800">${item.cantidad}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-2 text-sm border-t border-gray-100 pt-2 mt-2">
                        <div>
                            <p class="text-[11px] text-gray-400">Pallet</p>
                            <p class="font-mono font-semibold text-blue-700">${item.pallet}</p>
                        </div>
                        <div>
                            <p class="text-[11px] text-gray-400">Ubicación</p>
                            <p class="font-medium text-gray-700">Galpón ${item.galpon} · ${item.ubicacion}</p>
                        </div>
                    </div>

                    <div class="flex items-center justify-between text-xs text-gray-400 mt-2 pt-2 border-t border-gray-100">
                        <span>Registrado por <span class="font-medium text-gray-600">${item.usuario}</span></span>
                        <span>${item.creado}</span>
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
                    window.scrollTo({
                        top: 0,
                        behavior: 'smooth'
                    });
                }
            });

            document.getElementById('btnSiguiente')?.addEventListener('click', () => {
                paginaActual++;
                cargarListado();
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            });
        }
    </script>
</x-app-layout>
