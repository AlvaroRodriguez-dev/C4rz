<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">WMS - Ver Pallet</h2>
    </x-slot>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-rc.0/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-rc.0/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>

    <style>
        .select2-container .select2-selection--single { height: 48px !important; display: flex; align-items: center; border-radius: 0.75rem !important; border-color: #d1d5db !important; }
        .select2-container--default .select2-selection--single .select2-selection__rendered { line-height: 48px !important; font-size: 16px; padding-left: 12px !important; }
    </style>

    <div class="py-4 px-3 sm:py-6 sm:px-4">
        <div class="max-w-3xl mx-auto">
            <a href="{{ route('wms.index') }}" class="text-sm text-gray-600 mb-3 inline-flex items-center gap-1">&larr; Volver</a>

            <div class="bg-white shadow rounded-xl p-4 sm:p-5 mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Número de Pallet</label>
                <div class="flex gap-2">
                    <select id="selectPallet" class="flex-1" style="width:100%"></select>
                    <button id="btnEscanear" type="button"
                        class="shrink-0 bg-gray-800 text-white rounded-xl px-4 flex items-center justify-center">
                        📷
                    </button>
                </div>
            </div>

            <div id="qrReader" class="hidden mb-4 rounded-xl overflow-hidden border border-gray-200"></div>

            <div id="resultado" class="hidden space-y-3">
                <div class="bg-blue-600 text-white rounded-xl p-5 shadow text-center">
                    <p class="text-sm opacity-80" id="palletTitulo"></p>
                    <p id="totalPallet" class="text-4xl font-bold mt-1">0</p>
                    <p class="text-xs opacity-80 mt-1">cajas totales · leído <span id="fechaLectura"></span></p>
                </div>
                <div id="detalle" class="space-y-3"></div>
            </div>

            <div id="sinDatos" class="hidden text-center text-gray-500 mt-6">
                Este pallet no tiene saldo registrado.
            </div>
        </div>
    </div>

    <script>
        const routeBuscar = "{{ route('wms.pallet.ver.pallets.buscar') }}";
        const routeContenido = "{{ url('wms/pallet-ver/pallet') }}";
        let html5QrCode = null;

        $(document).ready(function () {
            $('#selectPallet').select2({
                placeholder: 'Selecciona o escribe un pallet...',
                minimumInputLength: 1,
                width: '100%',
                ajax: {
                    url: routeBuscar, dataType: 'json', delay: 300,
                    data: params => ({ q: params.term }),
                    processResults: data => ({ results: data.results })
                }
            });

            $('#selectPallet').on('select2:select', e => cargarPallet(e.params.data.id));

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
            html5QrCode.start(
                { facingMode: "environment" },
                { fps: 10, qrbox: 250 },
                (decodedText) => {
                    html5QrCode.stop();
                    reader.classList.add('hidden');
                    cargarPallet(decodedText.trim());
                }
            );
        }

        function cargarPallet(pallet) {
            $('#resultado').addClass('hidden');
            $('#sinDatos').addClass('hidden');
            $('#detalle').empty();

            fetch(`${routeContenido}/${encodeURIComponent(pallet)}`)
                .then(res => res.json())
                .then(data => {
                    if (data.total <= 0) {
                        $('#sinDatos').removeClass('hidden');
                        return;
                    }

                    $('#palletTitulo').text(`PALLET ${data.pallet}`);
                    $('#totalPallet').text(data.total);
                    $('#fechaLectura').text(data.fecha_lectura);

                    data.items.forEach(item => {
                        $('#detalle').append(`
                            <div class="bg-white border border-gray-200 rounded-xl p-4 shadow-sm">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <p class="font-mono font-semibold text-gray-800 text-sm">${item.codigo}</p>
                                        <p class="text-sm text-gray-600">${item.descrip} ${item.descrip1 ?? ''}</p>
                                        <p class="text-xs text-gray-400 mt-0.5">Lote: ${item.clote ?? 'S/L'}</p>
                                    </div>
                                    <p class="text-xl font-bold text-blue-700">${item.saldo}</p>
                                </div>
                                <p class="text-xs text-gray-500 mt-2 border-t border-gray-100 pt-2">
                                    Galpón ${item.galpon} · Ubic. ${item.ubicacion}
                                </p>
                            </div>
                        `);
                    });

                    $('#resultado').removeClass('hidden');
                });
        }
    </script>
</x-app-layout>