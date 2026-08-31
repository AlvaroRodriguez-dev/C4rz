<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">WMS - Registrar Ingreso</h2>
    </x-slot>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-rc.0/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-rc.0/js/select2.min.js"></script>

    <style>
        .select2-container .select2-selection--single { height: 48px !important; display: flex; align-items: center; border-radius: 0.75rem !important; border-color: #d1d5db !important; }
        .select2-container--default .select2-selection--single .select2-selection__rendered { line-height: 48px !important; font-size: 16px; padding-left: 12px !important; }
        .row-input { font-size: 16px; }
    </style>

    <div class="py-4 px-3 sm:py-6 sm:px-4">
        <div class="max-w-3xl mx-auto">
            <a href="{{ route('wms.index') }}" class="text-sm text-gray-600 mb-3 inline-flex items-center gap-1">&larr; Volver</a>

            <div id="alertBox" class="hidden mb-4 p-3 rounded-lg text-sm"></div>

            <div class="bg-white shadow rounded-xl p-4 sm:p-5 mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Nota de Ingreso</label>
                <select id="selectNota" class="w-full" style="width:100%"></select>
                <p class="text-xs text-gray-500 mt-2">Busca por número de documento o nombre del proveedor.</p>
            </div>

            <p id="totalNotaTxt" class="hidden mb-4 text-sm font-semibold text-gray-700 bg-gray-100 rounded-lg px-3 py-2"></p>

            <div id="detalleWrapper" class="hidden">
                <div id="warningBox" class="hidden mb-4 p-3 bg-yellow-100 text-yellow-800 rounded-lg text-sm"></div>

                <div id="tablaDetalle" class="space-y-3 mb-24 sm:mb-5"></div>

                <div class="fixed sm:static bottom-0 left-0 right-0 bg-white sm:bg-transparent border-t sm:border-0 border-gray-200 p-3 sm:p-0 shadow-[0_-2px_8px_rgba(0,0,0,0.06)] sm:shadow-none z-20">
                    <div class="max-w-3xl mx-auto">
                        <button id="btnGuardar" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-4 rounded-xl shadow text-lg active:scale-[0.99] transition">
                            GUARDAR
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const routeBuscarNotas = "{{ route('wms.ingresos.notas.buscar') }}";
        const routeDetalleNota = "{{ url('wms/ingresos/notas') }}";
        const routeStore = "{{ route('wms.ingresos.store') }}";
        const csrfToken = "{{ csrf_token() }}";

        let notaSeleccionada = null;
        let grupos = []; // [{ id, items: [...], galpon, ubicacion }]

        $(document).ready(function () {
            $('#selectNota').select2({
                placeholder: 'Escribe para buscar una nota de ingreso...',
                minimumInputLength: 1,
                width: '100%',
                ajax: {
                    url: routeBuscarNotas, dataType: 'json', delay: 300,
                    data: params => ({ q: params.term }),
                    processResults: data => ({ results: data.results })
                }
            });

            $('#selectNota').on('select2:select', function (e) {
                notaSeleccionada = { rdocum: e.params.data.id, rfecha: e.params.data.rfecha };
                cargarDetalle(notaSeleccionada.rdocum);
            });

            $('#btnGuardar').on('click', guardarIngreso);
        });

        function cargarDetalle(rdocum) {
            $('#tablaDetalle').empty();
            $('#warningBox').addClass('hidden');
            $('#detalleWrapper').addClass('hidden');
            grupos = [];

            fetch(`${routeDetalleNota}/${encodeURIComponent(rdocum)}/detalle`)
                .then(res => res.json())
                .then(data => {
                    $('#totalNotaTxt').removeClass('hidden').text(`📦 Total de cajas en la nota: ${data.total_cajas_nota}`);

                    if (data.warning) {
                        $('#warningBox').removeClass('hidden').text(data.warning);
                    }

                    $('#completosInfo').remove();
                    const completos = data.items_info.filter(i => i.completo);
                    if (completos.length > 0) {
                        const lista = completos.map(i => `<br>${i.codigo} (Lote ${i.clote ?? 'S/L'})`).join(', ');
                        $('#detalleWrapper').prepend(`
                            <div id="completosInfo" class="border border-green-300 bg-green-50 rounded-xl p-3 text-sm text-green-700 mb-3">
                                ✅ Ya registrados completamente en WMS: ${lista}
                            </div>
                        `);
                    }

                    grupos = data.rows.map(row => ({ id: row.id, items: [row], galpon: '', ubicacion: '' }));

                    renderGrupos();
                    $('#detalleWrapper').removeClass('hidden');
                });
        }

        function totalGrupo(g) { return g.items.reduce((s, i) => s + i.cantidad, 0); }
        function formatoGrupo(g) { return g.items[0].config_codigo; }
        function limiteGrupo(g) { return g.items[0].limite; }
        function esSinConfig(g) { return g.items[0].sin_config; }
        function esCandidatoAMezcla(g) {
            if (esSinConfig(g)) return false;
            const limite = limiteGrupo(g);
            return limite ? totalGrupo(g) < limite : false;
        }

        function renderGrupos() {
            const cont = $('#tablaDetalle');
            cont.empty();

            grupos.forEach((grupo, idx) => {
                const localNum = idx + 1;
                const total = totalGrupo(grupo);
                const limite = limiteGrupo(grupo);
                const sinConfig = esSinConfig(grupo);
                const candidato = esCandidatoAMezcla(grupo);

                const itemsHtml = grupo.items.map(item => `
                    <div class="flex justify-between text-sm py-1 border-t border-gray-100 first:border-0">
                        <div>
                            <p class="font-mono text-gray-700">${item.codigo}</p>
                            <p class="text-xs text-gray-500">${item.descripcion}</p>
                            <p class="text-xs text-gray-400">Lote: ${item.clote ?? 'S/L'}</p>
                        </div>
                        <span class="font-semibold text-gray-700">${item.cantidad}</span>
                    </div>
                `).join('');

                const badge = sinConfig
                    ? '<span class="inline-block text-[11px] font-semibold text-orange-700 bg-orange-100 rounded-full px-2 py-0.5">SIN CONFIG.</span>'
                    : (candidato
                        ? '<span class="inline-block text-[11px] font-semibold text-blue-700 bg-blue-100 rounded-full px-2 py-0.5">SALDO</span>'
                        : '<span class="inline-block text-[11px] font-semibold text-gray-500 bg-gray-100 rounded-full px-2 py-0.5">PALLET COMPLETO</span>');

                let opcionesFusion = '';
                if (candidato) {
                    const otros = grupos
                        .map((g, i) => ({ g, i }))
                        .filter(({ g, i }) => i !== idx && esCandidatoAMezcla(g) && formatoGrupo(g) === formatoGrupo(grupo) && (totalGrupo(g) + total) <= limite);

                    if (otros.length > 0) {
                        const options = otros.map(({ g, i }) =>
                            `<option value="${i}">Pallet Local ${i + 1} (${totalGrupo(g)}/${limite})</option>`
                        ).join('');

                        opcionesFusion = `
                            <div class="mt-2 pt-2 border-t border-blue-100">
                                <label class="block text-[11px] font-medium text-gray-500 mb-1">Fusionar con otro pallet de saldo (mismo formato)</label>
                                <div class="flex gap-2">
                                    <select class="selectFusion flex-1 border-gray-300 rounded-lg text-sm p-2">
                                        <option value="">Selecciona...</option>
                                        ${options}
                                    </select>
                                    <button type="button" class="btnFusionar bg-blue-600 text-white text-xs font-semibold rounded-lg px-3" data-origen="${idx}">Fusionar</button>
                                </div>
                            </div>
                        `;
                    }
                }

                // Bloque "Copiar ubicación a otros pallets" (solo si hay más de un pallet)
                let opcionesCopiar = '';
                if (grupos.length > 1) {
                    const checkboxesHtml = grupos.map((g, i) => i !== idx ? `
                        <label class="flex items-center gap-2 text-sm text-gray-700">
                            <input type="checkbox" class="chkDestinoCopiar" data-origen="${idx}" value="${i}">
                            Pallet Local ${i + 1}
                        </label>
                    ` : '').join('');

                    opcionesCopiar = `
                        <div class="mt-2 pt-2 border-t border-gray-100">
                            <button type="button" data-origen="${idx}" class="btnMostrarCopiar text-xs text-blue-600 font-medium">
                                📋 Copiar esta ubicación a otros pallets
                            </button>
                            <div id="copiarPanel-${idx}" class="hidden mt-2 bg-gray-50 border border-gray-200 rounded-lg p-3 space-y-2">
                                <p class="text-[11px] font-medium text-gray-500">Selecciona los pallets destino:</p>
                                <div class="space-y-1">
                                    ${checkboxesHtml}
                                </div>
                                <div class="flex gap-2 pt-1">
                                    <button type="button" data-origen="${idx}" class="btnSeleccionarTodosCopiar text-[11px] text-blue-600 font-medium">Seleccionar todos</button>
                                    <button type="button" data-origen="${idx}" class="btnAplicarCopiar bg-blue-600 text-white text-xs font-semibold rounded-lg px-3 py-1.5 ml-auto">Aplicar</button>
                                </div>
                            </div>
                        </div>
                    `;
                }

                cont.append(`
                    <div class="border ${sinConfig ? 'border-orange-300 bg-orange-50' : (candidato ? 'border-blue-300 bg-blue-50' : 'border-gray-200 bg-white')} rounded-xl p-4 shadow-sm">
                        <div class="flex items-center justify-between mb-2">
                            <p class="font-semibold text-gray-800">Pallet Local ${localNum}</p>
                            ${badge}
                        </div>

                        ${itemsHtml}

                        <div class="flex justify-between items-center mt-2 pt-2 border-t border-gray-100">
                            <span class="text-xs text-gray-500">Total en este pallet</span>
                            <span class="font-bold text-gray-800">${total}${limite ? ` / ${limite}` : ''}</span>
                        </div>

                        ${opcionesFusion}

                        <div class="grid grid-cols-2 gap-2 mt-3">
                            <div>
                                <label class="block text-[11px] font-medium text-gray-500 mb-1">Galpón</label>
                                <input type="text" data-grupo="${idx}" data-field="galpon" value="${grupo.galpon}"
                                    class="row-input grupo-input w-full border-gray-300 rounded-lg p-2.5" placeholder="Galpón">
                            </div>
                            <div>
                                <label class="block text-[11px] font-medium text-gray-500 mb-1">Ubicación</label>
                                <input type="text" data-grupo="${idx}" data-field="ubicacion" value="${grupo.ubicacion}"
                                    class="row-input grupo-input w-full border-gray-300 rounded-lg p-2.5" placeholder="Ubicación">
                            </div>
                        </div>

                        ${opcionesCopiar}
                    </div>
                `);
            });

            $('.grupo-input').on('input', function () {
                grupos[$(this).data('grupo')][$(this).data('field')] = $(this).val();
            });

            $('.btnFusionar').on('click', function () {
                const origenIdx = parseInt($(this).data('origen'));
                const destinoIdx = parseInt($(this).closest('div.mt-2').find('.selectFusion').val());

                if (isNaN(destinoIdx)) return mostrarAlerta('Selecciona el pallet destino de la fusión.', 'error');
                if (origenIdx === destinoIdx) return mostrarAlerta('No puedes fusionar un pallet consigo mismo.', 'error');

                grupos[destinoIdx].items = grupos[destinoIdx].items.concat(grupos[origenIdx].items);
                if (!grupos[destinoIdx].galpon) grupos[destinoIdx].galpon = grupos[origenIdx].galpon;
                if (!grupos[destinoIdx].ubicacion) grupos[destinoIdx].ubicacion = grupos[origenIdx].ubicacion;

                grupos.splice(origenIdx, 1);
                renderGrupos();
            });

            // Mostrar/ocultar el panel de "copiar ubicación"
            $('.btnMostrarCopiar').on('click', function () {
                $(`#copiarPanel-${$(this).data('origen')}`).toggleClass('hidden');
            });

            // Marcar todos los checkboxes de destino de ese pallet
            $('.btnSeleccionarTodosCopiar').on('click', function () {
                const origen = $(this).data('origen');
                $(`.chkDestinoCopiar[data-origen="${origen}"]`).prop('checked', true);
            });

            // Aplicar la copia de Galpón/Ubicación a los pallets seleccionados
            $('.btnAplicarCopiar').on('click', function () {
                const origen = $(this).data('origen');
                const seleccionados = $(`.chkDestinoCopiar[data-origen="${origen}"]:checked`)
                    .map(function () { return parseInt($(this).val()); })
                    .get();

                if (seleccionados.length === 0) {
                    mostrarAlerta('Selecciona al menos un pallet destino.', 'error');
                    return;
                }

                const galponOrigen = grupos[origen].galpon;
                const ubicacionOrigen = grupos[origen].ubicacion;

                if (!galponOrigen || !ubicacionOrigen) {
                    mostrarAlerta('Completa Galpón y Ubicación en este pallet antes de copiarlo.', 'error');
                    return;
                }

                seleccionados.forEach(destinoIdx => {
                    grupos[destinoIdx].galpon = galponOrigen;
                    grupos[destinoIdx].ubicacion = ubicacionOrigen;
                });

                renderGrupos();
                mostrarAlerta(`Ubicación copiada a ${seleccionados.length} pallet(s).`, 'success');
            });
        }

        function guardarIngreso() {
            let error = false;
            grupos.forEach(g => { if (!g.galpon || !g.ubicacion) error = true; });

            if (error) return mostrarAlerta('Completa Galpón y Ubicación en todos los pallets.', 'error');

            const payload = {
                rdocum: notaSeleccionada.rdocum,
                rfecha: notaSeleccionada.rfecha,
                grupos: grupos.map(g => ({
                    galpon: g.galpon,
                    ubicacion: g.ubicacion,
                    items: g.items.map(i => ({
                        codigo: i.codigo, clote: i.clote,
                        descrip: i.descrip, descrip1: i.descrip1,
                        cantidad: i.cantidad
                    }))
                }))
            };

            fetch(routeStore, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                body: JSON.stringify(payload)
            }).then(async res => {
                const data = await res.json();
                if (!res.ok) {
                    mostrarAlerta(Object.values(data.errors ?? {}).flat().join(' '), 'error');
                    return;
                }

                mostrarResumenPallets(data.pallets);
                
            });
        }

        function mostrarResumenPallets(pallets) {
            const filas = pallets.map(p => `
                <div class="flex justify-between text-sm py-1 border-t border-green-100 first:border-0">
                    <span class="text-gray-700">Pallet Local ${String(p.local).padStart(2, '0')}</span>
                    <span class="font-mono font-bold text-green-700">${p.pallet}</span>
                </div>
            `).join('');

            const box = $('#alertBox');
            box.removeClass('hidden bg-red-100 text-red-800').addClass('bg-green-50 text-green-800 border border-green-300');
            box.html(`<p class="font-semibold mb-2">✅ Ingreso registrado correctamente. Pallets asignados:</p>${filas}`);
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        function mostrarAlerta(msg, tipo) {
            const box = $('#alertBox');
            box.removeClass('hidden bg-green-100 text-green-800 bg-red-100 text-red-800 border border-green-300');
            box.addClass(tipo === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800');
            box.text(msg);
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    </script>
</x-app-layout>