<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">WMS - Kardex de Pallet</h2>
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
        <div class="max-w-4xl mx-auto">
            <a href="{{ route('wms.index') }}" class="text-sm text-gray-600 mb-3 inline-flex items-center gap-1">&larr;
                Volver</a>

            <div id="alertBox" class="hidden mb-4 p-3 rounded-lg text-sm"></div>

            {{-- Filtros --}}
            <div class="bg-white shadow rounded-xl p-4 sm:p-5 mb-4 space-y-4">

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Fecha Inicio</label>
                        <input type="date" id="fechaInicio"
                            class="w-full border-gray-300 rounded-lg p-2.5 text-base">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Fecha Fin</label>
                        <input type="date" id="fechaFin" class="w-full border-gray-300 rounded-lg p-2.5 text-base">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Producto</label>
                    <select id="selectProducto" class="w-full" style="width:100%"></select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Lote (opcional)</label>
                    <select id="selectLote" class="w-full" style="width:100%" disabled></select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Galpón (opcional)</label>
                    <select id="selectGalpon" class="w-full" style="width:100%" disabled></select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Ubicación (opcional)</label>
                    <select id="selectUbicacion" class="w-full" style="width:100%" disabled></select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Pallet (opcional)</label>
                    <select id="selectPallet" class="w-full" style="width:100%" disabled></select>
                </div>

                <button id="btnGenerar"
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3.5 rounded-xl shadow">
                    GENERAR KARDEX
                </button>
            </div>

            {{-- Resultado Rediseñado --}}
            <div id="resultado" class="hidden space-y-4">
                {{-- Bloques superiores informativos --}}
                <div class="grid grid-cols-3 gap-3">
                    <div class="bg-blue-600 text-white rounded-xl p-3 shadow-sm text-center">
                        <p class="text-[10px] uppercase tracking-wider font-semibold opacity-75">Entradas e Inv. Inicial</p>
                        <p id="totalEntradas" class="text-xl font-extrabold mt-0.5">0</p>
                        <p class="text-[9px] opacity-60 mt-0.5">Total pallets que entraron</p>
                    </div>
                    <div class="bg-red-600 text-white rounded-xl p-3 shadow-sm text-center">
                        <p class="text-[10px] uppercase tracking-wider font-semibold opacity-75">Fraccionamiento / Despacho</p>
                        <p id="totalSalidas" class="text-xl font-extrabold mt-0.5">0</p>
                        <p class="text-[9px] opacity-60 mt-0.5">Total cajas despachadas</p>
                    </div>
                    <div class="bg-emerald-600 text-white rounded-xl p-3 shadow-sm text-center">
                        <p class="text-[10px] uppercase tracking-wider font-semibold opacity-75">Saldo y Reubicación Actual</p>
                        <p id="saldoFinal" class="text-xl font-extrabold mt-0.5">0</p>
                        <p class="text-[9px] opacity-60 mt-0.5">Ubicación actual de saldos</p>
                    </div>
                </div>

                {{-- Contenedor de las filas de transacciones --}}
                <div id="filas" class="space-y-2.5"></div>
            </div>

            <div id="sinDatos" class="hidden text-center text-gray-500 mt-6">
                No hay movimientos con los filtros seleccionados.
            </div>
        </div>
    </div>

    <script>
        const routeProductos = "{{ route('wms.inventario.productos.buscar') }}";
        const routeLotes = (c) => `{{ url('wms/kardex/producto') }}/${encodeURIComponent(c)}/lotes`;
        const routeGalpones = (c) => `{{ url('wms/kardex/producto') }}/${encodeURIComponent(c)}/galpones`;
        const routeUbicaciones = (c) => `{{ url('wms/kardex/producto') }}/${encodeURIComponent(c)}/ubicaciones`;
        const routePallets = (c) => `{{ url('wms/kardex/producto') }}/${encodeURIComponent(c)}/pallets`;
        const routeReporte = "{{ route('wms.kardex.reporte') }}";

        let codigoSeleccionado = null;

        $(document).ready(function() {
            $('#selectProducto').select2({
                placeholder: 'Selecciona un producto...',
                minimumInputLength: 1,
                width: '100%',
                ajax: {
                    url: routeProductos,
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

            $('#selectLote').select2({ placeholder: 'Todos los lotes', width: '100%', allowClear: true });
            $('#selectGalpon').select2({ placeholder: 'Todos los galpones', width: '100%', allowClear: true });
            $('#selectUbicacion').select2({ placeholder: 'Todas las ubicaciones', width: '100%', allowClear: true });
            $('#selectPallet').select2({ placeholder: 'Todos los pallets', width: '100%', allowClear: true });

            $('#selectProducto').on('select2:select', function(e) {
                codigoSeleccionado = e.params.data.id;
                resetearSelect('#selectLote', 'Todos los lotes');
                resetearSelect('#selectGalpon', 'Todos los galpones');
                resetearSelect('#selectUbicacion', 'Todas las ubicaciones');
                resetearSelect('#selectPallet', 'Todos los pallets');

                cargarOpciones(routeLotes(codigoSeleccionado), '#selectLote');
                cargarOpciones(routeGalpones(codigoSeleccionado), '#selectGalpon');
                cargarOpciones(routeUbicaciones(codigoSeleccionado), '#selectUbicacion');
                cargarOpciones(routePallets(codigoSeleccionado), '#selectPallet');

                $('#selectLote, #selectGalpon, #selectUbicacion, #selectPallet').prop('disabled', false);
            });

            $('#selectLote').on('change', function() {
                const clote = $(this).val();
                cargarOpciones(routeGalpones(codigoSeleccionado) + (clote ? `?clote=${encodeURIComponent(clote)}` : ''), '#selectGalpon');
                cargarOpciones(routeUbicaciones(codigoSeleccionado) + (clote ? `?clote=${encodeURIComponent(clote)}` : ''), '#selectUbicacion');
            });

            $('#selectGalpon').on('change', function() {
                const clote = $('#selectLote').val();
                const galpon = $(this).val();
                let url = routeUbicaciones(codigoSeleccionado);
                const params = new URLSearchParams();
                if (clote) params.append('clote', clote);
                if (galpon) params.append('galpon', galpon);
                cargarOpciones(`${url}?${params.toString()}`, '#selectUbicacion');
            });

            $('#btnGenerar').on('click', generarKardex);
        });

        function resetearSelect(selector, placeholder) {
            $(selector).empty().val(null).trigger('change');
        }

        function cargarOpciones(url, selector) {
            fetch(url)
                .then(res => res.json())
                .then(data => {
                    const select = $(selector);
                    select.empty();
                    select.append('<option></option>');
                    data.results.forEach(r => {
                        select.append(new Option(r.text, r.id, false, false));
                    });
                    select.trigger('change');
                });
        }

        function generarKardex() {
            if (!codigoSeleccionado) {
                mostrarAlerta('Selecciona un producto.', 'error');
                return;
            }
            if (!$('#fechaInicio').val() || !$('#fechaFin').val()) {
                mostrarAlerta('Selecciona el periodo (fecha inicial y final).', 'error');
                return;
            }

            const params = new URLSearchParams({
                codigo: codigoSeleccionado,
                fecha_inicio: $('#fechaInicio').val(),
                fecha_fin: $('#fechaFin').val(),
            });

            if ($('#selectLote').val()) params.append('clote', $('#selectLote').val());
            if ($('#selectGalpon').val()) params.append('galpon', $('#selectGalpon').val());
            if ($('#selectUbicacion').val()) params.append('ubicacion', $('#selectUbicacion').val());
            if ($('#selectPallet').val()) params.append('pallet', $('#selectPallet').val());

            fetch(`${routeReporte}?${params.toString()}`)
                .then(async res => {
                    const data = await res.json();
                    if (!res.ok) {
                        mostrarAlerta(Object.values(data.errors ?? {}).flat().join(' '), 'error');
                        return;
                    }
                    renderResultado(data);
                });
        }

        function renderResultado(data) {
            // Sincronizar los indicadores superiores con la respuesta JSON del servidor
            // Tip: En el backend puedes mandar data.total_entradas (pallets), data.total_salidas (cajas), y data.saldo_final (cajas del remanente actual).
            $('#totalEntradas').text(data.total_entradas + (data.total_entradas == 1 ? ' Pallet' : ' Pallets'));
            $('#totalSalidas').text(data.total_salidas + ' Cajas');
            $('#saldoFinal').text(data.saldo_final + ' Cajas');

            const cont = $('#filas');
            cont.empty();

            if (data.filas.length === 0) {
                $('#sinDatos').removeClass('hidden');
                $('#resultado').addClass('hidden');
                return;
            }

            data.filas.forEach(f => {
                const tipoUpper = f.tipo.toUpperCase();
                
                // Determinar el contexto semántico del movimiento para asignar los colores ideales de Tailwind
                let badgeClass = 'bg-gray-100 text-gray-700'; // Default
                let borderClass = 'border-gray-200';

                if (tipoUpper === 'SALDO INICIAL') {
                    badgeClass = 'bg-gray-100 text-gray-600 border border-gray-300';
                    borderClass = 'border-dashed border-gray-300';
                } else if (tipoUpper === 'INGRESO' || tipoUpper === 'INGRESO (AJUSTE)') {
                    badgeClass = 'bg-green-100 text-green-700 font-bold';
                    borderClass = 'border-green-200';
                } else if (tipoUpper.includes('REUBICACIÓN') || tipoUpper.includes('TRASLADO')) {
                    // Trato intermedio y neutral (sin pérdidas/ganancias directas en inventario)
                    badgeClass = 'bg-blue-50 text-blue-700 border border-blue-200';
                    borderClass = 'border-blue-100';
                } else if (tipoUpper.includes('FRACCIONAMIENTO') || tipoUpper.includes('DESPACHO') || tipoUpper === 'SALIDA') {
                    badgeClass = 'bg-red-100 text-red-700 font-bold';
                    borderClass = 'border-red-200';
                } else if (tipoUpper.includes('RETORNO')) {
                    badgeClass = 'bg-emerald-100 text-emerald-800 font-bold';
                    borderClass = 'border-emerald-200';
                }

                // Generar los textos de adición o substracción según el flujo (Pallets vs Cajas)
                let cantidadHtml = '';
                if (f.entrada) {
                    const uniEntrada = tipoUpper.includes('INGRESO') || tipoUpper === 'SALDO INICIAL' ? 'Pallet' : 'Cajas';
                    cantidadHtml = `<p class="text-sm font-semibold text-green-600">+${f.entrada} ${uniEntrada}</p>`;
                }
                if (f.salida) {
                    const uniSalida = tipoUpper.includes('REUBICACIÓN') ? 'Pallet' : 'Cajas';
                    cantidadHtml += `<p class="text-sm font-semibold text-red-600">-${f.salida} ${uniSalida}</p>`;
                }

                // Asegurar que el saldo final por fila muestre de forma clara el tipo de unidad
                const unidadSaldo = (tipoUpper === 'SALDO INICIAL' || tipoUpper.includes('REUBICACIÓN')) ? 'Pallet' : 'Cajas';

                cont.append(`
                    <div class="bg-white border rounded-xl p-3.5 shadow-sm transition-all hover:shadow-md ${borderClass}">
                        <div class="flex justify-between items-start gap-2">
                            <div class="min-w-0">
                                <span class="inline-block text-[10px] font-bold tracking-wider px-2.5 py-0.5 rounded-full uppercase ${badgeClass}">
                                    ${f.tipo}
                                </span>
                                <p class="text-sm font-semibold text-gray-800 mt-1.5 truncate">${f.documento}</p>
                                <p class="text-xs text-gray-500 mt-0.5 flex items-center gap-1">
                                    <span class="font-medium text-gray-700">Ubicación:</span> ${f.detalle_ubicacion}
                                </p>
                                <p class="text-xs text-gray-500">Lote: <span class="font-semibold text-gray-700">${f.clote}</span></p>
                                ${tipoUpper !== 'SALDO INICIAL' 
                                    ? `<p class="text-[11px] text-gray-400 mt-1">${f.fecha} · <span class="text-gray-500 font-medium">${f.usuario}</span></p>` 
                                    : `<p class="text-[11px] text-gray-400 mt-1">Al ${f.fecha}</p>`
                                }
                            </div>
                            <div class="text-right shrink-0">
                                ${cantidadHtml}
                                <p class="text-xs text-gray-500 mt-1.5">Saldo: <span class="font-extrabold text-gray-800">${f.saldo} ${unidadSaldo}</span></p>
                            </div>
                        </div>
                    </div>
                `);
            });

            $('#sinDatos').addClass('hidden');
            $('#resultado').removeClass('hidden');
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
    </script>
</x-app-layout>