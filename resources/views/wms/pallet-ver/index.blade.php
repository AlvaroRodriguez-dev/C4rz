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
                    <button id="btnEscanear" type="button" class="shrink-0 bg-gray-800 text-white rounded-xl px-4 flex items-center justify-center">📷</button>
                </div>
            </div>

            <div id="qrReader" class="hidden mb-2 rounded-xl overflow-hidden border border-gray-200"></div>

            <div id="qrZoomControl" class="hidden mb-3 bg-white border border-gray-200 rounded-xl px-4 py-3">
                <div class="flex items-center gap-3">
                    <span class="text-sm text-gray-600 shrink-0">🔍 Zoom</span>
                    <input id="qrZoom" type="range" min="1" max="1" step="0.1" value="1" class="w-full">
                    <span id="qrZoomValue" class="text-sm font-semibold text-gray-700 w-10 text-right">1×</span>
                </div>
            </div>

            <div id="qrDiagnostics" class="hidden mb-4 bg-gray-900 text-white rounded-xl p-4 text-xs font-mono">
                <div class="flex items-center justify-between mb-2">
                    <span class="font-bold text-sm">DIAGNÓSTICO DE CÁMARA</span>
                    <button type="button" id="btnCopiarDiagnostico" class="bg-gray-700 hover:bg-gray-600 rounded-lg px-2 py-1">Copiar</button>
                </div>
                <div id="qrDiagnosticsContent" class="space-y-1"></div>
                <p class="text-gray-400 mt-3">Estos datos permiten determinar si el límite está en resolución, zoom, enfoque o en el lector QR.</p>
            </div>

            <div id="resultado" class="hidden space-y-3">
                <div class="bg-blue-600 text-white rounded-xl p-5 shadow text-center">
                    <p class="text-sm opacity-80" id="palletTitulo"></p>
                    <p id="totalPallet" class="text-4xl font-bold mt-1">0</p>
                    <p class="text-xs opacity-80 mt-1">cajas totales · leído <span id="fechaLectura"></span></p>
                </div>
                <div id="detalle" class="space-y-3"></div>
            </div>

            <div id="sinDatos" class="hidden text-center text-gray-500 mt-6">Este pallet no tiene saldo registrado.</div>
        </div>
    </div>

    <script>
        const routeBuscar = "{{ route('wms.pallet.ver.pallets.buscar') }}";
        const routeContenido = "{{ url('wms/pallet-ver/pallet') }}";
        let html5QrCode = null;
        let qrDiagnosticData = {};

        $(document).ready(function () {
            $('#selectPallet').select2({
                placeholder: 'Selecciona o escribe un pallet...', minimumInputLength: 1, width: '100%',
                ajax: {
                    url: routeBuscar, dataType: 'json', delay: 300,
                    data: params => ({ q: params.term }),
                    processResults: data => ({ results: data.results })
                }
            });
            $('#selectPallet').on('select2:select', e => cargarPallet(e.params.data.id));
            $('#btnEscanear').on('click', toggleScanner);
            $('#qrZoom').on('input', function () { aplicarZoom(Number(this.value)); });
            $('#btnCopiarDiagnostico').on('click', copiarDiagnostico);
        });

        function ocultarZoom() { $('#qrZoomControl').addClass('hidden'); }

        function mostrarDiagnostico(data) {
            qrDiagnosticData = data;
            const filas = [
                ['Navegador', data.browser],
                ['Dispositivo', data.platform],
                ['Cámara', data.cameraLabel],
                ['Resolución real', `${data.width} × ${data.height}`],
                ['FPS real', data.frameRate],
                ['Zoom soportado', data.zoomSupported ? 'SÍ' : 'NO'],
                ['Zoom mínimo', data.zoomMin],
                ['Zoom máximo', data.zoomMax],
                ['Zoom actual', data.zoomCurrent],
                ['Enfoque', data.focusMode],
                ['Distancia de enfoque', data.focusDistance],
                ['Capacidades completas', JSON.stringify(data.capabilities)]
            ];
            $('#qrDiagnosticsContent').html(filas.map(([k, v]) => `<div><span class="text-gray-400">${k}:</span> ${escapeHtml(String(v ?? 'no disponible'))}</div>`).join(''));
            $('#qrDiagnostics').removeClass('hidden');
        }

        function escapeHtml(value) {
            return value.replace(/[&<>'"]/g, char => ({ '&':'&amp;', '<':'&lt;', '>':'&gt;', "'":'&#39;', '"':'&quot;' }[char]));
        }

        function recopilarDiagnostico() {
            const capabilities = html5QrCode.getRunningTrackCapabilities();
            const settings = html5QrCode.getRunningTrackSettings();
            const video = document.querySelector('#qrReader video');
            const zoom = capabilities.zoom;
            const focusModes = capabilities.focusMode;
            const focusDistance = capabilities.focusDistance;

            return {
                browser: navigator.userAgent,
                platform: navigator.platform || 'no disponible',
                cameraLabel: settings.deviceId ? (video?.srcObject?.getVideoTracks?.()[0]?.label || 'cámara trasera') : 'no disponible',
                width: settings.width || video?.videoWidth || 'no disponible',
                height: settings.height || video?.videoHeight || 'no disponible',
                frameRate: settings.frameRate || 'no disponible',
                zoomSupported: !!(zoom && zoom.max > zoom.min),
                zoomMin: zoom?.min ?? 'no disponible',
                zoomMax: zoom?.max ?? 'no disponible',
                zoomCurrent: settings.zoom ?? 'no disponible',
                focusMode: Array.isArray(focusModes) ? focusModes.join(', ') : (focusModes ?? 'no disponible'),
                focusDistance: focusDistance ? JSON.stringify(focusDistance) : 'no disponible',
                capabilities
            };
        }

        function configurarZoom() {
            try {
                const capabilities = html5QrCode.getRunningTrackCapabilities();
                const zoom = capabilities.zoom;
                const slider = document.getElementById('qrZoom');
                const settings = html5QrCode.getRunningTrackSettings();

                if (zoom && zoom.max > zoom.min) {
                    slider.min = zoom.min;
                    slider.max = zoom.max;
                    slider.step = zoom.step || 0.1;
                    slider.value = settings.zoom ?? zoom.min;
                    actualizarTextoZoom(slider.value);
                    $('#qrZoomControl').removeClass('hidden');
                } else {
                    ocultarZoom();
                }

                mostrarDiagnostico(recopilarDiagnostico());
            } catch (error) {
                console.warn('No fue posible obtener las capacidades de la cámara.', error);
                mostrarDiagnostico({
                    browser: navigator.userAgent,
                    platform: navigator.platform || 'no disponible',
                    cameraLabel: 'ERROR',
                    width: 'no disponible',
                    height: 'no disponible',
                    frameRate: 'no disponible',
                    zoomSupported: false,
                    zoomMin: 'no disponible',
                    zoomMax: 'no disponible',
                    zoomCurrent: 'no disponible',
                    focusMode: 'no disponible',
                    focusDistance: 'no disponible',
                    capabilities: { error: String(error) }
                });
            }
        }

        function aplicarZoom(valor) {
            if (!html5QrCode) return;
            html5QrCode.applyVideoConstraints({ advanced: [{ zoom: valor }] })
                .then(() => {
                    actualizarTextoZoom(valor);
                    try { mostrarDiagnostico(recopilarDiagnostico()); } catch (e) {}
                })
                .catch(error => console.warn('No fue posible aplicar el zoom.', error));
        }

        function actualizarTextoZoom(valor) { document.getElementById('qrZoomValue').textContent = `${Number(valor).toFixed(1)}×`; }

        function copiarDiagnostico() {
            navigator.clipboard?.writeText(JSON.stringify(qrDiagnosticData, null, 2))
                .then(() => $('#btnCopiarDiagnostico').text('Copiado'))
                .catch(() => alert('No fue posible copiar el diagnóstico.'));
            setTimeout(() => $('#btnCopiarDiagnostico').text('Copiar'), 1500);
        }

        function toggleScanner() {
            const reader = document.getElementById('qrReader');
            if (!reader.classList.contains('hidden')) {
                html5QrCode?.stop();
                ocultarZoom();
                $('#qrDiagnostics').addClass('hidden');
                reader.classList.add('hidden');
                return;
            }
            reader.classList.remove('hidden');
            html5QrCode = new Html5Qrcode("qrReader");
            html5QrCode.start(
                { facingMode: "environment" },
                {
                    fps: 10,
                    qrbox: 250,
                    videoConstraints: {
                        facingMode: { ideal: "environment" },
                        width: { ideal: 1920, min: 1280 },
                        height: { ideal: 2560, min: 720 },
                        frameRate: { ideal: 30, min: 15 }
                    }
                },
                (decodedText) => {
                    html5QrCode.stop();
                    ocultarZoom();
                    $('#qrDiagnostics').addClass('hidden');
                    reader.classList.add('hidden');
                    cargarPallet(decodedText.trim());
                }
            ).then(() => configurarZoom()).catch(error => console.error('No fue posible iniciar la cámara.', error));
        }

        function cargarPallet(pallet) {
            $('#resultado').addClass('hidden');
            $('#sinDatos').addClass('hidden');
            $('#detalle').empty();
            fetch(`${routeContenido}/${encodeURIComponent(pallet)}`)
                .then(res => res.json())
                .then(data => {
                    if (data.total <= 0) { $('#sinDatos').removeClass('hidden'); return; }
                    $('#palletTitulo').text(`PALLET ${data.pallet}`);
                    $('#totalPallet').text(data.total);
                    $('#fechaLectura').text(data.fecha_lectura);
                    data.items.forEach(item => {
                        $('#detalle').append(`
                            <div class="bg-white border border-gray-200 rounded-xl p-4 shadow-sm">
                                <div class="flex justify-between items-start"><div>
                                    <p class="font-mono font-semibold text-gray-800 text-sm">${item.codigo}</p>
                                    <p class="text-sm text-gray-600">${item.descrip} ${item.descrip1 ?? ''}</p>
                                    <p class="text-xs text-gray-400 mt-0.5">Lote: ${item.clote ?? 'S/L'}</p>
                                </div><p class="text-xl font-bold text-blue-700">${item.saldo}</p></div>
                                <p class="text-xs text-gray-500 mt-2 border-t border-gray-100 pt-2">Galpón ${item.galpon} · Ubic. ${item.ubicacion}</p>
                            </div>`);
                    });
                    $('#resultado').removeClass('hidden');
                });
        }
    </script>
</x-app-layout>