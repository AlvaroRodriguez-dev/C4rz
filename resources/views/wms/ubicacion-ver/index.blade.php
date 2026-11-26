<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">WMS - Ver Ubicación</h2>
    </x-slot>

    <script src="https://cdn.jsdelivr.net/npm/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>

    <div class="py-4 px-3 sm:py-6 sm:px-4">
        <div class="max-w-3xl mx-auto">
            <a href="{{ route('wms.index') }}" class="text-sm text-gray-600 mb-3 inline-flex items-center gap-1">&larr; Volver</a>

            <div class="bg-white shadow rounded-xl p-4 sm:p-5 mb-4">
                <label for="numeroUbicacion" class="block text-sm font-medium text-gray-700 mb-2">Galpón / Ubicación</label>
                <div class="flex gap-2">
                    <input type="text" id="numeroUbicacion" class="flex-1 min-w-0 border-gray-300 rounded-xl p-3 text-base" placeholder="Galpón|Ubicación (ej. 110|01)">
                    <button id="btnBuscarUbicacion" type="button" class="bg-blue-600 text-white rounded-xl px-4 font-semibold">BUSCAR</button>
                    <button id="btnEscanear" type="button" class="shrink-0 bg-gray-800 text-white rounded-xl px-4 flex items-center justify-center">📷</button>
                </div>
                <p class="text-xs text-gray-500 mt-2">Escribe el código Galpón|Ubicación o escanea el QR de la ubicación.</p>
            </div>

            <div id="alertUbicacion" class="hidden mb-3 p-3 rounded-lg text-sm bg-red-100 text-red-800"></div>
            <div id="modalScannerUbicacionVer" class="hidden fixed inset-0 z-50 bg-black bg-opacity-60 flex items-center justify-center p-4">
                <div class="bg-white rounded-xl p-4 w-full max-w-md">
                    <div class="flex justify-between items-center mb-3">
                        <h3 class="font-semibold">Escanear ubicación</h3>
                        <button type="button" id="cerrarScannerUbicacionVer" class="text-gray-600 text-xl">✕</button>
                    </div>
                    <div id="lectorScannerUbicacionVer" class="rounded-lg overflow-hidden"></div>
                    <p class="text-xs text-gray-500 mt-2">Apunta la cámara al código QR de la ubicación.</p>
                </div>
            </div>

            <div id="qrZoomControl" class="hidden mb-4 bg-white border border-gray-200 rounded-xl px-4 py-3">
                <div class="flex items-center gap-3">
                    <span class="text-sm text-gray-600 shrink-0">🔍 Zoom</span>
                    <input id="qrZoom" type="range" min="1" max="1" step="0.1" value="1" class="w-full">
                    <span id="qrZoomValue" class="text-sm font-semibold text-gray-700 w-10 text-right">1×</span>
                </div>
            </div>

            <div id="resultado" class="hidden space-y-3">
                <div class="bg-blue-600 text-white rounded-xl p-5 shadow text-center">
                    <p class="text-sm opacity-80" id="ubicacionTitulo"></p>
                    <div class="flex items-center justify-center gap-6 mt-2">
                        <div><p id="totalUbicacion" class="text-4xl font-bold">0</p><p class="text-xs opacity-80">cajas totales</p></div>
                        <div class="w-px h-10 bg-blue-400"></div>
                        <div><p id="totalPalletsUbicacion" class="text-4xl font-bold">0</p><p class="text-xs opacity-80">pallets</p></div>
                    </div>
                    <p class="text-xs opacity-80 mt-2">leído <span id="fechaLectura"></span></p>
                </div>
                <h3 class="font-semibold text-gray-700 px-1">Detalle por Pallet</h3>
                <div id="detallePorPallet" class="space-y-3"></div>
            </div>

            <div id="sinDatos" class="hidden text-center text-gray-500 mt-6">Esta ubicación no tiene saldo registrado.</div>
        </div>
    </div>

    <script>
        const routeContenido = "{{ url('wms/pallet-ver/ubicacion') }}";
        let scannerUbicacionVer = null;

        document.getElementById('btnBuscarUbicacion').addEventListener('click', buscarUbicacion);
        document.getElementById('numeroUbicacion').addEventListener('keydown', e => {
            if (e.key === 'Enter') { e.preventDefault(); buscarUbicacion(); }
        });
        document.getElementById('btnEscanear').addEventListener('click', abrirScanner);
        document.getElementById('cerrarScannerUbicacionVer').addEventListener('click', detenerScanner);
        document.getElementById('qrZoom').addEventListener('input', e => aplicarZoom(Number(e.target.value)));

        function buscarUbicacion(valor = document.getElementById('numeroUbicacion').value.trim()) {
            if (!valor) return;
            const partes = valor.split('|').map(p => p.trim());
            if (partes.length !== 2 || !partes[0] || !partes[1]) {
                mostrarAlerta('Ingresa el código en formato Galpón|Ubicación, por ejemplo 110|01.');
                return;
            }
            document.getElementById('numeroUbicacion').value = valor;
            cargarUbicacion(partes[0], partes[1]);
        }

        function ocultarZoom() { document.getElementById('qrZoomControl').classList.add('hidden'); }

        function configurarZoom() {
            try {
                const zoom = scannerUbicacionVer.getRunningTrackCapabilities().zoom;
                if (!zoom || zoom.max <= zoom.min) { ocultarZoom(); return; }
                const slider = document.getElementById('qrZoom');
                slider.min = zoom.min; slider.max = zoom.max; slider.step = zoom.step || 0.1;
                slider.value = scannerUbicacionVer.getRunningTrackSettings().zoom ?? zoom.min;
                document.getElementById('qrZoomValue').textContent = Number(slider.value).toFixed(1) + '×';
                document.getElementById('qrZoomControl').classList.remove('hidden');
            } catch (e) { ocultarZoom(); }
        }

        function aplicarZoom(valor) {
            if (!scannerUbicacionVer) return;
            scannerUbicacionVer.applyVideoConstraints({advanced:[{zoom:valor}]})
                .then(() => document.getElementById('qrZoomValue').textContent = Number(valor).toFixed(1)+'×')
                .catch(e => console.warn('No fue posible aplicar zoom.', e));
        }

        function abrirScanner() {
            document.getElementById('modalScannerUbicacionVer').classList.remove('hidden');
            scannerUbicacionVer = new Html5Qrcode('lectorScannerUbicacionVer');
            scannerUbicacionVer.start({facingMode:'environment'}, {fps:10, qrbox:250}, texto => {
                const valor = texto.trim();
                document.getElementById('numeroUbicacion').value = valor;
                detenerScanner();
                buscarUbicacion(valor);
            }).then(configurarZoom).catch(err => mostrarAlerta('No fue posible iniciar la cámara: '+err));
        }

        function detenerScanner() {
            const lector = scannerUbicacionVer;
            scannerUbicacionVer = null;
            document.getElementById('modalScannerUbicacionVer').classList.add('hidden');
            ocultarZoom();
            if (lector) Promise.resolve(lector.stop()).catch(()=>{}).then(()=>{try{lector.clear();}catch(e){}});
        }

        function mostrarAlerta(msg) {
            const box = document.getElementById('alertUbicacion');
            box.textContent = msg; box.classList.remove('hidden');
        }

        function cargarUbicacion(galpon, ubicacion) {
            document.getElementById('alertUbicacion').classList.add('hidden');
            document.getElementById('resultado').classList.add('hidden');
            document.getElementById('sinDatos').classList.add('hidden');
            document.getElementById('detallePorPallet').innerHTML = '';
            fetch(`${routeContenido}/${encodeURIComponent(galpon)}/${encodeURIComponent(ubicacion)}`)
                .then(res => { if(!res.ok) throw new Error('Consulta fallida'); return res.json(); })
                .then(data => {
                    if (data.total <= 0) { document.getElementById('sinDatos').classList.remove('hidden'); return; }
                    document.getElementById('ubicacionTitulo').textContent = `GALPÓN ${data.galpon} · UBIC. ${data.ubicacion}`;
                    document.getElementById('totalUbicacion').textContent = data.total;
                    document.getElementById('totalPalletsUbicacion').textContent = data.total_pallets;
                    document.getElementById('fechaLectura').textContent = data.fecha_lectura;
                    const porPallet = {};
                    data.items.forEach(item => { if (!porPallet[item.pallet]) porPallet[item.pallet] = []; porPallet[item.pallet].push(item); });
                    Object.keys(porPallet).sort().forEach(pallet => {
                        const items = porPallet[pallet];
                        const totalPallet = items.reduce((sum, i) => sum + Number(i.saldo || 0), 0);
                        const detalleItems = items.map(i => `
                            <div class="flex justify-between text-sm py-1.5 border-t border-gray-100 first:border-0">
                                <div><p class="font-mono text-gray-700">${i.codigo}</p><p class="text-xs text-gray-500">${i.descrip ?? ''} ${i.descrip1 ?? ''}</p><p class="text-xs text-gray-400">Lote: ${i.clote ?? 'S/L'}</p></div>
                                <span class="font-semibold text-gray-700 shrink-0">${i.saldo}</span>
                            </div>`).join('');
                        document.getElementById('detallePorPallet').insertAdjacentHTML('beforeend', `
                            <div class="bg-white border border-gray-200 rounded-xl p-4 shadow-sm">
                                <div class="flex justify-between items-center mb-1"><p class="font-mono font-semibold text-blue-700">Pallet ${pallet}</p><p class="text-lg font-bold text-gray-800">${totalPallet}</p></div>
                                ${detalleItems}
                            </div>`);
                    });
                    document.getElementById('resultado').classList.remove('hidden');
                }).catch(() => mostrarAlerta('No fue posible consultar la ubicación. Verifica el código e intenta nuevamente.'));
        }
    </script>
</x-app-layout>
