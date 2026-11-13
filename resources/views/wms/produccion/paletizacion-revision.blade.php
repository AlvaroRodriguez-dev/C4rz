<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">WMS - Revisión de Pallets</h2>
    </x-slot>

    <div class="py-4 px-3 sm:py-6 sm:px-4">
        <div class="max-w-6xl mx-auto">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
                <div>
                    <a href="{{ route('wms.paletizacion.index') }}" class="text-sm text-gray-600">&larr; Volver a paletización</a>
                    <h1 class="text-2xl font-bold text-gray-900 mt-2">Registros paletizados</h1>
                    <p class="text-sm text-gray-500">Consulta los pallets reales generados y revisa el QR de cada HU.</p>
                </div>
            </div>

            <div id="alertBox" class="hidden mb-4 p-3 rounded-lg text-sm"></div>

            <div class="bg-white shadow rounded-xl p-4 mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Buscar entrega</label>
                <input id="search" type="search" class="w-full border-gray-300 rounded-lg" placeholder="Documento, folio u origen...">
            </div>

            <div class="bg-white shadow rounded-xl overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Documento</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Folio</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Origen</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Fecha</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Pallets</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Cajas</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Estado</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody id="resultados" class="divide-y divide-gray-100"></tbody>
                    </table>
                </div>
                <div id="empty" class="hidden p-8 text-center text-sm text-gray-500">No existen entregas paletizadas para revisar.</div>
            </div>
        </div>
    </div>

    <script>
        const routeBuscar = "{{ route('wms.paletizacion.revision.buscar') }}";
        const routeDetalleBase = "{{ url('wms/paletizacion/revision') }}";
        let timer = null;

        document.addEventListener('DOMContentLoaded', cargar);
        document.getElementById('search').addEventListener('input', () => {
            clearTimeout(timer);
            timer = setTimeout(cargar, 300);
        });

        function cargar() {
            const q = document.getElementById('search').value.trim();
            fetch(routeBuscar + '?q=' + encodeURIComponent(q))
                .then(async response => {
                    const data = await response.json();
                    if (!response.ok) throw new Error(data.message || 'No fue posible cargar los registros.');
                    return data;
                })
                .then(data => render(data.data || []))
                .catch(error => mostrarAlerta(error.message));
        }

        function render(items) {
            const tbody = document.getElementById('resultados');
            tbody.innerHTML = '';
            document.getElementById('empty').classList.toggle('hidden', items.length > 0);

            items.forEach(item => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td class="px-4 py-3 font-mono text-sm">${esc(item.documento || '—')}</td>
                    <td class="px-4 py-3 text-sm font-semibold">${esc(item.folio_fisico || '—')}</td>
                    <td class="px-4 py-3 text-sm">${esc(item.origen || '—')}</td>
                    <td class="px-4 py-3 text-sm">${esc(item.fecha_entrega || '—')}</td>
                    <td class="px-4 py-3 text-center font-semibold">${item.pallets}</td>
                    <td class="px-4 py-3 text-center">${item.cajas}</td>
                    <td class="px-4 py-3 text-center"><span class="px-2 py-1 rounded-full text-xs bg-green-100 text-green-700">${esc(item.estado)}</span></td>
                    <td class="px-4 py-3 text-right"><a href="${routeDetalleBase}/${item.id}" class="inline-flex px-3 py-2 rounded-lg bg-gray-900 text-white text-sm font-semibold hover:bg-gray-700">VER PALLETS</a></td>
                `;
                tbody.appendChild(tr);
            });
        }

        function esc(value) {
            return String(value).replace(/[&<>"']/g, char => ({ '&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#039;' })[char]);
        }

        function mostrarAlerta(message) {
            const box = document.getElementById('alertBox');
            box.textContent = message;
            box.className = 'mb-4 p-3 rounded-lg text-sm bg-red-100 text-red-800';
        }
    </script>
</x-app-layout>
