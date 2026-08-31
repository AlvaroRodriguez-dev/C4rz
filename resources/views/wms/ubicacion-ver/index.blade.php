<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">WMS - Ver Ubicación</h2>
    </x-slot>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-rc.0/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-rc.0/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>

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
                <label class="block text-sm font-medium text-gray-700 mb-2">Galpón / Ubicación</label>
                <div class="flex gap-2">
                    <select id="selectUbicacion" class="flex-1" style="width:100%"></select>
                    <button id="btnEscanear" type="button"
                        class="shrink-0 bg-gray-800 text-white rounded-xl px-4 flex items-center justify-center">
                        📷
                    </button>
                </div>
                <p class="text-xs text-gray-500 mt-2">Escribe, selecciona, o escanea el QR de la ubicación.</p>
            </div>

            <div id="qrReader" class="hidden mb-4 rounded-xl overflow-hidden border border-gray-200"></div>

            <div id="resultado" class="hidden space-y-3">
                <div class="bg-blue-600 text-white rounded-xl p-5 shadow text-center">
                    <p class="text-sm opacity-80" id="ubicacionTitulo"></p>
                    <div class="flex items-center justify-center gap-6 mt-2">
                        <div>
                            <p id="totalUbicacion" class="text-4xl font-bold">0</p>
                            <p class="text-xs opacity-80">cajas totales</p>
                        </div>
                        <div class="w-px h-10 bg-blue-400"></div>
                        <div>
                            <p id="totalPalletsUbicacion" class="text-4xl font-bold">0</p>
                            <p class="text-xs opacity-80">pallets</p>
                        </div>
                    </div>
                    <p class="text-xs opacity-80 mt-2">leído <span id="fechaLectura"></span></p>
                </div>

                <h3 class="font-semibold text-gray-700 px-1">Detalle por Pallet</h3>
                <div id="detallePorPallet" class="space-y-3"></div>
            </div>

            <div id="sinDatos" class="hidden text-center text-gray-500 mt-6">
                Esta ubicación no tiene saldo registrado.
            </div>
        </div>
    </div>

    <script>
        const routeBuscarUbicaciones = "{{ route('wms.ubicacion.ver.ubicaciones.buscar') }}";
        const routeContenido = "{{ url('wms/pallet-ver/ubicacion') }}"; // + /{galpon}/{ubicacion}
        let html5QrCode = null;

        $(document).ready(function() {
            $('#selectUbicacion').select2({
                placeholder: 'Escribe Galpón o Ubicación...',
                minimumInputLength: 1,
                width: '100%',
                ajax: {
                    url: routeBuscarUbicaciones,
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

            $('#selectUbicacion').on('select2:select', e =>
                cargarUbicacion(e.params.data.galpon, e.params.data.ubicacion)
            );

            $('#btnEscanear').on('click', toggleScanner);
        });

        function toggleScanner() {
            const reader = document.getElementById('qrReader');
            if (!reader.classList.contains('hidden')) {
                html5QrCode?.stop();
                reader.classList.add('hidden');
                return;
            }

            reader.classList.remove('hidden');
            html5QrCode = new Html5Qrcode("qrReader");
            html5QrCode.start({
                    facingMode: "environment"
                }, {
                    fps: 10,
                    qrbox: 250
                },
                (decodedText) => {
                    html5QrCode.stop();
                    reader.classList.add('hidden');

                    const [galpon, ubicacion] = decodedText.trim().split('|');
                    if (galpon && ubicacion) {
                        cargarUbicacion(galpon, ubicacion);
                    }
                }
            );
        }

        function cargarUbicacion(galpon, ubicacion) {
            $('#resultado').addClass('hidden');
            $('#sinDatos').addClass('hidden');
            $('#detallePorPallet').empty();

            fetch(`${routeContenido}/${encodeURIComponent(galpon)}/${encodeURIComponent(ubicacion)}`)
                .then(res => res.json())
                .then(data => {
                    if (data.total <= 0) {
                        $('#sinDatos').removeClass('hidden');
                        return;
                    }

                    $('#ubicacionTitulo').text(`GALPÓN ${data.galpon} · UBIC. ${data.ubicacion}`);
                    $('#totalUbicacion').text(data.total);
                    $('#totalPalletsUbicacion').text(data.total_pallets); // <-- nuevo
                    $('#fechaLectura').text(data.fecha_lectura);

                    // Agrupar items por pallet para el detalle
                    const porPallet = {};
                    data.items.forEach(item => {
                        if (!porPallet[item.pallet]) porPallet[item.pallet] = [];
                        porPallet[item.pallet].push(item);
                    });

                    Object.keys(porPallet).sort().forEach(pallet => {
                        const items = porPallet[pallet];
                        const totalPallet = items.reduce((sum, i) => sum + i.saldo, 0);

                        const detalleItems = items.map(i => `
                            <div class="flex justify-between text-sm py-1.5 border-t border-gray-100 first:border-0">
                                <div>
                                    <p class="font-mono text-gray-700">${i.codigo}</p>
                                    <p class="text-xs text-gray-500">${i.descrip ?? ''} ${i.descrip1 ?? ''}</p>
                                    <p class="text-xs text-gray-400">Lote: ${i.clote ?? 'S/L'}</p>
                                </div>
                                <span class="font-semibold text-gray-700 shrink-0">${i.saldo}</span>
                            </div>
                        `).join('');

                        $('#detallePorPallet').append(`
                            <div class="bg-white border border-gray-200 rounded-xl p-4 shadow-sm">
                                <div class="flex justify-between items-center mb-1">
                                    <p class="font-mono font-semibold text-blue-700">Pallet ${pallet}</p>
                                    <p class="text-lg font-bold text-gray-800">${totalPallet}</p>
                                </div>
                                ${detalleItems}
                            </div>
                        `);
                    });

                    $('#resultado').removeClass('hidden');
                });
        }
    </script>
</x-app-layout>
