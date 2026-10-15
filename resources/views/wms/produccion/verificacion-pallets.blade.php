<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">WMS - Verificación de Pallets</h2>
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
        <div class="max-w-6xl mx-auto">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
                <div>
                    <a href="{{ route('wms.produccion.liberacion.verificacion.index') }}" class="text-sm text-gray-600">&larr; Volver a liberaciones</a>
                    <h1 class="text-2xl font-bold text-gray-900 mt-2">Verificación de pallets</h1>
                    <p class="text-sm text-gray-500">Busque o escanee cada pallet. Si fue corregido, confirme nuevamente su estado físico; los antecedentes se conservan para auditoría.</p>
                </div>
                <a href="{{ route('wms.produccion.liberacion.pallets', $entrega) }}" class="px-4 py-2 rounded-lg bg-gray-100 text-gray-700 font-semibold text-center">VER PALLETS</a>
            </div>

            @if (session('success'))
                <div class="rounded-lg bg-green-50 border border-green-200 text-green-800 p-3 mb-4 text-sm">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="rounded-lg bg-red-50 border border-red-200 text-red-800 p-3 mb-4 text-sm">{{ session('error') }}</div>
            @endif
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

                    <div id="antecedentesBox" class="hidden rounded-xl border border-blue-200 bg-blue-50 p-4 mb-5"></div>
                    <div id="contenidoBox" class="border-t pt-4 mb-5"></div>

                    <div class="border rounded-xl p-4 mb-5">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-3">
                            <div><div class="text-sm font-semibold text-gray-700">Discrepancias de producto o lote</div><p class="text-xs text-gray-500">Registre tono, calibre o calidad/producto distinto al declarado.</p></div>
                            <button id="btnAgregarDiscrepancia" type="button" class="px-3 py-2 rounded-lg bg-violet-100 hover:bg-violet-200 text-violet-800 font-semibold">+ AGREGAR DISCREPANCIA</button>
                        </div>
                        <div id="discrepanciasBox" class="space-y-3"></div>
                    </div>

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
        const routeConfirmarTemplate = @json(route('wms.produccion.liberacion.verificacion.pallets.confirmar', [$entrega, '__HU__']));
        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || @json(csrf_token());
        let palletActual = null;
        let html5QrCode = null;

        const dom = id => document.getElementById(id);
        const jq = window.jQuery;

        jq(document).ready(function () {
            jq('#selectPallet').select2({
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

            jq('#selectPallet').on('select2:select', e => cargarPallet(e.params.data.id));
            jq('#btnEscanear').on('click', toggleScanner);
            jq('#qrZoom').on('input', function () { aplicarZoom(Number(this.value)); });
            dom('cantidadVerificada').addEventListener('input', actualizarDiferencia);
            dom('btnConfirmar').addEventListener('click', () => confirmar(false));
            dom('btnConfirmarDiferencia').addEventListener('click', () => confirmar(true));
            dom('btnCancelar').addEventListener('click', limpiarPanel);
            dom('btnAgregarDiscrepancia').addEventListener('click', () => agregarDiscrepancia());
        });

        function ocultarZoom() { jq('#qrZoomControl').addClass('hidden'); }

        function configurarZoom() {
            try {
                const capabilities = html5QrCode.getRunningTrackCapabilities();
                const zoom = capabilities.zoom;
                const slider = dom('qrZoom');
                const settings = html5QrCode.getRunningTrackSettings();
                if (zoom && zoom.max > zoom.min) {
                    slider.min = zoom.min; slider.max = zoom.max; slider.step = zoom.step || 0.1;
                    slider.value = settings.zoom ?? zoom.min; actualizarTextoZoom(slider.value);
                    jq('#qrZoomControl').removeClass('hidden');
                } else ocultarZoom();
            } catch (error) { console.warn('No fue posible obtener las capacidades de la cámara.', error); }
        }

        function aplicarZoom(valor) {
            if (!html5QrCode) return;
            html5QrCode.applyVideoConstraints({ advanced: [{ zoom: valor }] })
                .then(() => actualizarTextoZoom(valor))
                .catch(error => console.warn('No fue posible aplicar el zoom.', error));
        }

        function actualizarTextoZoom(valor) { dom('qrZoomValue').textContent = `${Number(valor).toFixed(1)}×`; }

        function toggleScanner() {
            const reader = dom('qrReader');
            if (!reader.classList.contains('hidden')) { detenerScanner(); return; }
            reader.classList.remove('hidden');
            html5QrCode = new Html5Qrcode('qrReader');
            html5QrCode.start(
                { facingMode: 'environment' },
                { fps: 10, qrbox: { width: 250, height: 250 }, videoConstraints: { facingMode: { ideal: 'environment' }, width: { ideal: 1920, min: 1280 }, height: { ideal: 2560, min: 720 }, frameRate: { ideal: 30, min: 15 } } },
                decodedText => { const numero = decodedText.trim(); detenerScanner(); cargarPallet(numero); }
            ).then(() => configurarZoom())
             .catch(error => { console.error('No fue posible iniciar la cámara.', error); detenerScanner(); mostrarAlerta('No fue posible acceder a la cámara. Revise los permisos del navegador.', 'error'); });
        }

        function detenerScanner() {
            const reader = dom('qrReader');
            if (html5QrCode) { html5QrCode.stop().catch(() => {}); html5QrCode.clear(); html5QrCode = null; }
            ocultarZoom(); reader.classList.add('hidden');
        }

        async function cargarPallet(numero) {
            numero = String(numero || '').trim();
            if (!numero) { mostrarAlerta('Seleccione o escanee un número de pallet.', 'error'); return; }
            jq('#palletPanel').addClass('hidden');
            try {
                const res = await fetch(baseUrl + '/buscar?numero=' + encodeURIComponent(numero), { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' });
                const data = await res.json();
                if (!res.ok) throw new Error(data.message || 'No fue posible consultar el pallet.');
                palletActual = data.pallet; mostrarPallet(palletActual); seleccionarPallet(numero);
            } catch (error) { mostrarAlerta(error.message, 'error'); jq('#selectPallet').val(null).trigger('change'); }
        }

        function seleccionarPallet(numero) {
            const select = jq('#selectPallet');
            if (!select.find('option').filter(function () { return this.value === numero; }).length) {
                select.append(new Option(numero, numero, true, true));
            }
            select.val(numero).trigger('change');
        }

        function mostrarPallet(pallet) {
            dom('palletPanel').classList.remove('hidden');
            dom('palletNumero').textContent = pallet.numero;
            dom('palletFormato').textContent = pallet.formato || '—';
            dom('palletTipo').textContent = pallet.tipo || '—';
            dom('palletCapacidad').textContent = pallet.capacidad ?? '—';
            dom('cantidadEsperada').textContent = pallet.cantidad_esperada;
            dom('cantidadVerificada').value = pallet.cantidad_verificada ?? pallet.cantidad_esperada;
            dom('observacion').value = pallet.observacion || '';
            dom('discrepanciasBox').innerHTML = '';
            (pallet.discrepancias || []).forEach(d => agregarDiscrepancia(d));

            const antecedentes = pallet.antecedentes || [];
            const antecedentesBox = dom('antecedentesBox');
            if (antecedentes.length) {
                antecedentesBox.classList.remove('hidden');
                antecedentesBox.innerHTML = '<div class="font-semibold text-blue-900 mb-1">Antecedente de la corrección (solo consulta)</div>' +
                    '<p class="text-sm text-blue-800 mb-3">Estas incidencias pertenecen a la revisión anterior. No debe eliminarlas ni volver a registrarlas. Verifique el pallet con su contenido actual.</p>' +
                    antecedentes.map(d => '<div class="border-t border-blue-200 py-2 text-sm"><div class="font-semibold">' + esc(d.tipo.replaceAll('_', ' ')) + ' · ' + esc(d.valor_esperado || '—') + ' → ' + esc(d.valor_observado || '—') + '</div>' +
                    '<div class="text-blue-800">Corrección: ' + esc(d.corregido_at || 'fecha no disponible') + '</div>' +
                    (d.observacion ? '<div class="text-blue-800">Observación original: ' + esc(d.observacion) + '</div>' : '') +
                    (d.valor_corregido ? '<details class="mt-1"><summary class="cursor-pointer font-medium">Ver detalle de la corrección</summary><pre class="text-xs whitespace-pre-wrap mt-2">' + esc(typeof d.valor_corregido === 'string' ? d.valor_corregido : JSON.stringify(d.valor_corregido, null, 2)) + '</pre></details>' : '') +
                    '</div>').join('');
            } else {
                antecedentesBox.classList.add('hidden');
                antecedentesBox.innerHTML = '';
            }

            const resultado = dom('palletResultado');
            if (pallet.requiere_reverificacion) {
                resultado.textContent = 'REVERIFICACIÓN PENDIENTE';
                resultado.className = 'px-3 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-800';
            } else if (pallet.verificado) {
                resultado.textContent = pallet.resultado === 'CONFIRMADO_CON_DIFERENCIA' ? 'CONFIRMADO CON DIFERENCIA' : 'CONFIRMADO';
                resultado.className = 'px-3 py-1 rounded-full text-xs font-semibold ' + (pallet.resultado === 'CONFIRMADO_CON_DIFERENCIA' ? 'bg-orange-100 text-orange-800' : 'bg-green-100 text-green-800');
            } else {
                resultado.textContent = 'PENDIENTE'; resultado.className = 'px-3 py-1 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-800';
            }
            dom('btnConfirmar').textContent = pallet.requiere_reverificacion ? 'CONFIRMAR REVERIFICACIÓN' : 'CONFIRMAR';
            dom('btnConfirmarDiferencia').textContent = pallet.requiere_reverificacion ? 'CONFIRMAR REVERIFICACIÓN CON DIFERENCIA' : 'CONFIRMAR CON DIFERENCIA';

            let html = '<div class="text-sm font-semibold text-gray-700 mb-2">Contenido del pallet</div>';
            if (!pallet.contenido.length) {
                html += '<div class="text-sm text-gray-500">Sin detalle de contenido.</div>';
            } else {
                html += '<div class="divide-y border rounded-lg">' + pallet.contenido.map(item => {
                    const nombre = [item.descripcion, item.descripcion1].filter(Boolean).join(' ').trim();
                    return '<div class="p-3 text-sm flex justify-between gap-3">' +
                        '<div class="min-w-0">' +
                            '<div class="font-semibold text-gray-800 break-words">' + esc(nombre || 'Producto sin descripción') + '</div>' +
                            '<div class="font-mono text-gray-600 break-all">Código: ' + esc(item.codigo) + '</div>' +
                            '<div class="text-gray-500">Calidad: ' + esc(item.calidad || '—') + '</div>' +
                            '<div class="text-gray-500">Lote: ' + esc(item.lote || 'S/L') + '</div>' +
                        '</div>' +
                        '<div class="font-bold shrink-0">' + item.cantidad + '</div>' +
                    '</div>';
                }).join('') + '</div>';
            }
            dom('contenidoBox').innerHTML = html;

            if (pallet.verificado) {
                dom('btnConfirmar').classList.add('hidden'); dom('btnConfirmarDiferencia').classList.add('hidden');
                dom('cantidadVerificada').disabled = true; dom('observacion').disabled = true;
                dom('observacionBox').classList.toggle('hidden', pallet.resultado !== 'CONFIRMADO_CON_DIFERENCIA');
            } else {
                dom('cantidadVerificada').disabled = false; dom('observacion').disabled = false; actualizarDiferencia();
            }
        }

        function actualizarDiferencia() {
            if (!palletActual || palletActual.verificado) return;
            const esperada = Number(palletActual.cantidad_esperada || 0);
            const verificada = Number(dom('cantidadVerificada').value || 0);
            const diferencia = verificada - esperada;
            const box = dom('diferenciaBox');
            const hayDiscrepancias = dom('discrepanciasBox').querySelectorAll('.discrepancia-row').length > 0;
            if (diferencia === 0 && !hayDiscrepancias) {
                box.textContent = 'La cantidad coincide con lo esperado.'; box.className = 'flex-1 rounded-lg p-3 text-sm bg-green-50 text-green-700';
                dom('btnConfirmar').classList.remove('hidden'); dom('btnConfirmarDiferencia').classList.add('hidden'); dom('observacionBox').classList.add('hidden');
            } else {
                box.textContent = diferencia !== 0 ? ('Diferencia: ' + (diferencia > 0 ? '+' : '') + diferencia + ' cajas') : 'Se registraron discrepancias de producto o lote.'; box.className = 'flex-1 rounded-lg p-3 text-sm bg-orange-50 text-orange-700';
                dom('btnConfirmar').classList.add('hidden'); dom('btnConfirmarDiferencia').classList.remove('hidden'); dom('observacionBox').classList.remove('hidden');
            }
        }

        async function confirmar(conDiferencia) {
            if (!palletActual || palletActual.verificado) return;
            const cantidad = Number(dom('cantidadVerificada').value);
            const esperada = Number(palletActual.cantidad_esperada);
            if (!Number.isInteger(cantidad) || cantidad < 0) { mostrarAlerta('Ingrese una cantidad válida.', 'error'); return; }
            const discrepancias = leerDiscrepancias();
            if (conDiferencia && cantidad === esperada && discrepancias.length === 0) { mostrarAlerta('Registre una discrepancia o confirme la cantidad coincidente.', 'error'); return; }
            if (!conDiferencia && (cantidad !== esperada || discrepancias.length > 0)) { mostrarAlerta('Existen diferencias. Use CONFIRMAR CON DIFERENCIA.', 'error'); return; }

            const confirmarUrl = routeConfirmarTemplate.replace('__HU__', encodeURIComponent(palletActual.id));

            try {
                const response = await fetch(confirmarUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrf,
                    },
                    body: JSON.stringify({ cantidad_verificada: cantidad, observacion: dom('observacion').value.trim() || null, discrepancias }),
                });
                const data = await response.json();
                if (!response.ok) throw new Error(data.message || 'No fue posible confirmar el pallet.');
                mostrarAlerta(data.message, 'success');
                palletActual.verificado = true; palletActual.resultado = data.resultado; palletActual.cantidad_verificada = cantidad; palletActual.diferencia = data.diferencia; palletActual.discrepancias = discrepancias;
                mostrarPallet(palletActual); actualizarResumen(data.resumen, data.estado_entrega); actualizarOpcionPallet(palletActual.numero, data.resultado);
            } catch (error) { mostrarAlerta(error.message, 'error'); }
        }


        function agregarDiscrepancia(dato = null) {
            if (!palletActual || palletActual.verificado) return;
            const row = document.createElement('div');
            row.className = 'discrepancia-row grid grid-cols-1 md:grid-cols-4 gap-2 border rounded-lg p-3';
            const opciones = (palletActual.contenido || []).map(item => '<option value="' + esc(item.id) + '" ' + (dato && Number(dato.hu_detalle_id) === Number(item.id) ? 'selected' : '') + '>' + esc(item.codigo) + ' · ' + esc(item.lote || 'S/L') + '</option>').join('');
            row.innerHTML = '<div><label class="block text-xs font-semibold text-gray-600 mb-1">Detalle del pallet</label><select class="disc-detalle w-full border-gray-300 rounded-lg">' + opciones + '</select></div>' +
                '<div><label class="block text-xs font-semibold text-gray-600 mb-1">Tipo</label><select class="disc-tipo w-full border-gray-300 rounded-lg"><option value="TONO">Tono</option><option value="CALIBRE">Calibre</option><option value="CALIDAD_PRODUCTO">Calidad / producto</option></select></div>' +
                '<div><label class="block text-xs font-semibold text-gray-600 mb-1">Valor físico observado</label><input class="disc-valor w-full border-gray-300 rounded-lg" maxlength="255" value="' + esc(dato?.valor_observado || '') + '" placeholder="Valor encontrado"></div>' +
                '<div><label class="block text-xs font-semibold text-gray-600 mb-1">Observación</label><div class="flex gap-2"><input class="disc-observacion min-w-0 w-full border-gray-300 rounded-lg" maxlength="1000" value="' + esc(dato?.observacion || '') + '" placeholder="Opcional"><button type="button" class="disc-eliminar px-3 rounded-lg bg-red-100 text-red-700" title="Quitar">×</button></div></div>';
            if (dato?.tipo) row.querySelector('.disc-tipo').value = dato.tipo;
            row.querySelector('.disc-eliminar').addEventListener('click', () => { row.remove(); actualizarDiferencia(); });
            row.querySelectorAll('input,select').forEach(el => el.addEventListener('input', actualizarDiferencia));
            dom('discrepanciasBox').appendChild(row);
            actualizarDiferencia();
        }

        function leerDiscrepancias() {
            return Array.from(dom('discrepanciasBox').querySelectorAll('.discrepancia-row')).map(row => ({
                hu_detalle_id: Number(row.querySelector('.disc-detalle').value),
                tipo: row.querySelector('.disc-tipo').value,
                valor_observado: row.querySelector('.disc-valor').value.trim(),
                observacion: row.querySelector('.disc-observacion').value.trim() || null,
            })).filter(d => d.valor_observado !== '');
        }

        function actualizarOpcionPallet(numero, resultado) {
            const select = jq('#selectPallet');
            const option = select.find('option').filter(function () { return this.value === numero; });
            if (!option.length) return;
            option.text(numero + ' · ' + (resultado === 'CONFIRMADO_CON_DIFERENCIA' ? 'CONFIRMADO CON DIFERENCIA' : 'CONFIRMADO'));
            select.trigger('change.select2');
        }

        function actualizarResumen(resumen, estado) {
            dom('progreso').textContent = `${resumen.verificados} / ${resumen.total}`;
            dom('pendientes').textContent = `${resumen.pendientes} pendientes`;
            dom('conDiferencia').textContent = resumen.con_diferencia;
            dom('estadoEntrega').textContent = estado;
            dom('barraProgreso').style.width = `${resumen.total ? ((resumen.verificados / resumen.total) * 100).toFixed(2) : 0}%`;
        }

        function limpiarPanel() {
            palletActual = null; dom('discrepanciasBox').innerHTML = ''; jq('#selectPallet').val(null).trigger('change'); jq('#palletPanel').addClass('hidden');
            dom('observacionBox').classList.add('hidden'); dom('btnConfirmar').classList.add('hidden'); dom('btnConfirmarDiferencia').classList.add('hidden');
        }

        function mostrarAlerta(mensaje, tipo) {
            const box = dom('alertBox'); box.textContent = mensaje;
            box.className = 'rounded-lg p-3 mb-4 text-sm ' + (tipo === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800');
            box.classList.remove('hidden'); window.setTimeout(() => box.classList.add('hidden'), 5000);
        }

        function esc(value) {
            return String(value ?? '').replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;').replaceAll('"', '&quot;').replaceAll("'", '&#039;');
        }
    </script>
</x-app-layout>
