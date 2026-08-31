<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">WMS - Ingreso sin Nota (Ajuste)</h2>
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

        .row-input {
            font-size: 16px;
        }
    </style>

    <div class="py-4 px-3 sm:py-6 sm:px-4">
        <div class="max-w-3xl mx-auto">
            <a href="{{ route('wms.index') }}" class="text-sm text-gray-600 mb-3 inline-flex items-center gap-1">&larr;
                Volver</a>

            <div class="bg-orange-50 border border-orange-200 text-orange-800 text-sm rounded-xl p-3 mb-4">
                ⚠️ Usa esta opción para registrar stock físico existente sin nota de ingreso. Arma el pallet completo
                antes de presionar FINALIZAR.
            </div>

            <div id="alertBox" class="hidden mb-4 p-3 rounded-lg text-sm"></div>

            {{-- Motivo --}}
            <div class="bg-white shadow rounded-xl p-4 sm:p-5 mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Motivo del Ajuste <span
                        class="text-red-500">*</span></label>
                <textarea id="motivo" rows="2" class="w-full border-gray-300 rounded-lg p-2.5 text-base"
                    placeholder="Ej: Saldo inicial - producción gestión 2024"></textarea>
            </div>

            {{-- Selector de modo --}}
            <div class="grid grid-cols-2 gap-2 mb-4">
                <button id="tabMixto"
                    class="modo-btn py-3 rounded-xl font-semibold text-sm bg-gray-800 text-white">PALLET MIXTO</button>
                <button id="tabCompleto"
                    class="modo-btn py-3 rounded-xl font-semibold text-sm bg-gray-200 text-gray-700">PALLET
                    COMPLETO</button>
            </div>

            {{-- MODO MIXTO --}}
            <div id="panelMixto" class="space-y-4">
                <div class="bg-white shadow rounded-xl p-4 space-y-3">
                    <label class="block text-sm font-medium text-gray-700">Formato del Pallet</label>
                    <select id="selectFormato" class="w-full" style="width:100%"></select>
                    <p id="limiteFormatoTxt"
                        class="hidden text-xs font-medium text-blue-700 bg-blue-50 rounded-lg px-3 py-2"></p>
                </div>

                {{-- Dentro de #panelMixto, después de #limiteFormatoTxt --}}
                {{-- <p id="palletReservadoMixtoTxt"
                    class="hidden text-sm font-bold text-white bg-blue-700 rounded-lg px-3 py-2 text-center"></p>
                - --}}
                <div id="mixtoUbicacion" class="hidden bg-white shadow rounded-xl p-4 grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-[11px] font-medium text-gray-500 mb-1">Galpón</label>
                        <input type="text" id="galponMixto" class="row-input w-full border-gray-300 rounded-lg p-2.5"
                            placeholder="Galpón">
                    </div>
                    <div>
                        <label class="block text-[11px] font-medium text-gray-500 mb-1">Ubicación</label>
                        <input type="text" id="ubicacionMixto"
                            class="row-input w-full border-gray-300 rounded-lg p-2.5" placeholder="Ubicación">
                    </div>
                </div>

                <div id="mixtoAgregar" class="hidden bg-white shadow rounded-xl p-4 space-y-3">
                    <label class="block text-sm font-medium text-gray-700">Agregar Producto</label>
                    <select id="selectProductoMixto" class="w-full" style="width:100%"></select>
                    <div class="grid grid-cols-2 gap-2">
                        <input type="text" id="cloteMixto" class="row-input border-gray-300 rounded-lg p-2.5"
                            placeholder="Lote">
                        <input type="number" id="cantidadMixto" min="1"
                            class="row-input border-gray-300 rounded-lg p-2.5" placeholder="Cantidad">
                    </div>
                    <button id="btnAgregarMixto"
                        class="w-full bg-gray-800 text-white font-semibold py-2.5 rounded-lg text-sm">+ Agregar a este
                        pallet</button>
                </div>

                <div id="mixtoResumen"
                    class="hidden bg-white border-2 border-blue-200 rounded-xl p-3 flex justify-between items-center">
                    <span class="text-sm font-medium text-gray-600">Total en el pallet</span>
                    <span id="mixtoTotalTxt" class="text-lg font-bold text-blue-700">0 / 0</span>
                </div>

                <div id="mixtoItems" class="space-y-2"></div>

                <button id="btnFinalizarMixto"
                    class="hidden w-full bg-blue-600 text-white font-semibold py-4 rounded-xl shadow text-lg">FINALIZAR
                    PALLET</button>
            </div>

            {{-- MODO COMPLETO --}}
            <div id="panelCompleto" class="space-y-4 hidden">
                <div class="bg-white shadow rounded-xl p-4 space-y-3">
                    <label class="block text-sm font-medium text-gray-700">Producto</label>
                    <select id="selectProductoCompleto" class="w-full" style="width:100%"></select>
                    <p id="limiteCompletoTxt" class="hidden text-xs font-medium rounded-lg px-3 py-2"></p>
                </div>

                {{-- Dentro de #panelCompleto, después de #limiteCompletoTxt --}}
                {{--  <p id="palletReservadoCompletoTxt"
                    class="hidden text-sm font-bold text-white bg-blue-700 rounded-lg px-3 py-2 text-center"></p>
                 --}}

                <div id="completoDatos" class="hidden bg-white shadow rounded-xl p-4 space-y-3">
                    <div class="grid grid-cols-2 gap-2">
                        <input type="text" id="cloteCompleto" class="row-input border-gray-300 rounded-lg p-2.5"
                            placeholder="Lote">
                        <input type="number" id="cantidadCompleto" min="1"
                            class="row-input border-gray-300 rounded-lg p-2.5" placeholder="Cantidad">
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <input type="text" id="galponCompleto" class="row-input border-gray-300 rounded-lg p-2.5"
                            placeholder="Galpón">
                        <input type="text" id="ubicacionCompleto" class="row-input border-gray-300 rounded-lg p-2.5"
                            placeholder="Ubicación">
                    </div>
                </div>

                <button id="btnFinalizarCompleto"
                    class="hidden w-full bg-blue-600 text-white font-semibold py-4 rounded-xl shadow text-lg">FINALIZAR
                    PALLET</button>
            </div>

        </div>
    </div>

    <script>
        const routeBuscarFormatos = "{{ route('wms.ingresos.ajuste.formatos.buscar') }}";
        const routeBuscarProductos = "{{ route('wms.ingresos.ajuste.productos.buscar') }}";
        const routeLimite = (c) => `{{ url('wms/ingresos-ajuste/producto') }}/${encodeURIComponent(c)}/limite`;
        const routeStore = "{{ route('wms.ingresos.ajuste.store') }}";
        const csrfToken = "{{ csrf_token() }}";
        
        let formatoActual = null; // { codigo, cajas_x_pallet }
        let productoMixtoSel = null;
        let itemsMixto = [];

        let productoCompletoSel = null;
        let limiteCompleto = null;

        $(document).ready(function() {
            // --- Tabs ---
            $('#tabMixto').on('click', () => cambiarModo('mixto'));
            $('#tabCompleto').on('click', () => cambiarModo('completo'));

            // --- Select2 Formato (MIXTO) ---
            $('#selectFormato').select2({
                placeholder: 'Selecciona el formato...',
                minimumInputLength: 0,
                width: '100%',
                ajax: {
                    url: routeBuscarFormatos,
                    dataType: 'json',
                    delay: 300,
                    data: params => ({
                        q: params.term || ''
                    }),
                    processResults: data => ({
                        results: data.results
                    })
                }
            });

            $('#selectFormato').on('select2:select', function(e) {
                formatoActual = {
                    codigo: e.params.data.id,
                    cajas_x_pallet: e.params.data.cajas_x_pallet
                };
                itemsMixto = [];
                renderItemsMixto();

                $('#limiteFormatoTxt').removeClass('hidden').text(
                    `Límite del pallet: ${formatoActual.cajas_x_pallet} cajas`);
                $('#mixtoUbicacion, #mixtoAgregar, #mixtoResumen').removeClass('hidden');
                $('#btnFinalizarMixto').addClass('hidden');

                inicializarSelectProductoMixto();


            });

            $('#btnAgregarMixto').on('click', agregarItemMixto);
            $('#btnFinalizarMixto').on('click', finalizarMixto);

            // --- Select2 Producto (COMPLETO) ---
            $('#selectProductoCompleto').select2({
                placeholder: 'Busca por código o descripción...',
                minimumInputLength: 1,
                width: '100%',
                ajax: {
                    url: routeBuscarProductos,
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

            $('#selectProductoCompleto').on('select2:select', function(e) {
                productoCompletoSel = e.params.data;

                fetch(routeLimite(productoCompletoSel.id))
                    .then(res => res.json())
                    .then(data => {
                        const txt = $('#limiteCompletoTxt');
                        if (!data.encontrado) {
                            limiteCompleto = null;
                            txt.removeClass('hidden bg-blue-50 text-blue-700').addClass(
                                'bg-red-50 text-red-700').text(data.mensaje);
                            $('#completoDatos, #btnFinalizarCompleto, #palletReservadoCompletoTxt')
                                .addClass('hidden');
                            return;
                        }
                        limiteCompleto = data.cajas_x_pallet;
                        txt.removeClass('hidden bg-red-50 text-red-700').addClass(
                                'bg-blue-50 text-blue-700')
                            .text(`Formato ${data.formato_codigo} · Límite: ${limiteCompleto} cajas`);
                        $('#cantidadCompleto').attr('max', limiteCompleto);
                        $('#completoDatos, #btnFinalizarCompleto').removeClass('hidden');


                    });
            });

            $('#btnFinalizarCompleto').on('click', finalizarCompleto);
        });

        function cambiarModo(modo) {
            const esMixto = modo === 'mixto';
            $('#panelMixto').toggleClass('hidden', !esMixto);
            $('#panelCompleto').toggleClass('hidden', esMixto);
            $('#tabMixto').attr('class',
                `modo-btn py-3 rounded-xl font-semibold text-sm ${esMixto ? 'bg-gray-800 text-white' : 'bg-gray-200 text-gray-700'}`
            );
            $('#tabCompleto').attr('class',
                `modo-btn py-3 rounded-xl font-semibold text-sm ${!esMixto ? 'bg-gray-800 text-white' : 'bg-gray-200 text-gray-700'}`
            );
        }

        function inicializarSelectProductoMixto() {
            $('#selectProductoMixto').empty().val(null);
            $('#selectProductoMixto').select2({
                placeholder: `Producto (formato ${formatoActual.codigo})...`,
                minimumInputLength: 1,
                width: '100%',
                ajax: {
                    url: routeBuscarProductos,
                    dataType: 'json',
                    delay: 300,
                    data: params => ({
                        q: params.term,
                        formato: formatoActual.codigo
                    }),
                    processResults: data => ({
                        results: data.results
                    })
                }
            });
            $('#selectProductoMixto').on('select2:select', e => productoMixtoSel = e.params.data);
        }

        function agregarItemMixto() {
            const clote = $('#cloteMixto').val().trim();
            const cantidad = parseInt($('#cantidadMixto').val()) || 0;

            if (!productoMixtoSel) return mostrarAlerta('Selecciona un producto.', 'error');
            if (cantidad <= 0) return mostrarAlerta('Ingresa una cantidad válida.', 'error');

            const totalActual = itemsMixto.reduce((s, i) => s + i.cantidad, 0);
            if (totalActual + cantidad > formatoActual.cajas_x_pallet) {
                return mostrarAlerta(
                    `No puedes superar el límite del pallet (${formatoActual.cajas_x_pallet} cajas). Disponible: ${formatoActual.cajas_x_pallet - totalActual}.`,
                    'error');
            }

            itemsMixto.push({
                codigo: productoMixtoSel.id,
                descrip: productoMixtoSel.descrip,
                descrip1: productoMixtoSel.descrip1,
                clote,
                cantidad
            });

            renderItemsMixto();

            $('#selectProductoMixto').val(null).trigger('change');
            $('#cloteMixto, #cantidadMixto').val('');
            productoMixtoSel = null;
        }

        function renderItemsMixto() {
            const cont = $('#mixtoItems');
            cont.empty();

            itemsMixto.forEach((item, idx) => {
                cont.append(`
                    <div class="bg-white border border-gray-200 rounded-xl p-3 shadow-sm flex justify-between items-center">
                        <div>
                            <p class="font-mono font-semibold text-gray-800 text-sm">${item.codigo}</p>
                            <p class="text-xs text-gray-500">${item.descrip ?? ''} ${item.descrip1 ?? ''}</p>
                            <p class="text-xs text-gray-400">Lote: ${item.clote || 'S/L'}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-lg font-bold text-gray-800">${item.cantidad}</p>
                            <button data-idx="${idx}" class="btnQuitarMixto text-xs text-red-600">Quitar</button>
                        </div>
                    </div>
                `);
            });

            $('.btnQuitarMixto').on('click', function() {
                itemsMixto.splice($(this).data('idx'), 1);
                renderItemsMixto();
            });

            const total = itemsMixto.reduce((s, i) => s + i.cantidad, 0);
            $('#mixtoTotalTxt').text(`${total} / ${formatoActual.cajas_x_pallet}`)
                .toggleClass('text-red-600', total > formatoActual.cajas_x_pallet)
                .toggleClass('text-blue-700', total <= formatoActual.cajas_x_pallet);

            $('#btnFinalizarMixto').toggleClass('hidden', itemsMixto.length === 0);
        }

        function finalizarMixto() {
            const motivo = $('#motivo').val().trim();
            const galpon = $('#galponMixto').val().trim();
            const ubicacion = $('#ubicacionMixto').val().trim();

            if (!motivo) return mostrarAlerta('El motivo del ajuste es obligatorio.', 'error');
            if (!galpon || !ubicacion) return mostrarAlerta('Ingresa Galpón y Ubicación.', 'error');
            if (itemsMixto.length === 0) return mostrarAlerta('Agrega al menos un producto al pallet.', 'error');

            enviarPallet({
                motivo,
                tipo_pallet: 'mixto',
                formato_codigo: formatoActual.codigo,
                galpon,
                ubicacion,
                items: itemsMixto
            }, resetearMixto);
        }

        function resetearMixto() {
            itemsMixto = [];
            formatoActual = null;
            palletReservadoMixto = null;
            $('#selectFormato').val(null).trigger('change');
            $('#limiteFormatoTxt, #palletReservadoMixtoTxt, #mixtoUbicacion, #mixtoAgregar, #mixtoResumen, #btnFinalizarMixto')
                .addClass('hidden');
            $('#galponMixto, #ubicacionMixto, #cloteMixto, #cantidadMixto').val('');
            renderItemsMixto();
        }

        function finalizarCompleto() {
            const motivo = $('#motivo').val().trim();
            const clote = $('#cloteCompleto').val().trim();
            const cantidad = parseInt($('#cantidadCompleto').val()) || 0;
            const galpon = $('#galponCompleto').val().trim();
            const ubicacion = $('#ubicacionCompleto').val().trim();

            if (!motivo) return mostrarAlerta('El motivo del ajuste es obligatorio.', 'error');
            if (!productoCompletoSel || limiteCompleto === null) return mostrarAlerta('Selecciona un producto válido.',
                'error');
            if (cantidad <= 0) return mostrarAlerta('Ingresa una cantidad válida.', 'error');
            if (cantidad > limiteCompleto) return mostrarAlerta(
                `La cantidad supera el límite del pallet (${limiteCompleto} cajas).`, 'error');
            if (!galpon || !ubicacion) return mostrarAlerta('Ingresa Galpón y Ubicación.', 'error');

            enviarPallet({
                motivo,
                tipo_pallet: 'completo',
                galpon,
                ubicacion,
                items: [{
                    codigo: productoCompletoSel.id,
                    clote,
                    cantidad
                }]
            }, resetearCompleto);
        }

        function resetearCompleto() {
            productoCompletoSel = null;
            limiteCompleto = null;
            palletReservadoCompleto = null;
            $('#selectProductoCompleto').val(null).trigger('change');
            $('#limiteCompletoTxt, #palletReservadoCompletoTxt, #completoDatos, #btnFinalizarCompleto').addClass('hidden');
            $('#cloteCompleto, #cantidadCompleto, #galponCompleto, #ubicacionCompleto').val('');
        }

        function enviarPallet(payload, onSuccess) {
            fetch(routeStore, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify(payload)
            }).then(async res => {
                const data = await res.json();
                if (!res.ok) {
                    mostrarAlerta(Object.values(data.errors ?? {}).flat().join(' '), 'error');
                    return;
                }

                // Mensaje destacado con el número de pallet asignado
                const box = $('#alertBox');
                box.removeClass('hidden bg-red-100 text-red-800');
                box.addClass('bg-green-100 text-green-800 text-base font-semibold text-center py-4');
                box.html(
                    `✅ Pallet asignado:<br><span class="text-2xl">${data.pallet}</span><br><span class="text-xs font-normal">Ajuste: ${data.codigo_ajuste}</span>`
                    );
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });

                onSuccess();
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
    </script>
</x-app-layout>
