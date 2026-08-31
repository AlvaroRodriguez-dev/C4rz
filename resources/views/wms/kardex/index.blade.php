<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">WMS - Kardex</h2>
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

            {{-- Resultado --}}
            <div id="resultado" class="hidden space-y-3">
                <div class="grid grid-cols-3 gap-2">
                    <div class="bg-green-600 text-white rounded-xl p-3 text-center">
                        <p class="text-[11px] opacity-80">Entradas</p>
                        <p id="totalEntradas" class="text-lg font-bold">0</p>
                    </div>
                    <div class="bg-red-600 text-white rounded-xl p-3 text-center">
                        <p class="text-[11px] opacity-80">Salidas</p>
                        <p id="totalSalidas" class="text-lg font-bold">0</p>
                    </div>
                    <div class="bg-blue-600 text-white rounded-xl p-3 text-center">
                        <p class="text-[11px] opacity-80">Saldo Final</p>
                        <p id="saldoFinal" class="text-lg font-bold">0</p>
                    </div>
                </div>

                <div class="flex flex-wrap gap-3 text-[11px] text-gray-500 px-1">
                    <span><span
                            class="inline-block w-2.5 h-2.5 rounded-full bg-green-500 align-middle mr-1"></span>Entrada
                        real</span>
                    <span><span class="inline-block w-2.5 h-2.5 rounded-full bg-red-500 align-middle mr-1"></span>Salida
                        real</span>
                    <span><span
                            class="inline-block w-2.5 h-2.5 rounded-full bg-blue-500 align-middle mr-1"></span>Reubicación
                        (mismo stock)</span>
                    <span><span
                            class="inline-block w-2.5 h-2.5 rounded-full bg-orange-500 align-middle mr-1"></span>Ajuste
                        sin nota</span>
                </div>

                <div id="filas" class="space-y-2"></div>
            </div>

            <div id="sinDatos" class="hidden text-center text-gray-500 mt-6">
                No hay movimientos con los filtros seleccionados.
            </div>
        </div>
    </div>

    <script>
        const routeProductos = "{{ route('wms.inventario.productos.buscar') }}"; // reutilizado de Inventario
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

            $('#selectLote').select2({
                placeholder: 'Todos los lotes',
                width: '100%',
                allowClear: true
            });
            $('#selectGalpon').select2({
                placeholder: 'Todos los galpones',
                width: '100%',
                allowClear: true
            });
            $('#selectUbicacion').select2({
                placeholder: 'Todas las ubicaciones',
                width: '100%',
                allowClear: true
            });
            $('#selectPallet').select2({
                placeholder: 'Todos los pallets',
                width: '100%',
                allowClear: true
            });


            $('#selectProducto').on('select2:select', function(e) {
                codigoSeleccionado = e.params.data.id;
                resetearSelect('#selectLote', 'Todos los lotes');
                resetearSelect('#selectGalpon', 'Todos los galpones');
                resetearSelect('#selectUbicacion', 'Todas las ubicaciones');
                resetearSelect('#selectPallet', 'Todos los pallets'); // <-- nuevo

                cargarOpciones(routeLotes(codigoSeleccionado), '#selectLote');
                cargarOpciones(routeGalpones(codigoSeleccionado), '#selectGalpon');
                cargarOpciones(routeUbicaciones(codigoSeleccionado), '#selectUbicacion');
                cargarOpciones(routePallets(codigoSeleccionado),
                    '#selectPallet'); // <-- nuevo: lista TODOS los pallets del producto, sin importar saldo

                $('#selectLote, #selectGalpon, #selectUbicacion, #selectPallet').prop('disabled', false);
            });

            $('#selectLote').on('change', function() {
                const clote = $(this).val();
                cargarOpciones(routeGalpones(codigoSeleccionado) + (clote ?
                    `?clote=${encodeURIComponent(clote)}` : ''), '#selectGalpon');
                cargarOpciones(routeUbicaciones(codigoSeleccionado) + (clote ?
                    `?clote=${encodeURIComponent(clote)}` : ''), '#selectUbicacion');
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
            if ($('#selectPallet').val()) params.append('pallet', $('#selectPallet').val()); // <-- nuevo

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
            $('#totalEntradas').text(data.total_entradas);
            $('#totalSalidas').text(data.total_salidas);
            $('#saldoFinal').text(data.saldo_final);

            const cont = $('#filas');
            cont.empty();

            if (data.filas.length === 0) {
                $('#sinDatos').removeClass('hidden');
                $('#resultado').addClass('hidden');
                return;
            }

            data.filas.forEach(f => {
                const esSaldoInicial = f.tipo === 'SALDO INICIAL';
                const esAjuste = f.tipo === 'INGRESO (AJUSTE)';
                const esReubicacion = f.tipo.startsWith('REUBICACIÓN'); // <-- nuevo

                const colorTipo = esSaldoInicial ? 'bg-gray-100 text-gray-600' :
                    esReubicacion ? 'bg-blue-100 text-blue-700' // <-- nuevo
                    :
                    esAjuste ? 'bg-orange-100 text-orange-700' :
                    f.entrada ? 'bg-green-100 text-green-700' :
                    'bg-red-100 text-red-700';

                const colorMonto = esReubicacion ? 'text-blue-600' : (f.entrada ? 'text-green-600' :
                    'text-red-600');

                cont.append(`
        <div class="bg-white border border-gray-200 rounded-xl p-3 shadow-sm ${esSaldoInicial ? 'border-dashed' : ''}">
            <div class="flex justify-between items-start gap-2">
                <div class="min-w-0">
                    <span class="inline-block text-[10px] font-semibold px-2 py-0.5 rounded-full ${colorTipo}">${f.tipo}</span>
                    <p class="text-sm text-gray-700 mt-1 truncate">${f.documento}</p>
                    <p class="text-xs text-gray-400">${f.detalle_ubicacion}</p>
                    <p class="text-xs text-gray-400">Lote: <span class="font-medium text-gray-600">${f.clote}</span></p>
                    ${!esSaldoInicial ? `<p class="text-[11px] text-gray-400">${f.fecha} · ${f.usuario}</p>` : `<p class="text-[11px] text-gray-400">Al ${f.fecha}</p>`}
                </div>
                <div class="text-right shrink-0">
                    ${f.entrada ? `<p class="text-sm font-semibold ${colorMonto}">+${f.entrada}</p>` : ''}
                    ${f.salida ? `<p class="text-sm font-semibold ${colorMonto}">-${f.salida}</p>` : ''}
                    <p class="text-xs text-gray-400 mt-1">Saldo: <span class="font-bold text-gray-700">${f.saldo}</span></p>
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
