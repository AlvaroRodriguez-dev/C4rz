<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">WMS - Liberaciones de Producción RG-CB-36</h2>
    </x-slot>

    <div class="py-4 px-3 sm:py-6 sm:px-4">
        <div class="max-w-6xl mx-auto">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-3">
                <a href="{{ route('wms.index') }}" class="text-sm text-gray-600 inline-flex items-center gap-1">&larr; Volver</a>
                <a href="{{ route('wms.produccion.liberacion.create', ['nuevo' => 1]) }}"
                   class="inline-flex items-center justify-center bg-green-600 hover:bg-green-700 text-white font-semibold px-4 py-2.5 rounded-lg">
                    + NUEVO
                </a>
            </div>

            <div id="alertBox" class="hidden mb-4 p-3 rounded-lg text-sm"></div>

            <div class="bg-white shadow rounded-xl p-4 sm:p-5 mb-4">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Buscar</label>
                        <input id="search" type="search"
                               placeholder="Documento WMS u origen..."
                               class="w-full border-gray-300 rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
                        <select id="estado" class="w-full border-gray-300 rounded-lg">
                            <option value="">Todos</option>
                            <option value="PENDIENTE_VERIFICACION">Pendiente de verificación</option>
                            <option value="EN_VERIFICACION">En verificación</option>
                            <option value="CONCILIADA">Conciliada</option>
                            <option value="CON_DIFERENCIA">Con diferencia</option>
                        </select>
                    </div>
                    <div class="flex items-end">
                        <button id="btnBuscar"
                                class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2.5 rounded-lg">
                            BUSCAR
                        </button>
                    </div>
                </div>
            </div>

            <div class="bg-white shadow rounded-xl overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-100 text-gray-600">
                            <tr>
                                <th class="text-left p-3">Documento WMS</th>
                                <th class="text-left p-3">Origen</th>
                                <th class="text-left p-3">Fecha</th>
                                <th class="text-right p-3">Declarado</th>
                                <th class="text-right p-3">Físico</th>
                                <th class="text-left p-3">Estado</th>
                                <th class="text-right p-3">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="tabla"></tbody>
                    </table>
                </div>
                <div id="paginacion" class="p-3 border-t"></div>
            </div>
        </div>
    </div>

    <script>
        const routeBuscar = "{{ route('wms.produccion.liberacion.buscar') }}";
        const routePdf = "{{ url('wms/produccion-liberacion/pdf') }}";
        const routeDetalle = "{{ url('wms/produccion-verificacion') }}";
        const puedeVerificar = @json(auth()->user()->can('wms.produccion.verificar'));

        document.addEventListener('DOMContentLoaded', () => {
            document.getElementById('btnBuscar').addEventListener('click', () => cargar(1));
            document.getElementById('search').addEventListener('keydown', e => {
                if (e.key === 'Enter') cargar(1);
            });
            document.getElementById('estado').addEventListener('change', () => cargar(1));
            cargar(1);
        });

        async function cargar(page) {
            const params = new URLSearchParams({
                q: document.getElementById('search').value.trim(),
                estado: document.getElementById('estado').value,
                page
            });

            try {
                const res = await fetch(routeBuscar + '?' + params.toString(), {
                    headers: { 'Accept': 'application/json' }
                });

                if (!res.ok) throw new Error('No fue posible cargar las liberaciones.');

                const data = await res.json();

                document.getElementById('tabla').innerHTML = data.data.length
                    ? data.data.map(fila).join('')
                    : '<tr><td colspan="7" class="p-6 text-center text-gray-500">No se encontraron liberaciones.</td></tr>';

                document.getElementById('paginacion').innerHTML =
                    data.last_page > 1
                        ? '<div class="flex justify-between items-center"><button class="px-3 py-2 border rounded-lg" ' +
                          (data.current_page <= 1 ? 'disabled' : '') +
                          ' onclick="cargar(' + (data.current_page - 1) + ')">Anterior</button>' +
                          '<span class="text-sm text-gray-500">Página ' + data.current_page + ' de ' + data.last_page + '</span>' +
                          '<button class="px-3 py-2 border rounded-lg" ' +
                          (data.current_page >= data.last_page ? 'disabled' : '') +
                          ' onclick="cargar(' + (data.current_page + 1) + ')">Siguiente</button></div>'
                        : '';
            } catch (error) {
                console.error(error);
                mostrarAlerta(error.message, 'error');
            }
        }

        function fila(item) {
            const pdf = '<a class="inline-flex items-center px-2.5 py-1.5 rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold mr-2" ' +
                'href="' + routePdf + '/' + item.id + '" target="_blank" rel="noopener">PDF</a>';

            const verificar = puedeVerificar
                ? '<a class="text-blue-600 font-semibold" href="' + routeDetalle + '/' + item.id + '">VER</a>'
                : '';

            return '<tr class="border-t hover:bg-gray-50">' +
                '<td class="p-3 font-mono font-semibold">' + (item.documento ?? '—') + '</td>' +
                '<td class="p-3">' + (item.origen ?? '—') + '</td>' +
                '<td class="p-3">' + (item.fecha_entrega ?? '—') + '</td>' +
                '<td class="p-3 text-right">' + item.total_declarado + '</td>' +
                '<td class="p-3 text-right">' + item.total_fisico + '</td>' +
                '<td class="p-3"><span class="px-2 py-1 rounded-full text-xs font-semibold ' + claseEstado(item.estado) + '">' + item.estado + '</span></td>' +
                '<td class="p-3 text-right whitespace-nowrap">' + pdf + verificar + '</td>' +
                '</tr>';
        }

        function claseEstado(estado) {
            return {
                PENDIENTE_VERIFICACION: 'bg-yellow-100 text-yellow-800',
                EN_VERIFICACION: 'bg-blue-100 text-blue-800',
                CONCILIADA: 'bg-green-100 text-green-800',
                CON_DIFERENCIA: 'bg-red-100 text-red-800'
            }[estado] ?? 'bg-gray-100 text-gray-800';
        }

        function mostrarAlerta(mensaje, tipo) {
            const box = document.getElementById('alertBox');
            box.className = 'mb-4 p-3 rounded-lg text-sm ' +
                (tipo === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800');
            box.textContent = mensaje;
            box.classList.remove('hidden');
        }
    </script>
</x-app-layout>
