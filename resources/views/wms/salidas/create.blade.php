<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            WMS - Registrar Salida
        </h2>
    </x-slot>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-rc.0/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-rc.0/js/select2.min.js"></script>

    <style>
        .select2-container .select2-selection--single {
            height: 48px !important;
            display: flex;
            align-items: center;
            border-radius: 0.75rem !important;
            border-color: #d1d5db !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 48px !important;
            font-size: 16px;
            padding-left: 12px !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 46px !important;
        }

        .select2-dropdown {
            border-radius: 0.75rem !important;
        }

        .row-input {
            font-size: 16px;
        }
    </style>

    <div class="py-4 px-3 sm:py-6 sm:px-4">
        <div class="max-w-3xl mx-auto">

            <a href="{{ route('wms.index') }}" class="text-sm text-gray-600 mb-3 inline-flex items-center gap-1">&larr;
                Volver</a>

            <div id="alertBox" class="hidden mb-4 p-3 rounded-lg text-sm"></div>

            <div class="bg-white shadow rounded-xl p-4 sm:p-5 mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Nota de Despacho
                    <p class="text-xs text-gray-500 mt-2">[Tipo 1: Despacho | Tipo 2: Programacion | Tipo 3: Transito]
                    </p>
                </label>
                <select id="selectNota" class="w-full" style="width:100%"></select>
                <p class="text-xs text-gray-500 mt-2">Busca por número de nota o glosa.</p>
            </div>

            <p id="totalNotaTxt"
                class="hidden mb-4 text-sm font-semibold text-gray-700 bg-gray-100 rounded-lg px-3 py-2"></p>

            <div id="itemsWrapper" class="hidden">
                <div id="items" class="space-y-4 mb-24 sm:mb-5"></div>

                <div
                    class="fixed sm:static bottom-0 left-0 right-0 bg-white sm:bg-transparent border-t sm:border-0 border-gray-200 p-3 sm:p-0 shadow-[0_-2px_8px_rgba(0,0,0,0.06)] sm:shadow-none z-20">
                    <div class="max-w-3xl mx-auto">
                        <button id="btnGuardar"
                            class="w-full bg-red-600 hover:bg-red-700 text-white font-semibold py-4 rounded-xl shadow text-lg active:scale-[0.99] transition">
                            GUARDAR SALIDA
                        </button>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script>
        const routeBuscarNotas = "{{ route('wms.salidas.notas.buscar') }}";
        const routeDetalleNota = "{{ url('wms/salidas/notas') }}"; // + /{id}/detalle
        const routeStore = "{{ route('wms.salidas.store') }}";
        const csrfToken = "{{ csrf_token() }}";

        const routeLotesAlternativos = "{{ route('wms.salidas.lotes-alternativos') }}";
        const routeUbicacionesPorLote = "{{ route('wms.salidas.ubicaciones-por-lote') }}";

        const routeDistribucionAutomatica = "{{ route('wms.salidas.distribucion-automatica') }}";
        const modoCambioLote = "{{ config('wms.cambio_lote_modo') }}"; // 'manual' | 'automatico'

        let notaSeleccionada = null;
        let itemsData = [];
        let exigeDespachoCompleto = false;

        $(document).ready(function() {
            $('#selectNota').select2({
                placeholder: 'Escribe para buscar una nota de despacho...',
                minimumInputLength: 1,
                width: '100%',
                ajax: {
                    url: routeBuscarNotas,
                    dataType: 'json',
                    delay: 300,
                    data: params => ({
                        q: params.term
                    }),
                    processResults: data => ({
                        results: data.results
                    })
                }
            });

            $('#selectNota').on('select2:select', function(e) {
                notaSeleccionada = {
                    id: e.params.data.id,
                    tipo_registro: e.params.data.tipo_registro,
                    glosa: e.params.data.glosa,
                    fecha: e.params.data.fecha
                };
                cargarDetalle(notaSeleccionada.id);
            });

            $('#btnGuardar').on('click', guardarSalida);
        });

        function cargarDetalle(id) {
            $('#items').empty();
            $('#itemsWrapper').addClass('hidden');

            fetch(`${routeDetalleNota}/${encodeURIComponent(id)}/detalle`)
                .then(res => res.json())
                .then(data => {
                    itemsData = data.items;
                    exigeDespachoCompleto = data.exige_despacho_completo;

                    $('#totalNotaTxt').removeClass('hidden').text(
                        `📦 Total de cajas en la nota: ${data.total_cajas_nota}`);

                    // Aviso de despacho completo obligatorio
                    $('#avisoDespachoCompleto').remove();
                    if (exigeDespachoCompleto) {
                        $('#totalNotaTxt').after(`
                            <div id="avisoDespachoCompleto" class="mb-4 p-3 bg-orange-100 text-orange-800 rounded-lg text-sm font-medium">
                                ⚠️ Nota con fecha ${data.fecha_nota}: debe despacharse el 100%. No se puede guardar dejando cantidades pendientes.
                            </div>
                        `);
                    }

                    itemsData.forEach((item, itemIndex) => {

                        // --- Caso 1: ya completo, no requiere acción ---
                        if (item.completo) {
                            $('#items').append(`
                                <div class="border border-green-300 bg-green-50 rounded-xl p-4 text-sm text-green-700">
                                    ✅ ${item.codigo} (Lote ${item.clote ?? 'S/L'}) — ${item.documento}<br>
                                    Ya despachado completamente (${item.cantidad_procesada}/${item.cantidad_despacho} cajas).
                                </div>
                            `);
                            return;
                        }

                        // --- Caso 2: requiere cambio de lote por excepción ---
                        if (item.requiere_cambio_lote) {

                            // Bloqueado por fecha de producción: no se ofrece ninguna opción, bloquea TODA la nota
                            if (item.cambio_lote_bloqueado) {
                                item.bloqueadoDefinitivo = true;
                                $('#items').append(`
                                    <div class="border border-gray-400 bg-gray-100 rounded-xl p-4" data-item-card="${itemIndex}">
                                        <p class="text-xs text-gray-400">${item.documento}</p>
                                        <p class="font-mono font-semibold text-gray-800 text-sm">${item.codigo}</p>
                                        <p class="text-sm text-gray-600">${item.descripcion}</p>
                                        <div class="mt-3 bg-white border border-gray-300 rounded-lg p-3">
                                            <p class="text-sm text-gray-700 font-semibold">
                                                🚫 Lote ${item.clote ?? 'S/L'} (producción: ${item.fecha_produccion_lote}) sin stock (${item.saldo_lote_declarado}/${item.cantidad_pendiente}).
                                            </p>
                                            <p class="text-xs text-gray-500 mt-1">
                                                Este Lote NO admite Cambio (producción posterior a la fecha límite permitida).
                                            </p>
                                        </div>
                                    </div>
                                `);
                                return;
                            }

                            // Permitido: flujo normal con Sí/No
                            $('#items').append(`
                                <div class="border border-red-300 bg-red-50 rounded-xl p-4" data-item-card="${itemIndex}">
                                    <div class="flex items-start justify-between gap-3 mb-2">
                                        <div class="min-w-0">
                                            <p class="text-xs text-gray-400">${item.documento}</p>
                                            <p class="font-mono font-semibold text-gray-800 text-sm">${item.codigo}</p>
                                            <p class="text-sm text-gray-600">${item.descripcion}</p>
                                            <p class="text-xs text-gray-400">Producción: ${item.fecha_produccion_lote}</p>
                                        </div>
                                        <div class="text-right shrink-0">
                                            <p class="text-[11px] text-gray-500">A despachar</p>
                                            <p class="text-xl font-bold text-gray-800">${item.cantidad_pendiente}</p>
                                            <p class="text-[10px] text-gray-400">de ${item.cantidad_despacho} original</p>
                                        </div>
                                    </div>
                                    <div class="mt-3 bg-white border border-red-200 rounded-lg p-3">
                                        <p class="text-sm text-red-700 font-medium">
                                            ⚠️ Lote ${item.clote ?? 'S/L'} (Stock: ${item.saldo_lote_declarado}). Cantidad requerida: ${item.cantidad_pendiente}.
                                        </p>
                                        <p class="text-xs text-gray-500 mt-1">¿Desea aplicar Cambio de Lote por Excepción?</p>
                                        <div class="flex gap-2 mt-2">
                                            <button type="button" data-item="${itemIndex}" class="btnCambioLoteSi bg-blue-600 text-white text-xs font-semibold rounded-lg px-3 py-2">Sí, cambiar lote</button>
                                            <button type="button" data-item="${itemIndex}" class="btnCambioLoteNo bg-gray-300 text-gray-700 text-xs font-semibold rounded-lg px-3 py-2">No, pausar</button>
                                        </div>
                                        <div id="panelCambioLote-${itemIndex}" class="hidden mt-3 space-y-2"></div>
                                    </div>
                                </div>
                            `);
                            return;
                        }

                        // --- Caso 3: item normal, con saldo disponible en el lote declarado ---
                        if (item.cantidad_procesada > 0) {
                            const detalleReserva = item.cantidad_reservada_ot > 0 ?
                                ` (de los cuales ${item.cantidad_reservada_ot} están en Órdenes de Trabajo aún pendientes de verificación)` :
                                '';

                            $('#items').append(`
                                <p class="text-xs text-orange-600 px-1">
                                    ⚠️ ${item.codigo}: ya se entregaron ${item.cantidad_procesada} de ${item.cantidad_despacho} cajas${detalleReserva}. Pendiente: ${item.cantidad_pendiente}.
                                </p>
                            `);
                        }

                        const ubicacionesHtml = item.ubicaciones.map((u, uIndex) => `
                    <div class="flex items-center justify-between gap-3 border-t border-gray-100 pt-2 mt-2 first:border-0 first:mt-0 first:pt-0">
                        <div class="text-sm">
                            <p class="font-mono font-semibold text-gray-700">${u.pallet}</p>
                            <p class="text-xs text-gray-500">Galpón ${u.galpon} · Ubic. ${u.ubicacion}</p>
                            <p class="text-xs text-green-600 font-semibold">Saldo: ${u.saldo}</p>
                        </div>
                        <input type="number" min="0" max="${u.saldo}"
                            data-item="${itemIndex}" data-ubic="${uIndex}"
                            class="row-input sacar-input w-24 border-gray-300 rounded-lg p-2.5 text-right"
                            placeholder="0">
                    </div>
                `).join('');

                        const sinSaldo = item.ubicaciones.length === 0 ?
                            '<p class="text-sm text-orange-600 mt-2">No hay saldo disponible para este código/lote.</p>' :
                            '';

                        $('#items').append(`
                    <div class="border border-gray-200 rounded-xl p-4 shadow-sm bg-white" data-item-card="${itemIndex}">
                        <div class="flex items-start justify-between gap-3 mb-2">
                            <div class="min-w-0">
                                <p class="text-xs text-gray-400">${item.documento}</p>
                                <p class="font-mono font-semibold text-gray-800 text-sm">${item.codigo}</p>
                                <p class="text-sm text-gray-600 leading-snug">${item.descripcion}</p>
                                ${item.clote ? `<p class="text-xs text-gray-400 mt-0.5">Lote: ${item.clote}</p>` : ''}
                            </div>
                            <div class="text-right shrink-0">
                                <p class="text-[11px] text-gray-500">A despachar</p>
                                <p class="text-xl font-bold text-gray-800">${item.cantidad_pendiente}</p>
                                <p class="text-[10px] text-gray-400">de ${item.cantidad_despacho} original</p>
                                <p class="text-[11px] mt-1">
                                    Asignado: <span class="font-semibold total-asignado" data-item="${itemIndex}">0</span>
                                </p>
                            </div>
                        </div>
                        <div>
                            ${ubicacionesHtml}
                            ${sinSaldo}
                        </div>
                    </div>
                `);
                    });

                    // Recalcula tanto los totales visuales como el estado del botón GUARDAR
                    // en cada cambio de cantidad (indispensable para notas con despacho 100% obligatorio).
                    $('.sacar-input').on('input', function() {
                        actualizarTotales();
                        actualizarEstadoGuardar();
                    });

                    bindBotonesCambioLote();
                    actualizarEstadoGuardar();
                    $('#itemsWrapper').removeClass('hidden');
                });
        }

        /**
         * Vincula los botones "Sí, cambiar lote" / "No, pausar" de cada tarjeta con
         * requiere_cambio_lote. Se llama después de cada render porque los botones
         * se recrean dinámicamente en el DOM.
         */
        function bindBotonesCambioLote() {
            $('.btnCambioLoteSi').off('click').on('click', function() {
                const idx = $(this).data('item');
                const item = itemsData[idx];

                item.lotesAgregados = item.lotesAgregados || [];

                if (modoCambioLote === 'automatico') {
                    iniciarModoAutomatico(idx, item);
                } else {
                    iniciarModoManual(idx, item);
                }
            });

            $('.btnCambioLoteNo').off('click').on('click', function() {
                const idx = $(this).data('item');
                itemsData[idx].pausado = true;
                $(`#panelCambioLote-${idx}`).removeClass('hidden').html(
                    '<p class="text-xs text-gray-600 font-medium">🛑 Tarea pausada para revisión. No se podrá guardar la salida hasta resolver este ítem.</p>'
                );
                actualizarEstadoGuardar();
            });
        }

        /** MODO AUTOMÁTICO: propone una distribución FIFO y el usuario solo confirma. */
        function iniciarModoAutomatico(idx, item) {
            const panel = $(`#panelCambioLote-${idx}`);
            panel.removeClass('hidden').html('<p class="text-xs text-gray-500">Calculando distribución automática...</p>');

            fetch(
                    `${routeDistribucionAutomatica}?codigo=${encodeURIComponent(item.codigo)}&lote_excluir=${encodeURIComponent(item.clote ?? '')}&cantidad=${item.cantidad_pendiente}`
                )
                .then(res => res.json())
                .then(data => {
                    if (!data.completo) {
                        panel.html(`
                    <p class="text-xs text-red-600 font-medium">
                        ❌ No hay stock suficiente combinando todos los lotes disponibles. Faltan ${data.faltante} cajas.
                    </p>
                    <button type="button" data-item="${idx}" class="btnModoManualFallback text-xs text-blue-600 font-medium mt-2">Intentar manualmente</button>
                `);
                        $('.btnModoManualFallback').off('click').on('click', function() {
                            iniciarModoManual($(this).data('item'), itemsData[$(this).data('item')]);
                        });
                        return;
                    }

                    const filas = data.propuesta.map(p => {
                        const marcaOriginal = p.clote === item.clote ?
                            ' <span class="text-blue-600 font-semibold">⭐ lote original</span>' : '';
                        return `
                            <div class="border-t border-gray-100 pt-2 mt-2 first:border-0 first:mt-0 first:pt-0">
                                <p class="text-sm font-semibold text-gray-700">Lote ${p.clote}${marcaOriginal} <span class="text-xs text-gray-400 font-normal">(producción: ${p.fecha_produccion})</span></p>
                                <p class="text-xs text-gray-500">${p.subtotal} cajas, de ${p.pallets.length} pallet(s)</p>
                            </div>
                        `;
                    }).join('');

                    panel.html(`
                <p class="text-xs text-gray-600 font-medium mb-1">Distribución automática propuesta (FIFO, más antiguo primero):</p>
                ${filas}
                <div class="flex gap-2 mt-3">
                    <button type="button" data-item="${idx}" class="btnConfirmarAutomatico bg-blue-600 text-white text-xs font-semibold rounded-lg px-3 py-2">Confirmar distribución</button>
                    <button type="button" data-item="${idx}" class="btnModoManualFallback text-xs text-gray-600 font-medium">Prefiero elegir manualmente</button>
                </div>
            `);

                    $('.btnConfirmarAutomatico').off('click').on('click', function() {
                        const i = $(this).data('item');
                        itemsData[i].lotes_aplicados = data.propuesta.map(p => ({
                            clote: p.clote,
                            salidas: p.pallets.map(pl => ({
                                pallet: pl.pallet,
                                almacen: pl.almacen,
                                galpon: pl.galpon,
                                ubicacion: pl.ubicacion,
                                cantidad: pl.cantidad,
                            })),
                        }));
                        itemsData[i].cambio_lote_resuelto = true;
                        $(`#panelCambioLote-${i}`).html(
                            `<p class="text-xs text-green-700 font-medium">✅ Distribución automática confirmada (${data.propuesta.length} lote(s)).</p>`
                        );
                        actualizarEstadoGuardar();
                    });

                    $('.btnModoManualFallback').off('click').on('click', function() {
                        iniciarModoManual($(this).data('item'), itemsData[$(this).data('item')]);
                    });
                });
        }

        /** MODO MANUAL: el usuario va agregando lotes uno por uno hasta cubrir el 100%. */
        function iniciarModoManual(idx, item) {
            item.lotesAgregados = item.lotesAgregados || [];
            renderPanelManual(idx, item);
        }

        function renderPanelManual(idx, item) {
            const panel = $(`#panelCambioLote-${idx}`);
            const totalAgregado = item.lotesAgregados.reduce((s, l) => s + l.subtotal, 0);
            const restante = item.cantidad_pendiente - totalAgregado;

            const listaLotes = item.lotesAgregados.map((l, li) => `
        <div class="flex justify-between items-center text-sm border-t border-gray-100 pt-1 mt-1 first:border-0">
            <span>Lote ${l.clote}: <strong>${l.subtotal}</strong> cajas</span>
            <button type="button" data-item="${idx}" data-lote-idx="${li}" class="btnQuitarLoteManual text-xs text-red-600">Quitar</button>
        </div>
    `).join('');

            panel.removeClass('hidden').html(`
                <p class="text-xs font-medium text-gray-600">Asignado: <span class="${totalAgregado === item.cantidad_pendiente ? 'text-green-600' : 'text-orange-600'} font-semibold">${totalAgregado} / ${item.cantidad_pendiente}</span></p>
                ${listaLotes}
                ${restante > 0 ? `
                                                                        <div class="mt-2 pt-2 border-t border-gray-100 space-y-2">
                                                                            <select class="selectLoteManual w-full border-gray-300 rounded-lg text-sm p-2" data-item="${idx}">
                                                                                <option value="">Selecciona un lote (restante: ${restante})...</option>
                                                                            </select>
                                                                            <div id="pallesLoteManual-${idx}" class="space-y-2"></div>
                                                                        </div>
                                                                    ` : `<p class="text-xs text-green-700 font-medium mt-2">✅ 100% asignado. Puedes finalizar.</p>`}
                <div class="flex gap-2 mt-3">
                    ${restante === 0 ? `<button type="button" data-item="${idx}" class="btnFinalizarManual bg-blue-600 text-white text-xs font-semibold rounded-lg px-3 py-2">Finalizar cambio de lote</button>` : ''}
                </div>
            `);

            if (restante > 0) {
                fetch(
                        `${routeLotesAlternativos}?codigo=${encodeURIComponent(item.codigo)}&lote_excluir=${encodeURIComponent(item.clote ?? '')}`
                    )
                    .then(res => res.json())
                    .then(data => {
                        const yaAgregados = item.lotesAgregados.map(l => l.clote);
                        const disponibles = data.lotes.filter(l => !yaAgregados.includes(l.clote));

                        const options = disponibles.map(l => {
                            const etiqueta = l.es_lote_original ? ' ⭐ (lote original de la nota)' : '';
                            return `<option value="${l.clote}">${l.clote} · prod. ${l.fecha_produccion} (Stock: ${l.saldo_total})${etiqueta}</option>`;
                        }).join('');

                        $(`.selectLoteManual[data-item="${idx}"]`).append(options);
                    });

                $(`.selectLoteManual[data-item="${idx}"]`).off('change').on('change', function() {
                    const loteElegido = $(this).val();
                    if (!loteElegido) return;

                    fetch(
                            `${routeUbicacionesPorLote}?codigo=${encodeURIComponent(item.codigo)}&clote=${encodeURIComponent(loteElegido)}`
                        )
                        .then(res => res.json())
                        .then(resUbic => {
                            const cont = $(`#pallesLoteManual-${idx}`);
                            cont.empty();

                            resUbic.ubicaciones.forEach((u, ui) => {
                                cont.append(`
                            <div class="flex items-center justify-between gap-2 border-t border-gray-100 pt-1">
                                <div class="text-xs">
                                    <p class="font-mono">${u.pallet}</p>
                                    <p class="text-gray-500">Galpón ${u.galpon} · ${u.ubicacion} · Saldo: ${u.saldo}</p>
                                </div>
                                <input type="number" min="0" max="${u.saldo}" data-ui="${ui}"
                                    class="inputPalletLoteManual w-20 border-gray-300 rounded-lg p-1.5 text-right text-sm">
                            </div>
                        `);
                            });

                            cont.append(
                                `<button type="button" class="btnAgregarLoteManual bg-gray-800 text-white text-xs font-semibold rounded-lg px-3 py-1.5 mt-2">+ Agregar este lote</button>`
                            );

                            $('.btnAgregarLoteManual').off('click').on('click', function() {
                                const salidas = [];
                                let subtotal = 0;

                                resUbic.ubicaciones.forEach((u, ui) => {
                                    const cantidad = parseInt($(
                                            `.inputPalletLoteManual[data-ui="${ui}"]`)
                                        .val()) || 0;
                                    if (cantidad <= 0) return;
                                    if (cantidad > u.saldo) return;

                                    subtotal += cantidad;
                                    salidas.push({
                                        pallet: u.pallet,
                                        almacen: u.almacen,
                                        galpon: u.galpon,
                                        ubicacion: u.ubicacion,
                                        cantidad
                                    });
                                });

                                if (subtotal <= 0) {
                                    mostrarAlertaEnTarjeta(idx,
                                        'Ingresa al menos una cantidad para este lote.', 'error');
                                    return;
                                }
                                if (subtotal > restante) {
                                    mostrarAlertaEnTarjeta(idx,
                                        `No puedes asignar más de lo pendiente (${restante}).`,
                                        'error');
                                    return;
                                }

                                item.lotesAgregados.push({
                                    clote: loteElegido,
                                    subtotal,
                                    salidas
                                });
                                renderPanelManual(idx, item);
                            });
                        });
                });
            }

            $('.btnQuitarLoteManual').off('click').on('click', function() {
                item.lotesAgregados.splice($(this).data('lote-idx'), 1);
                renderPanelManual(idx, item);
            });

            $('.btnFinalizarManual').off('click').on('click', function() {
                item.lotes_aplicados = item.lotesAgregados.map(l => ({
                    clote: l.clote,
                    salidas: l.salidas
                }));
                item.cambio_lote_resuelto = true;
                panel.html('<p class="text-xs text-green-700 font-medium">✅ Cambio de lote confirmado (' + item
                    .lotesAgregados.length + ' lote(s)).</p>');
                actualizarEstadoGuardar();
            });
        }

        /**
         * Habilita/deshabilita el botón GUARDAR según: cambios de lote pendientes de
         * resolver, bloqueos definitivos por fecha, y (si la nota exige despacho 100%)
         * que todos los ítems activos estén totalmente asignados.
         */
        /**
         * Habilita/deshabilita el botón GUARDAR:
         * - Si la nota EXIGE despacho completo (fecha >= corte): todos los ítems activos
         *   deben estar resueltos y asignados al 100%.
         * - Si la nota PERMITE despacho parcial (fecha < corte): basta con que exista al
         *   menos una cantidad asignada en algún ítem; los ítems con cambio de lote aún
         *   sin resolver simplemente se omiten del guardado (se completan después).
         */
        function actualizarEstadoGuardar() {
            const hayBloqueoDefinitivo = itemsData.some(item => item.bloqueadoDefinitivo);

            let faltaCompletar = false;
            let hayAlgoQueGuardar = false;

            itemsData.forEach((item, itemIndex) => {
                if (item.completo || item.pausado || item.bloqueadoDefinitivo) return;

                // Ítem resuelto por cambio de lote (uno o varios lotes)
                if (item.lotes_aplicados) {
                    const asignado = item.lotes_aplicados.reduce((s, l) =>
                        s + l.salidas.reduce((s2, sd) => s2 + sd.cantidad, 0), 0);

                    if (asignado > 0) hayAlgoQueGuardar = true;
                    if (exigeDespachoCompleto && asignado < item.cantidad_pendiente) faltaCompletar = true;
                    return;
                }

                // Ítem con cambio de lote pendiente de decidir (ni Sí resuelto, ni No pausado)
                if (item.requiere_cambio_lote) {
                    if (exigeDespachoCompleto) faltaCompletar = true;
                    // Si la nota permite parcial, este ítem simplemente no aporta cantidad todavía.
                    return;
                }

                // Ítem normal: suma lo digitado en sus inputs de pallet
                let asignado = 0;
                item.ubicaciones.forEach((u, uIndex) => {
                    asignado += parseInt($(
                        `.sacar-input[data-item="${itemIndex}"][data-ubic="${uIndex}"]`
                    ).val()) || 0;
                });

                if (asignado > 0) hayAlgoQueGuardar = true;
                if (exigeDespachoCompleto && asignado < item.cantidad_pendiente) faltaCompletar = true;
            });

            
            // El bloqueo TOTAL de la nota por ítems con lote no elegible (cambio de lote fecha
            // límite) solo debe aplicar cuando la nota EXIGE despacho completo. Si la nota
            // permite despacho parcial (fecha anterior al corte), esos ítems simplemente quedan
            // sin procesar (se omiten al guardar) y no deben impedir despachar el resto.
            const bloqueado = (exigeDespachoCompleto && hayBloqueoDefinitivo) || faltaCompletar || !hayAlgoQueGuardar;

            $('#btnGuardar').prop('disabled', bloqueado)
                .toggleClass('opacity-50 cursor-not-allowed', bloqueado);

            if (faltaCompletar && !hayBloqueoDefinitivo) {
                $('#btnGuardar').attr('title', 'Esta nota requiere despacho completo (100%) antes de poder guardar.');
            } else if (!hayAlgoQueGuardar && !hayBloqueoDefinitivo) {
                $('#btnGuardar').attr('title', 'Ingresa al menos una cantidad para habilitar el guardado.');
            } else {
                $('#btnGuardar').removeAttr('title');
            }
        }

        function actualizarTotales() {
            itemsData.forEach((item, itemIndex) => {
                let total = 0;

                $(`.sacar-input[data-item="${itemIndex}"]`).each(function() {
                    total += parseInt($(this).val()) || 0;
                });
                $(`.sacar-input-sustituto[data-item="${itemIndex}"]`).each(function() {
                    total += parseInt($(this).val()) || 0;
                });

                $(`.total-asignado[data-item="${itemIndex}"]`).text(total);
                $(`.total-asignado[data-item="${itemIndex}"]`)
                    .toggleClass('text-red-600', total > item.cantidad_pendiente)
                    .toggleClass('text-green-600', total > 0 && total <= item.cantidad_pendiente);
            });
        }

        function guardarSalida() {
            const lines = [];
            let error = null;

            itemsData.forEach((item, itemIndex) => {
                if (item.completo) return;
                if (item.pausado) return;
                if (item.bloqueadoDefinitivo) return;

                if (item.lotes_aplicados) {
                    lines.push({
                        codigo: item.codigo,
                        descrip: item.descrip,
                        descrip1: item.descrip1,
                        lote_declarado: item.clote,
                        cantidad_despacho: item.cantidad_despacho,
                        lotes_aplicados: item.lotes_aplicados,
                    });
                    return;
                }

                if (item.requiere_cambio_lote && !item.cambio_lote_resuelto) {
                    if (exigeDespachoCompleto) {
                        error = `Resuelve el cambio de lote pendiente para ${item.codigo}.`;
                        mostrarAlertaEnTarjeta(itemIndex, error, 'error');
                    }
                    // Nota con despacho parcial permitido: este ítem se omite y queda pendiente
                    // para una sesión posterior; no bloquea el guardado del resto de la nota.
                    return;
                }

                const salidas = [];
                let totalAsignado = 0;

                item.ubicaciones.forEach((u, uIndex) => {
                    const input = $(`.sacar-input[data-item="${itemIndex}"][data-ubic="${uIndex}"]`);
                    const cantidad = parseInt(input.val()) || 0;
                    if (cantidad <= 0) return;

                    if (cantidad > u.saldo) {
                        error = `El pallet ${u.pallet} (${item.codigo}) no tiene saldo suficiente.`;
                        mostrarAlertaEnTarjeta(itemIndex, error, 'error');
                        return;
                    }

                    totalAsignado += cantidad;
                    salidas.push({
                        pallet: u.pallet,
                        almacen: u.almacen,
                        galpon: u.galpon,
                        ubicacion: u.ubicacion,
                        cantidad
                    });
                });

                if (error) return;

                if (totalAsignado > item.cantidad_pendiente) {
                    error = `El código ${item.codigo} supera la cantidad autorizada (${item.cantidad_pendiente}).`;
                    mostrarAlertaEnTarjeta(itemIndex, error, 'error');
                    return;
                }

                if (exigeDespachoCompleto && totalAsignado < item.cantidad_pendiente) {
                    error =
                        `Esta nota requiere despacho completo: falta asignar ${item.cantidad_pendiente - totalAsignado} cajas de ${item.codigo}.`;
                    mostrarAlertaEnTarjeta(itemIndex, error, 'error');
                    return;
                }

                if (salidas.length > 0) {
                    lines.push({
                        codigo: item.codigo,
                        descrip: item.descrip,
                        descrip1: item.descrip1,
                        lote_declarado: item.clote,
                        clote: item.clote,
                        cantidad_despacho: item.cantidad_despacho,
                        salidas
                    });
                }
            });

            if (error) {
                // El error ya se mostró en la tarjeta correspondiente; no hacemos scroll global.
                return;
            }

            if (lines.length === 0) {
                mostrarAlerta('Ingresa al menos una cantidad a despachar.', 'error');
                return;
            }

            fetch(routeStore, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({
                        tipo_registro: notaSeleccionada.tipo_registro,
                        id_registro: notaSeleccionada.id,
                        fecha: notaSeleccionada.fecha,
                        glosa: notaSeleccionada.glosa,
                        lines
                    })
                })
                .then(async res => {
                    const data = await res.json();
                    if (!res.ok) {
                        mostrarAlerta(Object.values(data.errors ?? {}).flat().join(' '), 'error');
                        return;
                    }

                    let mensaje = data.message;
                    if (data.hay_excepciones && data.ticket_url) {
                        mensaje +=
                            ` <a href="${data.ticket_url}" target="_blank" class="underline font-semibold">Descargar Ticket de Variación de Lote (PDF)</a>`;
                    }

                    mostrarAlertaHtml(mensaje, 'success');
                    setTimeout(() => window.location.href = "{{ route('wms.index') }}", 3500);
                });
        }

        function mostrarAlertaHtml(html, tipo) {
            const box = $('#alertBox');
            box.removeClass('hidden bg-green-100 text-green-800 bg-red-100 text-red-800');
            box.addClass(tipo === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800');
            box.html(html);
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        }

        function mostrarAlerta(msg, tipo) {
            const box = $('#alertBox');
            box.removeClass('hidden bg-green-100 text-green-800 bg-red-100 text-red-800');
            box.addClass(tipo === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800');
            box.text(msg);
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        }

        /**
         * Muestra un mensaje de error/éxito DENTRO de la tarjeta del ítem correspondiente,
         * sin mover el scroll de la página. Incluye botón para cerrar manualmente.
         */
        function mostrarAlertaEnTarjeta(itemIndex, msg, tipo = 'error') {
            const card = $(`[data-item-card="${itemIndex}"]`);
            if (card.length === 0) {
                // Fallback: si no encontramos la tarjeta (caso raro), usamos la alerta global
                mostrarAlerta(msg, tipo);
                return;
            }

            // Quita cualquier alerta previa de esta misma tarjeta antes de mostrar la nueva
            card.find('.alerta-tarjeta').remove();

            const colorClases = tipo === 'success' ?
                'bg-green-100 text-green-800 border-green-300' :
                'bg-red-100 text-red-800 border-red-300';

            const alerta = $(`
                <div class="alerta-tarjeta border ${colorClases} rounded-lg px-3 py-2 text-xs mb-2 flex items-start justify-between gap-2">
                    <span>${msg}</span>
                    <button type="button" class="btnCerrarAlertaTarjeta shrink-0 font-bold leading-none">&times;</button>
                </div>
            `);

            alerta.find('.btnCerrarAlertaTarjeta').on('click', function() {
                alerta.remove();
            });

            card.prepend(alerta);

            // Auto-cierre a los 6 segundos si el usuario no la cierra manualmente
            setTimeout(() => alerta.fadeOut(300, function() {
                $(this).remove();
            }), 6000);
        }

        /*
         * NOTA: la función renderPallesLoteSustituto() del flujo antiguo de "un solo
         * lote sustituto" fue reemplazada por renderPanelManual()/iniciarModoAutomatico()
         * (soporte de múltiples lotes). Se removió por ser código muerto: ya no la
         * invoca ningún manejador de eventos en este archivo.
         */
    </script>
</x-app-layout>
