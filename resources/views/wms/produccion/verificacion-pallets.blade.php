<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">WMS - Verificación de Pallets</h2>
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
        <div class="max-w-6xl mx-auto">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
                <div>
                    <a href="{{ route('wms.produccion.liberacion.create') }}" class="text-sm text-gray-600">&larr; Volver a liberaciones</a>
                    <h1 class="text-2xl font-bold text-gray-900 mt-2">Verificación de pallets</h1>
                    <p class="text-sm text-gray-500">Busque o escanee cada pallet, confirme la cantidad física y complete la verificación de toda la liberación.</p>
                </div>
                <a href="{{ route('wms.produccion.liberacion.pallets', $entrega) }}" class="px-4 py-2 rounded-lg bg-gray-100 text-gray-700 font-semibold text-center">VER PALLETS</a>
            </div>

            <div id="alertBox" class="hidden rounded-lg p-3 mb-4 text-sm"></div>

            <div class="bg-white shadow rounded-xl p-4 sm:p-5 mb-5">
                <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-5">
                    <div><div class="text-xs text-gray-500">Documento</div><div class="font-mono font-semibold">{{ $entrega->documento?->id_documento ?? '—' }}</div></div>
                    <div><div class="text-xs text-gray-500">Origen</div><div>{{ $entrega->origen ?? '—' }}</div></div>
                    <div><div class="text-xs text-gray-500">Estado</div><div id="estadoEntrega" class="font-semibold">{{ $entrega->estado }}</div></div>
                    <div><div class="text-xs text-gray-500">Pallets</div><div class="font-bold text-lg">{{ $resumen['total'] }}</div></div>
                    <div><div class="text-xs text-gray-500">Progreso</div><div id="progreso" class="font-bold text-lg">{{ $resumen['verificados'] }} / {{ $resumen['total'] }}</div></div>
                </div>

                <div class="border-t pt-5">
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Buscar / escanear pallet</label>
                    <div class="flex gap-2">
                        <select id="selectPallet" class="flex-1" style="width:100%"></select>
                        <button id="btnEscanear" type="button" class="shrink-0 bg-gray-800 hover:bg-gray-900 text-white rounded-xl px-4 flex items-center justify-center" title="Escanear QR con la cámara">📷</button>
                    </div>
                    <p class="text-xs text-gray-500 mt-2">Puede escribir el número, seleccionarlo de la lista o usar la cámara del celular. En el lector HUGEROCK, el escaneo puede utilizarse como entrada de teclado.</p>
                </div>
            </div>

            <div id="qrReader" class="hidden mb-3 rounded-xl overflow-hidden border border-gray-200 bg-black"></div>

            <div id="qrZoomControl" class="hidden mb-4 bg-white border border-gray-200 rounded-xl px-4 py-3">
                <div class="flex items-center gap-3">
                    <span class="text-sm text-gray-600 shrink-0">🔍 Zoom</span>
                    <input id="qrZoom" type="range" min="1" max="1" step="0.1" value="1" class="w-full">
                    <span id="qrZoomValue" class="text-sm font-semibold text-gray-700 w-10 text-right">1×</span>
                </div>
            </div>

            <div id="palletPanel" class="hidden bg-white shadow rounded-xl overflow-hidden mb-5">
                <div class="p-4 border-b bg-gray-50 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                    <div>
                        <div class="text-xs text-gray-500">HU / PALLET</div>
                        <div id="palletNumero" class="font-mono text-2xl font-bold"></div>
                    </div>
                    <span id="palletResultado" class="px-3 py-1 rounded-full text-xs font-semibold"></span>
                </div>

                <div class="p-4 sm:p-5">
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-5">
                        <div><div class="text-xs text-gray-500">Formato</div><div id="palletFormato" class="font-semibold"></div></div>
                        <div><div class="text-xs text-gray-500">Tipo</div><div id="palletTipo"></div></div>
                        <div><div class="text-xs text-gray-500">Capacidad</div><div id="palletCapacidad"></div></div>
                        <div><div class="text-xs text-gray-500">Cantidad esperada</div><div id="cantidadEsperada" class="font-bold text-xl"></div></div>
                    </div>

                    <div class="border rounded-xl p-4 mb-5">
                        <div class="text-sm font-semibold text-gray-700 mb-2">Cantidad verificada</div>
                        <div class="flex flex-col sm:flex-row gap-3 items-stretch sm:items-center">
                            <input id="cantidadVerificada" type="number" min="0" step="1" class="w-full sm:w-56 border-gray-300 rounded-lg text-2xl font-bold text-center">
                            <div id="diferenciaBox" class="flex-1 rounded-lg p-3 text-sm bg-gray-50 text-gray-600">Ingrese la cantidad física encontrada.</div>
                        </div>
                    </div>

                    <div id="contenidoBox" class="border-t pt-4 mb-5"></div>

                    <div id="observacionBox" class="hidden mb-4">
                        <label for="observacion" class="block text-sm font-semibold text-gray-700 mb-1">Observación de la diferencia</label>
                        <textarea id="observacion" rows="2" maxlength="1000" class="w-full border-gray-300 rounded-lg" placeholder="Detalle breve de la diferencia encontrada..."></textarea>
                    </div>

                    <div class="flex flex-col sm:flex-row justify-end gap-2">
                        <button id="btnCancelar" type="button" class="px-5 py-2.5 rounded-lg bg-gray-100 text-gray-700 font-semibold">CANCELAR</button>
                        <button id="btnConfirmar" type="button" class="hidden px-5 py-2.5 rounded-lg bg-green-600 hover:bg-green-700 text-white font-semibold">CONFIRMAR</button>
                        <button id="btnConfirmarDiferencia" type="button" class="hidden px-5 py-2.5 rounded-lg bg-orange-600 hover:bg-orange-700 text-white font-semibold">CONFIRMAR CON DIFERENCIA</button>
                    </div>
                </div>
            </div>

            <div class="bg-white shadow rounded-xl p-4">
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <h2 class="font-bold text-gray-900">Estado de la verificación</h2>
                        <p class="text-xs text-gray-500">La liberación solo finalizará cuando todos los pallets tengan una confirmación.</p>
                    </div>
                    <span id="pendientes" class="px-3 py-1 rounded-full bg-yellow-100 text-yellow-800 text-xs font-semibold">{{ $resumen['pendientes'] }} pendientes</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2 mb-4"><div id="barraProgreso" class="bg-blue-600 h-2 rounded-full" style="width: {{ $resumen['total'] ? round(($resumen['verificados'] / $resumen['total']) * 100, 2) : 0 }}%"></div></div>
                <div class="text-xs text-gray-500">Con diferencia registrados: <span id="conDiferencia">{{ $resumen['con_diferencia'] }}</span></div>
            </div>
        </div>
    </div>

    <script>
        const baseUrl = @json(url('/wms/produccion-liberacion/' . $entrega->id . '/verificacion-pallets'));
        const routeBuscar = @json(route('wms.produccion.liberacion.verificacion.pallets.buscar', $entrega));
        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || @json(csrf_token());
        let palletActual = null;
        let html5QrCode = null;

        const $ = id => document.getElementById(id);

        $(document).ready(function () {
            $('#selectPallet').select2({
                placeholder: 'Selecciona o escribe un pallet...',
                minimumInputLength: 1,
                width: '100%',
                ajax: {
                    url: routeBuscar,
                    dataType: 'json',
                    delay: 250,
                    data: params => ({ q: params.term }),
                    processResults: data => ({ results: data.results })
                }
            });

            $('#selectPallet').on('select2:select', e => cargarPallet(e.params.data.id));
            $('#btnEscanear').on('click', toggleScanner);
            $('#qrZoom').on('input', function () { aplicarZoom(Number(this.value)); });
            $('cantidadVerificada').addEventListener('input', actualizarDiferencia);
            $('btnConfirmar').addEventListener('click', () => confirmar(false));
            $('btnConfirmarDiferencia').addEventListener('click', () => confirmar(true));
            $('btnCancelar').addEventListener('click', limpiarPanel);
        });

        function ocultarZoom() {
            $('#qrZoomControl').addClass('hidden');
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
            } catch (error) {
                console.warn('No fue posible obtener las capacidades de la cámara.', error);
            }
        }

        function aplicarZoom(valor) {
            if (!html5QrCode) return;
            html5QrCode.applyVideoConstraints({ advanced: [{ zoom: valor }] })
                .then(() => actualizarTextoZoom(valor))
                .catch(error => console.warn('No fue posible aplicar el zoom.', error));
        }

        function actualizarTextoZoom(valor) {
            document.getElementById('qrZoomValue').textContent = `${Number(valor).toFixed(1)}×`;
        }

        function toggleScanner() {
            const reader = document.getElementById('qrReader');

            if (!reader.classList.contains('hidden')) {
                detenerScanner();
                return;
            }

            reader.classList.remove('hidden');
            html5QrCode = new Html5Qrcode('qrReader');

            html5QrCode.start(
                { facingMode: 'environment' },
                {
                    fps: 10,
                    qrbox: { width: 250, height: 250 },
                    videoConstraints: {
                        facingMode: { ideal: 'environment' },
                        width: { ideal: 1920, min: 1280 },
                        height: { ideal: 2560, min: 720 },
                        frameRate: { ideal: 30, min: 15 }
                    }
                },
                decodedText => {
                    const numero = decodedText.trim();
                    detenerScanner();
                    cargarPallet(numero);
                }
            ).then(() => configurarZoom())
             .catch(error => {
                 console.error('No fue posible iniciar la cámara.', error);
                 detenerScanner();
                 mostrarAlerta('No fue posible acceder a la cámara. Revise los permisos del navegador.', 'error');
             });
        }

        function detenerScanner() {
            const reader = document.getElementById('qrReader');
            if (html5QrCode) {
                html5QrCode.stop().catch(() => {});
                html5QrCode.clear();
                html5QrCode = null;
            }
            ocultarZoom();
            reader.classList.add('hidden');
        }

        async function cargarPallet(numero) {
            numero = String(numero || '').trim();
            if (!numero) {
                mostrarAlerta('Seleccione o escanee un número de pallet.', 'error');
                return;
            }

            $('#palletPanel').addClass('hidden');

            try {
                const res = await fetch(baseUrl + '/buscar?numero=' + encodeURIComponent(numero), {
                    headers: { 'Accept': 'application/json' }
                });
                const data = await res.json();
                if (!res.ok) throw new Error(data.message || 'No fue posible consultar el pallet.');

                palletActual = data.pallet;
                mostrarPallet(palletActual);
                seleccionarPallet(numero);
            } catch (error) {
                mostrarAlerta(error.message, 'error');
                $('#selectPallet').val(null).trigger('change');
            }
        }

        function seleccionarPallet(numero) {
            const select = $('#selectPallet');
            if (!select.find(`option[value="${CSS.escape(numero)}"]`).length) {
                const option = new Option(numero, numero, true, true);
                select.append(option);
            }
            select.val(numero).trigger('change');
        }

        function mostrarPallet(pallet) {
            $('palletPanel').classList.remove('hidden');
            $('palletNumero').textContent = pallet.numero;
            $('palletFormato').textContent = pallet.formato || '—';
            $('palletTipo').textContent = pallet.tipo || '—';
            $('palletCapacidad').textContent = pallet.capacidad ?? '—';
            $('cantidadEsperada').textContent = pallet.cantidad_esperada;
            $('cantidadVerificada').value = pallet.cantidad_verificada ?? pallet.cantidad_esperada;
            $('observacion').value = pallet.observacion || '';

            const resultado = $('palletResultado');
            if (pallet.verificado) {
                resultado.textContent = pallet.resultado === 'CONFIRMADO_CON_DIFERENCIA' ? 'CONFIRMADO CON DIFERENCIA' : 'CONFIRMADO';
                resultado.className = 'px-3 py-1 rounded-full text-xs font-semibold ' + (pallet.resultado === 'CONFIRMADO_CON_DIFERENCIA' ? 'bg-orange-100 text-orange-800' : 'bg-green-100 text-green-800');
            } else {
                resultado.textContent = 'PENDIENTE';
                resultado.className = 'px-3 py-1 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-800';
            }

            let html = '<div class="text-sm font-semibold text-gray-700 mb-2">Contenido del pallet</div>';
            if (!pallet.contenido.length) {
                html += '<div class="text-sm text-gray-500">Sin detalle de contenido.</div>';
            } else {
                html += '<div class="divide-y border rounded-lg">' + pallet.contenido.map(item => '<div class="flex justify-between gap-3 p-3 text-sm"><div><div class="font-mono">' + esc(item.codigo) + '</div><div class="text-gray-500">Lote: ' + esc(item.lote || 'S/L') + '</div></div><div class="font-bold">' + item.cantidad + '</div></div>').join('') + '</div>';
            }
            $('contenidoBox').innerHTML = html;

            if (pallet.verificado) {
                $('cantidadVerificada').disabled = true;
                $('btnConfirmar').classList.add('hidden');
                $('btnConfirmarDiferencia').classList.add('hidden');
                $('observacionBox').classList.add('hidden');
            } else {
                $('cantidadVerificada').disabled = false;
                actualizarDiferencia();
                $('cantidadVerificada').focus();
                $('cantidadVerificada').select();
            }
        }

        function actualizarDiferencia() {
            if (!palletActual || palletActual.verificado) return;
            const verificada = Number($('cantidadVerificada').value);
            const esperada = Number(palletActual.cantidad_esperada);
            if (!Number.isInteger(verificada) || verificada < 0) {
                $('diferenciaBox').textContent = 'Ingrese una cantidad válida.';
                $('diferenciaBox').className = 'flex-1 rounded-lg p-3 text-sm bg-gray-50 text-gray-600';
                $('btnConfirmar').classList.add('hidden');
                $('btnConfirmarDiferencia').classList.add('hidden');
                $('observacionBox').classList.add('hidden');
                return;
            }

            const diferencia = verificada - esperada;
            if (diferencia === 0) {
                $('diferenciaBox').textContent = 'Cantidad correcta. No existe diferencia.';
                $('diferenciaBox').className = 'flex-1 rounded-lg p-3 text-sm bg-green-100 text-green-800';
                $('btnConfirmar').classList.remove('hidden');
                $('btnConfirmarDiferencia').classList.add('hidden');
                $('observacionBox').classList.add('hidden');
            } else {
                $('diferenciaBox').textContent = 'Diferencia: ' + (diferencia > 0 ? '+' : '') + diferencia + ' cajas.';
                $('diferenciaBox').className = 'flex-1 rounded-lg p-3 text-sm bg-orange-100 text-orange-800';
                $('btnConfirmar').classList.add('hidden');
                $('btnConfirmarDiferencia').classList.remove('hidden');
                $('observacionBox').classList.remove('hidden');
            }
        }

        async function confirmar(conDiferencia) {
            if (!palletActual || palletActual.verificado) return;
            const cantidad = Number($('cantidadVerificada').value);
            const esperada = Number(palletActual.cantidad_esperada);
            const diferencia = cantidad - esperada;

            if (conDiferencia && diferencia === 0) {
                mostrarAlerta('La cantidad coincide. Utilice CONFIRMAR.', 'error');
                return;
            }
            if (!conDiferencia && diferencia !== 0) {
                mostrarAlerta('Existe una diferencia. Utilice CONFIRMAR CON DIFERENCIA.', 'error');
                return;
            }
            if (conDiferencia && !$('observacion').value.trim()) {
                if (!confirm('Existe una diferencia. ¿Desea confirmar la diferencia sin observación adicional?')) return;
            }

            try {
                const res = await fetch(baseUrl + '/' + encodeURIComponent(palletActual.id) + '/confirmar', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf},
                    body: JSON.stringify({cantidad_verificada: cantidad, observacion: $('observacion').value.trim() || null})
                });
                const data = await res.json();
                if (!res.ok) throw new Error(data.message || 'No fue posible confirmar el pallet.');

                mostrarAlerta(data.message, data.resultado === 'CONFIRMADO' ? 'success' : 'warning');
                actualizarResumen(data.resumen, data.estado_entrega);
                limpiarPanel();
            } catch (error) {
                mostrarAlerta(error.message, 'error');
            }
        }

        function actualizarResumen(resumen, estado) {
            $('progreso').textContent = resumen.verificados + ' / ' + resumen.total;
            $('pendientes').textContent = resumen.pendientes + ' pendientes';
            $('conDiferencia').textContent = resumen.con_diferencia;
            $('barraProgreso').style.width = (resumen.total ? ((resumen.verificados / resumen.total) * 100) : 0) + '%';
            $('estadoEntrega').textContent = estado;
            if (estado === 'VERIFICADA') {
                $('pendientes').className = 'px-3 py-1 rounded-full bg-green-100 text-green-800 text-xs font-semibold';
                mostrarAlerta('La verificación de todos los pallets fue completada. La liberación quedó en estado VERIFICADA.', 'success');
            }
        }

        function limpiarPanel() {
            palletActual = null;
            $('palletPanel').classList.add('hidden');
            $('cantidadVerificada').value = '';
            $('observacion').value = '';
            $('#selectPallet').val(null).trigger('change');
            setTimeout(() => $('#selectPallet').select2('open'), 50);
        }

        function mostrarAlerta(mensaje, tipo) {
            const box = $('alertBox');
            box.textContent = mensaje;
            box.className = 'rounded-lg p-3 mb-4 text-sm ' + (tipo === 'success' ? 'bg-green-100 text-green-800' : tipo === 'warning' ? 'bg-orange-100 text-orange-800' : 'bg-red-100 text-red-800');
            box.classList.remove('hidden');
        }

        function esc(value) {
            return String(value ?? '—').replace(/[&<>"']/g, char => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[char]));
        }
    </script>
</x-app-layout>
