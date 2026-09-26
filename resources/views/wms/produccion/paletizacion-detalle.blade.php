<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">WMS - Paletización de Producción</h2>
    </x-slot>

    <div class="py-4 px-3 sm:py-6 sm:px-4">
        <div class="max-w-6xl mx-auto">
            <div class="flex items-center justify-between gap-3 mb-4">
                <a href="{{ route('wms.paletizacion.index') }}" class="text-sm text-gray-600">&larr; Volver</a>
                <span class="px-3 py-1.5 rounded-full text-xs font-semibold bg-green-100 text-green-800">CONCILIADA</span>
            </div>

            <div id="alertBox" class="hidden mb-4 p-3 rounded-lg text-sm"></div>

            <div class="bg-white shadow rounded-xl p-4 sm:p-5 mb-4">
                <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                    <div>
                        <div class="text-xs text-gray-500">Documento WMS</div>
                        <div class="font-mono font-semibold">{{ $entrega->documento?->id_documento ?? '—' }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-gray-500">Almacén operativo</div>
                        <div class="font-semibold">{{ $entrega->almacen?->codigo }} · {{ $entrega->almacen?->nombre }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-gray-500">Fecha</div>
                        <div>{{ optional($entrega->fecha_entrega)->format('d/m/Y') }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-gray-500">Total físico</div>
                        <div class="font-semibold">{{ number_format($entrega->total_fisico, 0, ',', '.') }} cajas</div>
                    </div>
                </div>
            </div>

            <div class="bg-white shadow rounded-xl p-4 sm:p-5 mb-4">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-3">
                    <div>
                        <h3 class="font-semibold text-lg">Productos disponibles</h3>
                        <p class="text-sm text-gray-500">La cantidad disponible corresponde a lo verificado físicamente y aún no paletizado.</p>
                    </div>
                    <div id="resumenDisponible" class="text-sm font-semibold"></div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-100 text-gray-600">
                            <tr>
                                <th class="text-left p-3">Producto</th>
                                <th class="text-left p-3">Lote</th>
                                <th class="text-left p-3">Formato</th>
                                <th class="text-left p-3">Calidad</th>
                                <th class="text-right p-3">Físico</th>
                                <th class="text-right p-3">Paletizado</th>
                                <th class="text-right p-3">Pendiente</th>
                            </tr>
                        </thead>
                        <tbody id="tablaDetalles"></tbody>
                    </table>
                </div>
            </div>

            <div class="bg-white shadow rounded-xl p-4 sm:p-5 mb-4">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
                    <div>
                        <h3 class="font-semibold text-lg">Construcción de pallets</h3>
                        <p class="text-sm text-gray-500">Un pallet solo puede contener productos del mismo formato. Puede contener diferentes productos y lotes del mismo formato.</p>
                    </div>
                    <button type="button" id="btnAgregarPallet" class="inline-flex items-center justify-center bg-blue-600 hover:bg-blue-700 text-white font-semibold px-4 py-2.5 rounded-lg">
                        + AGREGAR PALLET
                    </button>
                </div>

                <div id="pallets"></div>

                <div id="sinPallets" class="border border-dashed rounded-xl p-8 text-center text-gray-500">
                    Agregue un pallet para comenzar la distribución física.
                </div>
            </div>

            <div class="flex flex-col sm:flex-row sm:justify-end gap-3">
                <a href="{{ route('wms.paletizacion.index') }}" class="px-5 py-3 border rounded-lg text-center font-semibold text-gray-700">CANCELAR</a>
                <button type="button" id="btnGuardar" class="px-5 py-3 bg-green-600 hover:bg-green-700 text-white rounded-lg font-semibold">GUARDAR PALETIZACIÓN</button>
            </div>
        </div>
    </div>

    <script>
        const detalles = @json($detalles);
        const storeUrl = "{{ route('wms.paletizacion.store', $entrega) }}";
        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';
        let pallets = [];

        document.addEventListener('DOMContentLoaded', () => {
            renderDetalles();
            actualizarResumen();
            document.getElementById('btnAgregarPallet').addEventListener('click', agregarPallet);
            document.getElementById('btnGuardar').addEventListener('click', guardar);
        });

        function renderDetalles() {
            const tbody = document.getElementById('tablaDetalles');
            tbody.innerHTML = detalles.map(d => '<tr class="border-t">' +
                '<td class="p-3"><div class="font-mono font-semibold">' + escapeHtml(d.codigo) + '</div><div class="text-gray-500">' + escapeHtml(d.descripcion || '') + '</div></td>' +
                '<td class="p-3 font-mono">' + escapeHtml(d.lote || '—') + '</td>' +
                '<td class="p-3 font-semibold">' + escapeHtml(d.formato || '—') + '</td>' +
                '<td class="p-3">' + escapeHtml(d.calidad || '—') + '</td>' +
                '<td class="p-3 text-right">' + d.cantidad_fisica + '</td>' +
                '<td class="p-3 text-right">' + d.cantidad_paletizada + '</td>' +
                '<td class="p-3 text-right font-semibold ' + (d.cantidad_pendiente > 0 ? 'text-blue-700' : 'text-green-700') + '">' + d.cantidad_pendiente + '</td>' +
                '</tr>').join('');
        }

        function agregarPallet() {
            pallets.push({ items: [] });
            renderPallets();
        }

        function renderPallets() {
            const contenedor = document.getElementById('pallets');
            document.getElementById('sinPallets').classList.toggle('hidden', pallets.length > 0);

            contenedor.innerHTML = pallets.map((pallet, index) => {
                const formato = palletFormato(pallet);
                const capacidad = formato ? capacidadFormato(formato) : null;
                const total = pallet.items.reduce((s, i) => s + Number(i.cantidad || 0), 0);
                const opciones = detalles
                    .filter(d => disponible(d.id) > 0)
                    .filter(d => !formato || d.formato === formato)
                    .map(d => '<option value="' + d.id + '">' + escapeHtml(d.codigo) + ' · ' + escapeHtml(d.lote || 'SIN LOTE') + ' · disp. ' + disponible(d.id) + '</option>')
                    .join('');

                return '<div class="border rounded-xl p-4 mb-4 bg-gray-50" data-pallet="' + index + '">' +
                    '<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-3">' +
                        '<div><div class="font-semibold">Pallet #' + (index + 1) + '</div><div class="text-sm text-gray-500">El número real será asignado al guardar.</div></div>' +
                        '<div class="text-sm font-semibold">Formato: ' + (formato || '—') + ' · Total: ' + total + (capacidad ? ' / ' + capacidad : '') + ' cajas</div>' +
                    '</div>' +
                    '<div class="space-y-2">' +
                        pallet.items.map((item, itemIndex) => filaItem(index, item, itemIndex)).join('') +
                    '</div>' +
                    '<div class="flex flex-col sm:flex-row gap-2 mt-3">' +
                        '<select class="producto-select w-full border-gray-300 rounded-lg" data-pallet="' + index + '"><option value="">Agregar producto/lote...</option>' + opciones + '</select>' +
                        '<button type="button" class="px-4 py-2 bg-white border rounded-lg font-semibold" onclick="agregarItem(' + index + ')">AGREGAR</button>' +
                        '<button type="button" class="px-4 py-2 text-red-600 font-semibold" onclick="eliminarPallet(' + index + ')">ELIMINAR</button>' +
                    '</div>' +
                    '<div class="mt-3 text-xs text-gray-500">' + (capacidad ? 'Capacidad configurada: ' + capacidad + ' cajas.' : 'Seleccione un producto para determinar la capacidad.') + '</div>' +
                '</div>';
            }).join('');

            actualizarResumen();
        }

        function filaItem(palletIndex, item, itemIndex) {
            const d = detalles.find(x => Number(x.id) === Number(item.entrega_detalle_id));
            return '<div class="grid grid-cols-1 md:grid-cols-12 gap-2 items-center bg-white border rounded-lg p-2">' +
                '<div class="md:col-span-7"><div class="font-mono text-sm font-semibold">' + escapeHtml(d.codigo) + '</div><div class="text-xs text-gray-500">Lote ' + escapeHtml(d.lote || '—') + ' · ' + escapeHtml(d.descripcion || '') + '</div></div>' +
                '<div class="md:col-span-3"><input type="number" min="1" max="' + disponible(d.id, palletIndex, itemIndex) + '" value="' + item.cantidad + '" class="w-full border-gray-300 rounded-lg cantidad-input" data-pallet="' + palletIndex + '" data-item="' + itemIndex + '"></div>' +
                '<div class="md:col-span-2 text-right"><button type="button" class="text-red-600 font-semibold" onclick="eliminarItem(' + palletIndex + ',' + itemIndex + ')">Quitar</button></div>' +
                '</div>';
        }

        function agregarItem(palletIndex) {
            const card = document.querySelector('[data-pallet="' + palletIndex + '"]');
            const select = card.querySelector('.producto-select');
            const id = Number(select.value);
            if (!id) return;

            const d = detalles.find(x => Number(x.id) === id);
            if (!d || disponible(id) <= 0) return;

            const pallet = pallets[palletIndex];
            const formato = palletFormato(pallet);
            if (formato && formato !== d.formato) {
                mostrarAlerta('No puede mezclar formatos en un mismo pallet.', 'error');
                return;
            }

            if (pallet.items.some(i => Number(i.entrega_detalle_id) === id)) {
                mostrarAlerta('Ese producto/lote ya está agregado al pallet. Modifique su cantidad en la fila existente.', 'error');
                return;
            }

            pallet.items.push({ entrega_detalle_id: id, cantidad: Math.min(disponible(id), capacidadFormato(d.formato) || disponible(id)) });
            renderPallets();
        }

        function eliminarItem(palletIndex, itemIndex) {
            pallets[palletIndex].items.splice(itemIndex, 1);
            renderPallets();
        }

        function eliminarPallet(index) {
            pallets.splice(index, 1);
            renderPallets();
        }

        document.addEventListener('input', e => {
            if (!e.target.classList.contains('cantidad-input')) return;
            const p = Number(e.target.dataset.pallet);
            const i = Number(e.target.dataset.item);
            pallets[p].items[i].cantidad = Number(e.target.value || 0);
            actualizarResumen();
        });

        function palletFormato(pallet) {
            if (!pallet.items.length) return null;
            const d = detalles.find(x => Number(x.id) === Number(pallet.items[0].entrega_detalle_id));
            return d?.formato || null;
        }

        function capacidadFormato(formato) {
            const d = detalles.find(x => x.formato === formato && x.capacidad);
            return d?.capacidad || null;
        }

        function cantidadAsignadaDetalle(id) {
            return pallets.reduce((total, p) => total + p.items.filter(i => Number(i.entrega_detalle_id) === Number(id)).reduce((s, i) => s + Number(i.cantidad || 0), 0), 0);
        }

        function disponible(id, palletIndex = null, itemIndex = null) {
            const d = detalles.find(x => Number(x.id) === Number(id));
            if (!d) return 0;
            let asignado = cantidadAsignadaDetalle(id);
            if (palletIndex !== null && itemIndex !== null) {
                asignado -= Number(pallets[palletIndex]?.items[itemIndex]?.cantidad || 0);
            }
            return Math.max(Number(d.cantidad_pendiente) - asignado, 0);
        }

        function actualizarResumen() {
            const totalPendiente = detalles.reduce((s, d) => s + Number(d.cantidad_pendiente), 0);
            const totalAsignado = pallets.reduce((s, p) => s + p.items.reduce((x, i) => x + Number(i.cantidad || 0), 0), 0);
            document.getElementById('resumenDisponible').textContent = 'Pendiente: ' + totalPendiente + ' · En pallets: ' + totalAsignado + ' · Falta asignar: ' + Math.max(totalPendiente - totalAsignado, 0);
        }

        async function guardar() {
            const payload = { pallets: pallets.map(p => ({ items: p.items })) };
            if (!payload.pallets.length || payload.pallets.some(p => !p.items.length)) {
                mostrarAlerta('Debe agregar productos a todos los pallets creados.', 'error');
                return;
            }

            const totalPendiente = detalles.reduce((s, d) => s + Number(d.cantidad_pendiente), 0);
            const totalAsignado = pallets.reduce((s, p) => s + p.items.reduce((x, i) => x + Number(i.cantidad || 0), 0), 0);
            if (totalAsignado !== totalPendiente) {
                mostrarAlerta('La paletización debe distribuir toda la cantidad física pendiente. Pendiente: ' + totalPendiente + ' · asignado: ' + totalAsignado + '.', 'error');
                return;
            }

            for (let index = 0; index < pallets.length; index++) {
                const formato = palletFormato(pallets[index]);
                const capacidad = capacidadFormato(formato);
                const total = pallets[index].items.reduce((s, i) => s + Number(i.cantidad || 0), 0);
                if (!capacidad || total > capacidad) {
                    mostrarAlerta('El pallet #' + (index + 1) + ' supera la capacidad configurada o no tiene configuración.', 'error');
                    return;
                }
            }

            const boton = document.getElementById('btnGuardar');
            boton.disabled = true;
            boton.textContent = 'GUARDANDO...';

            try {
                const res = await fetch(storeUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                    },
                    body: JSON.stringify(payload),
                });
                const data = await res.json();
                if (!res.ok) throw new Error(data.message || 'No fue posible guardar la paletización.');

                mostrarAlerta(data.message + ' Pallets: ' + data.pallets.map(p => p.numero).join(', '), 'success');
                setTimeout(() => window.location.href = "{{ route('wms.paletizacion.index') }}", 900);
            } catch (error) {
                mostrarAlerta(error.message, 'error');
                boton.disabled = false;
                boton.textContent = 'GUARDAR PALETIZACIÓN';
            }
        }

        function mostrarAlerta(mensaje, tipo) {
            const box = document.getElementById('alertBox');
            box.className = 'mb-4 p-3 rounded-lg text-sm ' +
                (tipo === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800');
            box.textContent = mensaje;
            box.classList.remove('hidden');
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        function escapeHtml(value) {
            return String(value ?? '').replace(/[&<>'"]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#039;','"':'&quot;'}[c]));
        }
    </script>
</x-app-layout>
