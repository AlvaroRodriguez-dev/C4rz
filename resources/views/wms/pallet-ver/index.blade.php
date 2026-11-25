<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">WMS - Ver Pallet</h2>
    </x-slot>

    <script src="https://cdn.jsdelivr.net/npm/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>


    <div class="py-4 px-3 sm:py-6 sm:px-4">
        <div class="max-w-3xl mx-auto">
            <a href="{{ route('wms.index') }}" class="text-sm text-gray-600 mb-3 inline-flex items-center gap-1">&larr; Volver</a>

            <div class="bg-white shadow rounded-xl p-4 sm:p-5 mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Número de Pallet</label>
                <div class="flex gap-2">
                    <input type="text" id="numeroPallet" class="flex-1 border-gray-300 rounded-xl p-3 text-base" placeholder="Escribe o escanea el número de pallet">
                    <button id="btnBuscarPallet" type="button" class="bg-blue-600 text-white rounded-xl px-4 font-semibold">BUSCAR</button>
                    <button id="btnEscanear" type="button" class="shrink-0 bg-gray-800 text-white rounded-xl px-4 flex items-center justify-center">📷</button>
                </div>
            </div>

            <div id="alertPallet" class="hidden mb-3 p-3 rounded-lg text-sm bg-red-100 text-red-800"></div>
            <div id="modalScannerPallet" class="hidden fixed inset-0 z-50 bg-black bg-opacity-60 flex items-center justify-center p-4"><div class="bg-white rounded-xl p-4 w-full max-w-md"><div class="flex justify-between items-center mb-3"><h3 class="font-semibold">Escanear pallet</h3><button type="button" id="cerrarScannerPallet" class="text-gray-600 text-xl">✕</button></div><div id="lectorScannerPallet" class="rounded-lg overflow-hidden"></div><p class="text-xs text-gray-500 mt-2">Apunta la cámara al código QR del pallet.</p></div></div>

            <div id="qrZoomControl" class="hidden mb-3 bg-white border border-gray-200 rounded-xl px-4 py-3">
                <div class="flex items-center gap-3">
                    <span class="text-sm text-gray-600 shrink-0">🔍 Zoom</span>
                    <input id="qrZoom" type="range" min="1" max="1" step="0.1" value="1" class="w-full">
                    <span id="qrZoomValue" class="text-sm font-semibold text-gray-700 w-10 text-right">1×</span>
                </div>
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
        const routeContenido = "{{ url('wms/pallet-ver/pallet') }}";
        let scannerPallet = null;
        document.getElementById('btnBuscarPallet').addEventListener('click', () => buscarPallet());
        document.getElementById('numeroPallet').addEventListener('keydown', e => { if(e.key==='Enter'){e.preventDefault();buscarPallet();} });
        document.getElementById('btnEscanear').addEventListener('click', abrirScanner);
        document.getElementById('cerrarScannerPallet').addEventListener('click', detenerScanner);
        document.getElementById('qrZoom').addEventListener('input',e=>aplicarZoom(Number(e.target.value)));
        function buscarPallet(valor=document.getElementById('numeroPallet').value.trim()){if(!valor)return;document.getElementById('numeroPallet').value=valor;cargarPallet(valor);}
        function ocultarZoom(){document.getElementById('qrZoomControl').classList.add('hidden');}
        function configurarZoom(){try{const z=scannerPallet.getRunningTrackCapabilities().zoom;if(!z||z.max<=z.min){ocultarZoom();return;}const s=document.getElementById('qrZoom');s.min=z.min;s.max=z.max;s.step=z.step||0.1;s.value=scannerPallet.getRunningTrackSettings().zoom??z.min;document.getElementById('qrZoomValue').textContent=Number(s.value).toFixed(1)+'×';document.getElementById('qrZoomControl').classList.remove('hidden');}catch(e){ocultarZoom();}}
        function aplicarZoom(v){if(!scannerPallet)return;scannerPallet.applyVideoConstraints({advanced:[{zoom:v}]}).then(()=>document.getElementById('qrZoomValue').textContent=Number(v).toFixed(1)+'×').catch(e=>console.warn('No fue posible aplicar zoom.',e));}
        function abrirScanner(){document.getElementById('modalScannerPallet').classList.remove('hidden');scannerPallet=new Html5Qrcode('lectorScannerPallet');scannerPallet.start({facingMode:'environment'},{fps:10,qrbox:250},texto=>{document.getElementById('numeroPallet').value=texto.trim();detenerScanner();buscarPallet(texto.trim());}).then(configurarZoom).catch(err=>mostrarAlertaPallet('No fue posible iniciar la cámara: '+err));}
        function detenerScanner(){const lector=scannerPallet;scannerPallet=null;document.getElementById('modalScannerPallet').classList.add('hidden');ocultarZoom();if(lector)Promise.resolve(lector.stop()).catch(()=>{}).then(()=>{try{lector.clear();}catch(e){}});}
        function mostrarAlertaPallet(msg){const box=document.getElementById('alertPallet');box.textContent=msg;box.classList.remove('hidden');}
        function cargarPallet(pallet){document.getElementById('alertPallet').classList.add('hidden');document.getElementById('resultado').classList.add('hidden');document.getElementById('sinDatos').classList.add('hidden');document.getElementById('detalle').innerHTML='';fetch(`${routeContenido}/${encodeURIComponent(pallet)}`).then(res=>res.json()).then(data=>{if(data.total<=0){document.getElementById('sinDatos').classList.remove('hidden');return;}document.getElementById('palletTitulo').textContent='PALLET '+data.pallet;document.getElementById('totalPallet').textContent=data.total;document.getElementById('fechaLectura').textContent=data.fecha_lectura;data.items.forEach(item=>{const card=document.createElement('div');card.className='bg-white border border-gray-200 rounded-xl p-4 shadow-sm';card.innerHTML='<div class="flex justify-between items-start"><div><p class="font-mono font-semibold text-gray-800 text-sm"></p><p class="text-sm text-gray-600"></p><p class="text-xs text-gray-400 mt-0.5"></p></div><p class="text-xl font-bold text-blue-700"></p></div><p class="text-xs text-gray-500 mt-2 border-t border-gray-100 pt-2"></p>';const ps=card.querySelectorAll('p');ps[0].textContent=item.codigo;ps[1].textContent=(item.descrip||'')+' '+(item.descrip1||'');ps[2].textContent='Lote: '+(item.clote??'S/L');ps[3].textContent=item.saldo;ps[4].textContent='Galpón '+item.galpon+' · Ubic. '+item.ubicacion;document.getElementById('detalle').appendChild(card);});document.getElementById('resultado').classList.remove('hidden');}).catch(()=>mostrarAlertaPallet('No fue posible consultar el pallet. Verifica el número e intenta nuevamente.'));}
    </script>
</x-app-layout>