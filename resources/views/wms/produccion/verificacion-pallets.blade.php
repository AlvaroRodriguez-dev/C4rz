<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">WMS - Verificación de Pallets</h2>
    </x-slot>

    <div class="py-4 px-3 sm:py-6 sm:px-4">
        <div class="max-w-6xl mx-auto">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
                <div>
                    <a href="{{ route('wms.produccion.liberacion.create') }}" class="text-sm text-gray-600">&larr; Volver a liberaciones</a>
                    <h1 class="text-2xl font-bold text-gray-900 mt-2">Verificación de pallets</h1>
                    <p class="text-sm text-gray-500">Escanee cada QR, confirme la cantidad física y complete la verificación de toda la liberación.</p>
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
                    <label for="numeroPallet" class="block text-sm font-semibold text-gray-700 mb-1">Escanear QR del pallet</label>
                    <div class="flex flex-col sm:flex-row gap-2">
                        <input id="numeroPallet" type="text" autocomplete="off" autofocus placeholder="Escanee o escriba el número del pallet..." class="flex-1 border-gray-300 rounded-lg text-lg font-mono">
                        <button id="btnBuscar" type="button" class="px-5 py-2.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-semibold">BUSCAR PALLET</button>
                    </div>
                    <p class="text-xs text-gray-500 mt-2">Los lectores QR que funcionan como teclado pueden utilizarse directamente sobre este campo.</p>
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
        const confirmarUrlBase = @json(url('/wms/produccion-liberacion/' . $entrega->id . '/verificacion-pallets'));
        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || @json(csrf_token());
        let palletActual = null;

        const $ = id => document.getElementById(id);

        document.addEventListener('DOMContentLoaded', () => {
            $('btnBuscar').addEventListener('click', buscarPallet);
            $('numeroPallet').addEventListener('keydown', e => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    buscarPallet();
                }
            });
            $('cantidadVerificada').addEventListener('input', actualizarDiferencia);
            $('btnConfirmar').addEventListener('click', () => confirmar(false));
            $('btnConfirmarDiferencia').addEventListener('click', () => confirmar(true));
            $('btnCancelar').addEventListener('click', limpiarPanel);
        });

        async function buscarPallet() {
            const numero = $('numeroPallet').value.trim();
            if (!numero) {
                mostrarAlerta('Escanee o ingrese un número de pallet.', 'error');
                $('numeroPallet').focus();
                return;
            }

            try {
                const res = await fetch(baseUrl + '/buscar?numero=' + encodeURIComponent(numero), {headers: {'Accept': 'application/json'}});
                const data = await res.json();
                if (!res.ok) throw new Error(data.message || 'No fue posible consultar el pallet.');

                palletActual = data.pallet;
                mostrarPallet(palletActual);
                $('numeroPallet').select();
            } catch (error) {
                mostrarAlerta(error.message, 'error');
                $('numeroPallet').select();
            }
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
                const res = await fetch(confirmarUrlBase + '/' + encodeURIComponent(palletActual.id) + '/confirmar', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf},
                    body: JSON.stringify({cantidad_verificada: cantidad, observacion: $('observacion').value.trim() || null})
                });
                const data = await res.json();
                if (!res.ok) throw new Error(data.message || 'No fue posible confirmar el pallet.');

                mostrarAlerta(data.message, data.resultado === 'CONFIRMADO' ? 'success' : 'warning');
                actualizarResumen(data.resumen, data.estado_entrega);
                limpiarPanel();
                $('numeroPallet').focus();
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
            $('numeroPallet').focus();
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
