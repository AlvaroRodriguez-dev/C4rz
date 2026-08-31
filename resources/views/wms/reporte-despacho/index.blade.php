<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">WMS - Reporte de Notas Despachadas</h2>
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
    </style>

    <div class="py-4 px-3 sm:py-6 sm:px-4">
        <div class="max-w-3xl mx-auto">
            <a href="{{ route('wms.index') }}" class="text-sm text-gray-600 mb-3 inline-flex items-center gap-1">&larr;
                Volver</a>

            <div class="bg-white shadow rounded-xl p-4 sm:p-5 mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Nota de Despacho</label>
                <select id="selectNota" class="w-full" style="width:100%"></select>
                <p class="text-xs text-gray-500 mt-2">Busca por número de nota o glosa.</p>
            </div>

            <div id="resultado" class="hidden space-y-4">
                <div class="grid grid-cols-2 gap-3">
                    <div class="bg-blue-600 text-white rounded-xl p-4 shadow text-center">
                        <p class="text-xs opacity-80">Cajas Despachadas</p>
                        <p id="totalGeneral" class="text-3xl font-bold">0</p>
                    </div>
                    <div class="bg-orange-500 text-white rounded-xl p-4 shadow text-center">
                        <p class="text-xs opacity-80">Cajas en OT (sin verificar)</p>
                        <p id="totalEnCamino" class="text-3xl font-bold">0</p>
                    </div>
                </div>

                <a id="btnPdf" href="#" target="_blank"
                    class="block text-center bg-white border border-blue-200 text-blue-700 font-semibold text-sm rounded-xl px-4 py-3 shadow-sm">
                    📄 Generar PDF
                </a>

                <div>
                    <h3 class="font-semibold text-gray-700 mb-2 px-1">Resumen por Producto</h3>
                    <div id="resumenProductos" class="space-y-2"></div>
                </div>

                <div>
                    <h3 class="font-semibold text-gray-700 mb-2 px-1">Detalle de Despachos (Realizados)</h3>
                    <div id="detalleDespachos" class="space-y-2"></div>
                </div>

                <div id="seccionEnCamino" class="hidden">
                    <h3 class="font-semibold text-gray-700 mb-2 px-1">En OT (Órdenes de Trabajo sin verificar)
                    </h3>
                    <div id="detalleEnCamino" class="space-y-2"></div>
                </div>
            </div>

            <div id="sinDatos" class="hidden text-center text-gray-500 mt-6">
                Esta nota no tiene despachos registrados en el WMS todavía.
            </div>
        </div>
    </div>

    <script>
        const routeBuscarNotas = "{{ route('wms.reporte.despacho.notas.buscar') }}";
        const routeDetalle = "{{ url('wms/reporte-despacho') }}"; // + /{tipo}/{id}/detalle
        const routePdf = "{{ url('wms/reporte-despacho') }}"; // + /{tipo}/{id}/pdf

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
                cargarReporte(e.params.data.tipo_registro, e.params.data.id);
            });
        });

        function cargarReporte(tipo, id) {
            $('#resultado').addClass('hidden');
            $('#sinDatos').addClass('hidden');

            fetch(`${routeDetalle}/${tipo}/${encodeURIComponent(id)}/detalle`)
                .then(res => res.json())
                .then(data => {
                    if (data.lineas.length === 0 && data.lineas_en_camino.length === 0) {
                        $('#sinDatos').removeClass('hidden');
                        return;
                    }

                    $('#totalGeneral').text(data.total_general);
                    $('#totalEnCamino').text(data.total_en_camino); // <-- nuevo
                    $('#btnPdf').attr('href', `${routePdf}/${tipo}/${encodeURIComponent(id)}/pdf`);

                    const resumen = $('#resumenProductos');
                    resumen.empty();
                    data.totales_por_producto.forEach(t => {
                        const badge = t.completo ?
                            '<span class="text-[10px] font-semibold text-green-700 bg-green-100 rounded-full px-2 py-0.5">COMPLETO</span>' :
                            '<span class="text-[10px] font-semibold text-orange-700 bg-orange-100 rounded-full px-2 py-0.5">PARCIAL</span>';
                        const excepcion = t.tuvo_excepcion ?
                            '<span class="text-[10px] font-semibold text-blue-700 bg-blue-100 rounded-full px-2 py-0.5 ml-1">CAMBIO DE LOTE</span>' :
                            '';
                        const enCaminoTxt = t.total_en_camino > 0 ?
                            `<p class="text-[11px] text-orange-600 mt-1">${t.total_en_camino} cajas en OT (sin verificar)</p>` :
                            '';

                        resumen.append(`
                    <div class="bg-white border border-gray-200 rounded-xl p-3 shadow-sm">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="font-mono font-semibold text-gray-800 text-sm">${t.codigo}</p>
                                <p class="text-xs text-gray-500">${t.descripcion}</p>
                                <div class="mt-1">${badge}${excepcion}</div>
                                ${enCaminoTxt}
                            </div>
                            <p class="text-lg font-bold text-gray-800 shrink-0">${t.total_despachado} <span class="text-xs text-gray-400 font-normal">/ ${t.total_autorizado}</span></p>
                        </div>
                    </div>
                `);
                    });

                    const detalle = $('#detalleDespachos');
                    detalle.empty();
                    data.lineas.forEach(l => {
                        const chipExcepcion = l.es_excepcion ?
                            `<span class="text-[10px] font-semibold text-blue-700 bg-blue-100 rounded-full px-2 py-0.5">CAMBIO DE LOTE</span>` :
                            '';
                        const lotesTxt = l.es_excepcion ?
                            `Solicitado: <span class="text-red-600 font-medium">${l.lote_solicitado}</span> → Aplicado: <span class="text-blue-600 font-medium">${l.lote_aplicado}</span>` :
                            `Lote: ${l.lote_aplicado}`;

                        detalle.append(`
                    <div class="bg-white border border-gray-200 rounded-xl p-3 shadow-sm">
                        <div class="flex justify-between items-start gap-2">
                            <div class="min-w-0">
                                <p class="font-mono font-semibold text-gray-800 text-sm">${l.codigo}</p>
                                <p class="text-xs text-gray-500">${l.descripcion}</p>
                                <p class="text-xs text-gray-400 mt-0.5">${lotesTxt}</p>
                                ${chipExcepcion}
                                <p class="text-xs text-gray-500 mt-1">Pallet <span class="font-mono">${l.pallet}</span> · Galpón ${l.galpon} · Ubic. ${l.ubicacion}</p>
                                <p class="text-[11px] text-gray-400 mt-1">${l.fecha} · ${l.usuario}</p>
                            </div>
                            <p class="text-lg font-bold text-gray-800 shrink-0">${l.cantidad}</p>
                        </div>
                    </div>
                `);
                    });

                    // --- NUEVO: sección "En Camino" ---
                    const seccionEnCamino = $('#seccionEnCamino');
                    const detalleEnCamino = $('#detalleEnCamino');
                    detalleEnCamino.empty();

                    if (data.lineas_en_camino.length > 0) {
                        data.lineas_en_camino.forEach(l => {
                            const chipExcepcion = l.es_excepcion ?
                                `<span class="text-[10px] font-semibold text-blue-700 bg-blue-100 rounded-full px-2 py-0.5">CAMBIO DE LOTE</span>` :
                                '';
                            const lotesTxt = l.es_excepcion ?
                                `Solicitado: <span class="text-red-600 font-medium">${l.lote_solicitado}</span> → Aplicado: <span class="text-blue-600 font-medium">${l.lote_aplicado}</span>` :
                                `Lote: ${l.lote_aplicado}`;

                            detalleEnCamino.append(`
                        <div class="bg-orange-50 border border-orange-200 rounded-xl p-3 shadow-sm">
                            <div class="flex justify-between items-start gap-2">
                                <div class="min-w-0">
                                    <p class="text-[11px] text-orange-500">OT #${l.orden_trabajo_id} · pendiente de verificación</p>
                                    <p class="font-mono font-semibold text-gray-800 text-sm">${l.codigo}</p>
                                    <p class="text-xs text-gray-500">${l.descripcion}</p>
                                    <p class="text-xs text-gray-400 mt-0.5">${lotesTxt}</p>
                                    ${chipExcepcion}
                                    <p class="text-xs text-gray-500 mt-1">Pallet <span class="font-mono">${l.pallet}</span> · Galpón ${l.galpon} · Ubic. ${l.ubicacion}</p>
                                    <p class="text-[11px] text-gray-400 mt-1">Generada: ${l.fecha}</p>
                                </div>
                                <p class="text-lg font-bold text-orange-700 shrink-0">${l.cantidad}</p>
                            </div>
                        </div>
                    `);
                        });
                        seccionEnCamino.removeClass('hidden');
                    } else {
                        seccionEnCamino.addClass('hidden');
                    }

                    $('#resultado').removeClass('hidden');
                });
        }
    </script>
</x-app-layout>
