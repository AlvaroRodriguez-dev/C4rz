<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">WMS - Paletización de Producción</h2>
    </x-slot>

    <div class="py-4 px-3 sm:py-6 sm:px-4">
        <div class="max-w-6xl mx-auto">
            <div class="flex items-center justify-between gap-3 mb-4">
                <a href="{{ route('wms.paletizacion.index') }}" class="text-sm text-gray-600">&larr; Volver a entregas</a>
                <span class="px-3 py-1.5 rounded-full text-xs font-semibold bg-green-100 text-green-800">CONCILIADA</span>
            </div>

            @if (session('success'))
                <div class="mb-4 p-3 rounded-lg text-sm bg-green-100 text-green-800">{{ session('success') }}</div>
            @endif

            @if (session('error'))
                <div class="mb-4 p-3 rounded-lg text-sm bg-red-100 text-red-800">{{ session('error') }}</div>
            @endif

            <div id="alertBox" class="hidden mb-4 p-3 rounded-lg text-sm"></div>

            <div class="bg-white shadow rounded-xl p-4 sm:p-5 mb-4">
                <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                    <div>
                        <div class="text-xs text-gray-500">Documento WMS</div>
                        <div class="font-mono font-semibold">{{ $entrega->documento?->id_documento ?? '—' }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-gray-500">Folio RG-CB-36</div>
                        <div class="font-semibold">{{ $entrega->folio_fisico ?? '—' }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-gray-500">Almacén operativo</div>
                        <div class="font-semibold">{{ $entrega->almacen?->codigo }} · {{ $entrega->almacen?->nombre }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-gray-500">Fecha</div>
                        <div>{{ optional($entrega->fecha_entrega)->format('d/m/Y') }}</div>
                    </div>
                </div>
            </div>

            <div class="bg-white shadow rounded-xl p-4 sm:p-5 mb-4">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-3">
                    <div>
                        <h3 class="font-semibold text-lg">Productos disponibles</h3>
                        <p class="text-sm text-gray-500">Cantidad física conciliada que todavía no está asignada a un pallet.</p>
                    </div>
                    <div id="resumenDisponible" class="text-sm font-semibold text-gray-700"></div>
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

            <form id="formPaletizacion" method="POST" action="{{ route('wms.paletizacion.store', $entrega) }}">
                @csrf
                <input type="hidden" name="pallets" id="palletsInput">

                <div class="bg-white shadow rounded-xl p-4 sm:p-5 mb-4">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
                        <div>
                            <h3 class="font-semibold text-lg">Construcción de pallets</h3>
                            <p class="text-sm text-gray-500">Un pallet solo puede contener productos del mismo formato. Puede mezclar productos y lotes del mismo formato.</p>
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
                    <button type="submit" id="btnGuardar" class="px-5 py-3 bg-green-600 hover:bg-green-700 text-white rounded-lg font-semibold">GUARDAR PALETIZACIÓN</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const detalles = @json($detalles);
        let pallets = [];

        document.addEventListener('DOMContentLoaded', () => {
            renderDetalles();
            renderPallets();

            document.getElementById('btnAgregarPallet').addEventListener('click', () => {
                pallets.push({ items: [] });
                renderPallets();
            });

            document.getElementById('formPaletizacion').addEventListener('submit', validarYPreparar);
        });

        document.addEventListener('change', e => {
            if (!e.target.classList.contains('cantidad-input')) return;
            const palletIndex = Number(e.target.dataset.pallet);
            const itemIndex = Number(e.target.dataset.item);
            pallets[palletIndex].items[itemIndex].cantidad = Number(e.target.value || 0);
            renderPallets();
        });

        function renderDetalles() {
            const tbody = document.getElementById('tablaDetalles');

            tbody.innerHTML = detalles.map(d => {
                const pendiente = disponible(d.id);
                return '<tr class="border-t">' +
                    '<td class="p-3"><div class="font-mono font-semibold">' + escapeHtml(d.codigo) + '</div><div class="text-gray-500">' + escapeHtml(d.descripcion || '') + '</div></td>' +
                    '<td class="p-3 font-mono">' + escapeHtml(d.lote || '—') + '</td>' +
                    '<td class="p-3 font-semibold">' + escapeHtml(d.formato || '—') + '</td>' +
                    '<td class="p-3">' + escapeHtml(d.calidad || '—') + '</td>' +
                    '<td class="p-3 text-right">' + formatear(d.cantidad_fisica) + '</td>' +
                    '<td class="p-3 text-right">' + formatear(d.cantidad_paletizada) + '</td>' +
                    '<td class="p-3 text-right font-semibold ' + (pendiente > 0 ? 'text-blue-700' : 'text-green-700') + '">' + formatear(pendiente) + '</td>' +
                    '</tr>';
            }).join('');

            actualizarResumen();
        }

        function renderPallets() {
            const contenedor = document.getElementById('pallets');
            document.getElementById('sinPallets').classList.toggle('hidden', pallets.length > 0);

            contenedor.innerHTML = pallets.map((pallet, index) => {
                const formato = palletFormato(pallet);
                const capacidad = capacidadFormato(formato);
                const total = totalPallet(pallet);
                const tipo = capacidad && total === capacidad ? 'COMPLETO' : 'SALDO';
                const tipoClass = tipo === 'COMPLETO' ? 'bg-green-100 text-green-800' : 'bg-amber-100 text-amber-800';
                const porcentaje = capacidad ? Math.min((total / capacidad) * 100, 100) : 0;

                const opciones = detalles
                    .filter(d => disponible(d.id) > 0)
                    .filter(d => !formato || d.formato === formato)
                    .map(d => '<option value="' + d.id + '">' + escapeHtml(d.codigo) + ' · lote ' + escapeHtml(d.lote || 'SIN LOTE') + ' · disp. ' + formatear(disponible(d.id)) + '</option>')
                    .join('');

                return '<div class="border rounded-xl p-4 mb-4 bg-gray-50" data-pallet="' + index + '">' +
                    '<div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-3">' +
                        '<div><div class="font-semibold">Pallet en construcción #' + (index + 1) + '</div><div class="text-xs text-gray-500">El número definitivo se asignará al guardar.</div></div>' +
                        '<div class="flex items-center gap-2"><span class="px-2 py-1 rounded-full text-xs font-semibold ' + tipoClass + '">' + tipo + '</span><span class="font-semibold">' + formatear(total) + (capacidad ? ' / ' + formatear(capacidad) : '') + ' cajas</span></div>' +
                    '</div>' +
                    '<div class="w-full bg-gray-200 rounded-full h-2 mb-4"><div class="bg-blue-600 h-2 rounded-full" style="width:' + porcentaje + '%"></div></div>' +
                    '<div class="space-y-2">' + pallet.items.map((item, itemIndex) => filaItem(index, item, itemIndex)).join('') + '</div>' +
                    '<div class="grid grid-cols-1 md:grid-cols-12 gap-2 mt-3">' +
                        '<select class="md:col-span-8 producto-select w-full border-gray-300 rounded-lg"><option value="">Agregar producto/lote...</option>' + opciones + '</select>' +
                        '<button type="button" class="md:col-span-2 px-4 py-2 bg-white border rounded-lg font-semibold" onclick="agregarItem(' + index + ')">AGREGAR</button>' +
                        '<button type="button" class="md:col-span-2 px-4 py-2 text-red-600 font-semibold" onclick="eliminarPallet(' + index + ')">ELIMINAR</button>' +
                    '</div>' +
                    '<div class="mt-3 text-xs ' + (capacidad ? 'text-gray-500' : 'text-red-600') + '">' +
                        (capacidad ? 'Capacidad configurada para formato ' + escapeHtml(formato) + ': ' + formatear(capacidad) + ' cajas. Un pallet con menos cantidad se registrará como SALDO.' : 'El formato seleccionado no tiene una capacidad configurada de pallet.') +
                    '</div>' +
                '</div>';
            }).join('');

            actualizarResumen();
        }

        function filaItem(palletIndex, item, itemIndex) {
            const d = detalles.find(x => Number(x.id) === Number(item.entrega_detalle_id));
            const maximo = disponible(d.id, palletIndex, itemIndex);

            return '<div class="grid grid-cols-1 md:grid-cols-12 gap-2 items-center bg-white border rounded-lg p-2">' +
                '<div class="md:col-span-7"><div class="font-mono text-sm font-semibold">' + escapeHtml(d.codigo) + '</div><div class="text-xs text-gray-500">Lote ' + escapeHtml(d.lote || '—') + ' · ' + escapeHtml(d.descripcion || '') + '</div></div>' +
                '<div class="md:col-span-3"><label class="text-xs text-gray-500">Cantidad</label><input type="number" min="1" max="' + maximo + '" value="' + Number(item.cantidad || 0) + '" class="w-full border-gray-300 rounded-lg cantidad-input" data-pallet="' + palletIndex + '" data-item="' + itemIndex + '"></div>' +
                '<div class="md:col-span-2 text-right"><button type="button" class="text-red-600 font-semibold" onclick="eliminarItem(' + palletIndex + ',' + itemIndex + ')">Quitar</button></div>' +
                '</div>';
        }

        function agregarItem(palletIndex) {
            const card = document.querySelector('[data-pallet="' + palletIndex + '"]');
            const select = card.querySelector('.producto-select');
            const id = Number(select.value);
            if (!id) return;

            const d = detalles.find(x => Number(x.id) === id);
            if (!d) return;

            const pallet = pallets[palletIndex];
            const formato = palletFormato(pallet);

            if (!d.configurado || !d.capacidad) {
                mostrarAlerta('El formato ' + d.formato + ' no tiene una capacidad de pallet configurada.', 'error');
                return;
            }

            if (formato && formato !== d.formato) {
                mostrarAlerta('No puede mezclar formatos en un mismo pallet.', 'error');
                return;
            }

            if (pallet.items.some(i => Number(i.entrega_detalle_id) === id)) {
                mostrarAlerta('Ese producto/lote ya está agregado al pallet. Modifique la cantidad en la fila existente.', 'error');
                return;
            }

            const espacio = Math.max(d.capacidad - totalPallet(pallet), 0);
            const disponibleProducto = disponible(id);
            const cantidadInicial = Math.min(espacio, disponibleProducto);

            if (cantidadInicial <= 0) {
                mostrarAlerta('El pallet ya alcanzó su capacidad o el producto no tiene cantidad disponible.', 'error');
                return;
            }

            pallet.items.push({ entrega_detalle_id: id, cantidad: cantidadInicial });
            ocultarAlerta();
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

        function palletFormato(pallet) {
            if (!pallet.items.length) return null;
            const d = detalles.find(x => Number(x.id) === Number(pallet.items[0].entrega_detalle_id));
            return d?.formato || null;
        }

        function capacidadFormato(formato) {
            if (!formato) return null;
            const d = detalles.find(x => x.formato === formato && x.capacidad);
            return d?.capacidad ? Number(d.capacidad) : null;
        }

        function totalPallet(pallet) {
            return pallet.items.reduce((total, item) => total + Number(item.cantidad || 0), 0);
        }

        function cantidadAsignadaDetalle(id) {
            return pallets.reduce((total, pallet) => total + pallet.items
                .filter(item => Number(item.entrega_detalle_id) === Number(id))
                .reduce((sum, item) => sum + Number(item.cantidad || 0), 0), 0);
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
            const totalPendiente = detalles.reduce((sum, d) => sum + Number(d.cantidad_pendiente), 0);
            const totalAsignado = pallets.reduce((sum, pallet) => sum + totalPallet(pallet), 0);
            const restante = Math.max(totalPendiente - totalAsignado, 0);

            document.getElementById('resumenDisponible').textContent =
                'Pendiente: ' + formatear(totalPendiente) + ' · En construcción: ' + formatear(totalAsignado) + ' · Restante: ' + formatear(restante);
        }

        function validarYPreparar(event) {
            ocultarAlerta();

            if (!pallets.length) {
                event.preventDefault();
                mostrarAlerta('Debe agregar al menos un pallet.', 'error');
                return;
            }

            for (let index = 0; index < pallets.length; index++) {
                const pallet = pallets[index];
                const total = totalPallet(pallet);
                const formato = palletFormato(pallet);
                const capacidad = capacidadFormato(formato);

                if (!pallet.items.length) {
                    event.preventDefault();
                    mostrarAlerta('El pallet #' + (index + 1) + ' no tiene productos.', 'error');
                    return;
                }

                if (!capacidad) {
                    event.preventDefault();
                    mostrarAlerta('El pallet #' + (index + 1) + ' no tiene una capacidad configurada.', 'error');
                    return;
                }

                if (total > capacidad) {
                    event.preventDefault();
                    mostrarAlerta('El pallet #' + (index + 1) + ' supera la capacidad de ' + capacidad + ' cajas.', 'error');
                    return;
                }
            }

            document.getElementById('palletsInput').value = JSON.stringify(pallets);
            document.getElementById('btnGuardar').disabled = true;
            document.getElementById('btnGuardar').textContent = 'GUARDANDO...';
        }

        function formatear(valor) {
            return new Intl.NumberFormat('es-BO').format(Number(valor || 0));
        }

        function escapeHtml(valor) {
            return String(valor ?? '')
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;')
                .replaceAll("'", '&#039;');
        }

        function mostrarAlerta(mensaje, tipo) {
            const box = document.getElementById('alertBox');
            box.className = 'mb-4 p-3 rounded-lg text-sm ' + (tipo === 'error' ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800');
            box.textContent = mensaje;
            box.classList.remove('hidden');
        }

        function ocultarAlerta() {
            document.getElementById('alertBox').classList.add('hidden');
        }
    </script>
</x-app-layout>
