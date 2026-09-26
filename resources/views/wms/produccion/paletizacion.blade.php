<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">WMS - Paletización de Producción</h2>
    </x-slot>

    <div class="py-4 px-3 sm:py-6 sm:px-4">
        <div class="max-w-6xl mx-auto">
            <a href="{{ route('wms.index') }}" class="text-sm text-gray-600 inline-flex items-center gap-1 mb-3">&larr; Volver</a>

            <div id="alertBox" class="hidden mb-4 p-3 rounded-lg text-sm"></div>

            <div class="bg-white shadow rounded-xl p-4 sm:p-5 mb-4">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Buscar</label>
                        <input id="search" type="search" placeholder="Documento WMS, folio u origen..." class="w-full border-gray-300 rounded-lg">
                    </div>
                    <div class="sm:col-span-2 flex items-end">
                        <button id="btnBuscar" class="w-full sm:w-auto bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2.5 px-6 rounded-lg">BUSCAR</button>
                    </div>
                </div>
            </div>

            <div class="bg-white shadow rounded-xl overflow-hidden">
                <div class="px-4 py-3 border-b bg-gray-50">
                    <p class="text-sm text-gray-600">Aquí aparecen las entregas <strong>CONCILIADAS</strong> del almacén operativo pendientes de paletización.</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-100 text-gray-600">
                            <tr>
                                <th class="text-left p-3">Documento WMS</th>
                                <th class="text-left p-3">Folio RG-CB-36</th>
                                <th class="text-left p-3">Fecha</th>
                                <th class="text-right p-3">Físico</th>
                                <th class="text-right p-3">Paletizado</th>
                                <th class="text-right p-3">Pendiente</th>
                                <th class="text-left p-3">Estado</th>
                                <th class="text-right p-3">Acción</th>
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
        const routeBuscar = "{{ route('wms.produccion.paletizacion.buscar') }}";

        document.addEventListener('DOMContentLoaded', () => {
            document.getElementById('btnBuscar').addEventListener('click', () => cargar(1));
            document.getElementById('search').addEventListener('keydown', e => {
                if (e.key === 'Enter') cargar(1);
            });
            cargar(1);
        });

        async function cargar(page) {
            const params = new URLSearchParams({ q: document.getElementById('search').value.trim(), page });

            try {
                const res = await fetch(routeBuscar + '?' + params.toString(), {
                    headers: { 'Accept': 'application/json' }
                });
                if (!res.ok) throw new Error('No fue posible cargar las entregas pendientes de paletización.');

                const data = await res.json();
                document.getElementById('tabla').innerHTML = data.data.length
                    ? data.data.map(fila).join('')
                    : '<tr><td colspan="8" class="p-6 text-center text-gray-500">No hay entregas conciliadas pendientes de paletización.</td></tr>';

                document.getElementById('paginacion').innerHTML = data.last_page > 1
                    ? '<div class="flex justify-between items-center"><button class="px-3 py-2 border rounded-lg disabled:opacity-50" ' +
                      (data.current_page <= 1 ? 'disabled' : '') +
                      ' onclick="cargar(' + (data.current_page - 1) + ')">Anterior</button>' +
                      '<span class="text-sm text-gray-500">Página ' + data.current_page + ' de ' + data.last_page + '</span>' +
                      '<button class="px-3 py-2 border rounded-lg disabled:opacity-50" ' +
                      (data.current_page >= data.last_page ? 'disabled' : '') +
                      ' onclick="cargar(' + (data.current_page + 1) + ')">Siguiente</button></div>'
                    : '';
            } catch (error) {
                console.error(error);
                mostrarAlerta(error.message);
            }
        }

        function fila(item) {
            const accion = item.pendiente > 0
                ? '<span class="inline-flex items-center px-3 py-1.5 rounded-lg bg-gray-200 text-gray-600 text-xs font-semibold" title="La construcción del pallet se habilitará en el siguiente bloque">PALETIZAR</span>'
                : '<span class="text-xs text-gray-500">COMPLETO</span>';

            return '<tr class="border-t hover:bg-gray-50">' +
                '<td class="p-3 font-mono font-semibold whitespace-nowrap">' + (item.documento ?? '—') + '</td>' +
                '<td class="p-3">' + (item.folio_fisico ?? '—') + '</td>' +
                '<td class="p-3 whitespace-nowrap">' + (item.fecha_entrega ?? '—') + '</td>' +
                '<td class="p-3 text-right">' + formatearNumero(item.total_fisico) + '</td>' +
                '<td class="p-3 text-right">' + formatearNumero(item.total_paletizado) + '</td>' +
                '<td class="p-3 text-right font-semibold">' + formatearNumero(item.pendiente) + '</td>' +
                '<td class="p-3"><span class="px-2 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800">CONCILIADA</span></td>' +
                '<td class="p-3 text-right">' + accion + '</td>' +
                '</tr>';
        }

        function formatearNumero(valor) {
            return new Intl.NumberFormat('es-BO').format(Number(valor || 0));
        }

        function mostrarAlerta(mensaje) {
            const box = document.getElementById('alertBox');
            box.className = 'mb-4 p-3 rounded-lg text-sm bg-red-100 text-red-800';
            box.textContent = mensaje;
            box.classList.remove('hidden');
        }
    </script>
</x-app-layout>
