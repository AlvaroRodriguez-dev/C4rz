<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            WMS - Inventario
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
    </style>

    <div class="py-4 px-3 sm:py-6 sm:px-4">
        <div class="max-w-3xl mx-auto">

            <a href="{{ route('wms.index') }}" class="text-sm text-gray-600 mb-3 inline-flex items-center gap-1">&larr;
                Volver</a>

            <div class="bg-white shadow rounded-xl p-4 sm:p-5 mb-4">
                <label for="selectProducto" class="block text-sm font-medium text-gray-700 mb-2">Buscar por
                    Producto</label>

                <select id="selectProducto" class="w-full" style="width:100%"></select>
                <p class="text-xs text-gray-500 mt-2">Busca por código o descripción.</p>
            </div>


            <div id="resultado" class="hidden space-y-4">

                {{-- Total general --}}
                <div class="bg-blue-600 text-white rounded-xl p-5 shadow text-center">
                    <p class="text-sm opacity-80">TOTAL GENERAL</p>
                    <div class="flex items-center justify-center gap-6 mt-2">
                        <div>
                            <p id="totalGeneral" class="text-4xl font-bold">0</p>
                            <p class="text-xs opacity-80">cajas</p>
                        </div>
                        <div class="w-px h-10 bg-blue-400"></div>
                        <div>
                            <p id="totalPalletsGeneral" class="text-4xl font-bold">0</p>
                            <p class="text-xs opacity-80">pallets</p>
                        </div>
                    </div>
                </div>

                {{-- Buscador (filtra en el navegador, sin nueva consulta al servidor) --}}
                <div class="bg-white shadow rounded-xl p-3">
                    <div class="relative">
                        <input type="text" id="buscadorInventario"
                            class="w-full border-gray-300 rounded-xl p-3 pl-10 text-base"
                            placeholder="Filtrar por pallet, galpón, ubicación o lote...">
                        <svg class="w-5 h-5 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none"
                            stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z" />
                        </svg>
                    </div>
                    <p id="contadorFiltro" class="text-xs text-gray-500 mt-2"></p>
                </div>

                {{-- Detalle por Pallet --}}
                <div>
                    <h3 class="font-semibold text-gray-700 mb-2 px-1">Detalle por Pallet</h3>
                    <div id="detallePallet" class="space-y-3"></div>
                    <p id="sinResultadosPallet" class="hidden text-sm text-gray-400 text-center py-3">Sin
                        coincidencias.</p>
                </div>

                {{-- Detalle por Ubicación --}}
                <div>
                    <h3 class="font-semibold text-gray-700 mb-2 px-1">Detalle por Ubicación</h3>
                    <div id="detalleUbicacion" class="space-y-3"></div>
                    <p id="sinResultadosUbicacion" class="hidden text-sm text-gray-400 text-center py-3">Sin
                        coincidencias.</p>
                </div>

            </div>



            <div id="sinDatos" class="hidden text-center text-gray-500 mt-6">
                Este producto no tiene saldo disponible actualmente.
            </div>

        </div>
    </div>

    <script>
        const routeBuscarProductos = "{{ route('wms.inventario.productos.buscar') }}";
        const routeSaldos = "{{ url('wms/inventario') }}"; // + /{codigo}/saldos

        let inventarioData = {
            por_pallet: [],
            por_ubicacion: []
        };

        $(document).ready(function() {
            $('#selectProducto').select2({
                placeholder: 'Escribe código o descripción...',
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

            $('#selectProducto').on('select2:select', function(e) {
                cargarSaldos(e.params.data.id);
            });

            $('#buscadorInventario').on('input', function() {
                renderInventarioFiltrado($(this).val().trim().toLowerCase());
            });
        });

        function cargarSaldos(codigo) {
            $('#resultado').addClass('hidden');
            $('#sinDatos').addClass('hidden');
            $('#buscadorInventario').val('');

            fetch(`${routeSaldos}/${encodeURIComponent(codigo)}/saldos`)
                .then(res => res.json())
                .then(data => {
                    if (data.total_general <= 0) {
                        $('#sinDatos').removeClass('hidden');
                        return;
                    }

                    $('#totalGeneral').text(data.total_general);
                    $('#totalPalletsGeneral').text(data.total_pallets); // <-- nuevo

                    inventarioData = {
                        por_pallet: data.por_pallet,
                        por_ubicacion: data.por_ubicacion
                    };

                    renderInventarioFiltrado('');
                    $('#resultado').removeClass('hidden');
                });
        }

        /**
         * Renderiza el detalle por Pallet y por Ubicación aplicando un filtro de texto libre
         * (sin consultar al servidor). Coincide si el texto aparece en pallet, galpón,
         * ubicación, o en cualquiera de los lotes contenidos.
         */
        function renderInventarioFiltrado(texto) {
            const coincideTexto = (valor) => (valor ?? '').toString().toLowerCase().includes(texto);

            const coincidePallet = (p) =>
                coincideTexto(p.pallet) || coincideTexto(p.galpon) || coincideTexto(p.ubicacion) ||
                p.lotes.some(l => coincideTexto(l.clote));

            const coincideUbicacion = (u) =>
                coincideTexto(u.galpon) || coincideTexto(u.ubicacion) ||
                u.lotes.some(l => coincideTexto(l.clote));

            const palletsFiltrados = texto === '' ? inventarioData.por_pallet : inventarioData.por_pallet.filter(
                coincidePallet);
            const ubicacionesFiltradas = texto === '' ? inventarioData.por_ubicacion : inventarioData.por_ubicacion.filter(
                coincideUbicacion);

            // --- Detalle por Pallet ---
            $('#detallePallet').empty();
            palletsFiltrados.forEach(p => {
                const lotesHtml = p.lotes.map(l => `
            <div class="flex justify-between text-sm py-1 border-t border-gray-100 first:border-0">
                <span class="text-gray-500">Lote: ${l.clote}</span>
                <span class="font-semibold text-gray-700">${l.total}</span>
            </div>
        `).join('');

                $('#detallePallet').append(`
            <div class="bg-white border border-gray-200 rounded-xl p-4 shadow-sm">
                <div class="flex justify-between items-center mb-1">
                    <p class="font-mono font-semibold text-gray-800">${p.pallet}</p>
                    <p class="text-lg font-bold text-blue-700">${p.total_pallet}</p>
                </div>
                <p class="text-xs text-gray-400 mb-2">Galpón ${p.galpon} · Ubic. ${p.ubicacion}</p>
                ${lotesHtml}
            </div>
        `);
            });
            $('#sinResultadosPallet').toggleClass('hidden', palletsFiltrados.length > 0);

            // --- Detalle por Ubicación ---
            $('#detalleUbicacion').empty();
            ubicacionesFiltradas.forEach(u => {
                const lotesHtml = u.lotes.map(l => `
            <div class="flex justify-between text-sm py-1 border-t border-gray-100 first:border-0">
                <span class="text-gray-500">Lote: ${l.clote}</span>
                <span class="font-semibold text-gray-700">${l.total}</span>
            </div>
        `).join('');

                $('#detalleUbicacion').append(`
            <div class="bg-white border border-gray-200 rounded-xl p-4 shadow-sm">
                <div class="flex justify-between items-center mb-1">
                    <p class="font-semibold text-gray-800">Galpón ${u.galpon} · Ubic. ${u.ubicacion}</p>
                    <p class="text-lg font-bold text-green-700">${u.total_ubicacion}</p>
                </div>
                ${lotesHtml}
            </div>
        `);
            });
            $('#sinResultadosUbicacion').toggleClass('hidden', ubicacionesFiltradas.length > 0);

            // --- Contador de resultados ---
            if (texto === '') {
                $('#contadorFiltro').text(
                    `${inventarioData.por_pallet.length} pallet(s) · ${inventarioData.por_ubicacion.length} ubicación(es)`
                );
            } else {
                $('#contadorFiltro').text(
                    `Filtrando "${texto}": ${palletsFiltrados.length} pallet(s) · ${ubicacionesFiltradas.length} ubicación(es)`
                );
            }
        }
    </script>
</x-app-layout>
