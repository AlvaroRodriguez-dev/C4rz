<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">WMS - Verificación de Producción</h2>
    </x-slot>

    <div class="py-4 px-3 sm:py-6 sm:px-4">
        <div class="max-w-6xl mx-auto">
            <a href="{{ route('wms.index') }}" class="text-sm text-gray-600 mb-3 inline-flex items-center gap-1">&larr; Volver</a>

            <div id="alertBox" class="hidden mb-4 p-3 rounded-lg text-sm"></div>

            <div class="bg-white shadow rounded-xl p-4 sm:p-5 mb-4">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Buscar</label>
                        <input id="search" type="search"
                               placeholder="Documento SAS, documento WMS u origen..."
                               class="w-full border-gray-300 rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
                        <select id="estado" class="w-full border-gray-300 rounded-lg">
                            <option value="">Todos</option>
                            <option value="PENDIENTE_VERIFICACION">Pendiente</option>
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
                                <th class="text-left p-3">Documento SAS</th>
                                <th class="text-left p-3">Fecha</th>
                                <th class="text-right p-3">Declarado</th>
                                <th class="text-right p-3">Físico</th>
                                <th class="text-left p-3">Estado</th>
                                <th class="p-3"></th>
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
        const routeBuscar = "{{ route('wms.produccion.verificacion.buscar') }}";
        const routeDetalle = "{{ url('wms/produccion-verificacion') }}";

        let pagina = 1;

        document.addEventListener('DOMContentLoaded', () => {
            document.getElementById('btnBuscar').addEventListener('click', () => cargar(1));
            document.getElementById('search').addEventListener('keydown', e => {
                if (e.key === 'Enter') cargar(1);
            });
            cargar(1);
        });

        async function cargar(page) {
            pagina = page;
            const params = new URLSearchParams({
                q: document.getElementById('search').value.trim(),
                estado: document.getElementById('estado').value,
                page
            });

            const res = await fetch(routeBuscar + '?' + params.toString());
            const data = await res.json();

            document.getElementById('tabla').innerHTML = data.data.length
                ? data.data.map(fila).join('')
                : '<tr><td colspan="7" class="p-6 text-center text-gray-500">No se encontraron entregas.</td></tr>';

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
        }

        function fila(item) {
            return '<tr class="border-t hover:bg-gray-50">' +
                '<td class="p-3 font-mono">' + (item.documento ?? '—') + '</td>' +
                '<td class="p-3 font-mono">' + item.rdocum_sas + '</td>' +
                '<td class="p-3">' + (item.fecha_entrega ?? '—') + '</td>' +
                '<td class="p-3 text-right">' + item.total_declarado + '</td>' +
                '<td class="p-3 text-right">' + item.total_fisico + '</td>' +
                '<td class="p-3"><span class="px-2 py-1 rounded-full text-xs font-semibold ' + claseEstado(item.estado) + '">' + item.estado + '</span></td>' +
                '<td class="p-3 text-right"><a class="text-blue-600 font-semibold" href="' + routeDetalle + '/' + item.id + '">VER</a></td>' +
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
    </script>
</x-app-layout>
