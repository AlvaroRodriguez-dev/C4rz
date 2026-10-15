<x-app-layout>
    <x-slot name="header">
        <h2 class="text-2xl font-bold text-gray-800">INGRESO PT LIBERADO</h2>
        <p class="text-sm text-gray-500">Asigne ubicación a cada pallet verificado para registrar su stock físico.</p>
    </x-slot>
    <div class="py-5"><div class="max-w-6xl mx-auto px-3 space-y-5">
        <div id="mensaje" class="hidden rounded-xl border p-3"></div>
        <section class="bg-white rounded-2xl shadow p-4">
            <h3 class="font-bold text-gray-800 mb-3">Liberaciones verificadas al 100%</h3>
            @forelse ($liberaciones as $liberacion)
                @php
                    $activos = $liberacion->hu->where('estado', '!=', 'SUSTITUIDO');
                    $pendientes = $activos->filter(fn ($hu) => !$hu->ubicacion_id && !$hu->ubicado_at)->count();
                @endphp
                <article class="border rounded-xl p-4 mb-3 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                    <div>
                        <div class="font-semibold">Liberación #{{ $liberacion->id }} · {{ $liberacion->documento?->id_documento ?? 'Sin documento' }}</div>
                        <div class="text-sm text-gray-600">Folio: {{ $liberacion->folio_fisico ?? '—' }} · Fecha: {{ optional($liberacion->fecha_entrega)->format('d/m/Y') ?? '—' }}</div>
                        <div class="text-sm mt-1">Pallets: {{ $activos->count() }} · Pendientes de ubicación: {{ $pendientes }}</div>
                    </div>
                    @if ($pendientes > 0)
                        <button type="button" class="seleccionar rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold px-5 py-3"
                            data-entrega="{{ $liberacion->id }}" data-documento="Liberación #{{ $liberacion->id }} · {{ $liberacion->documento?->id_documento ?? 'Sin documento' }}">DAR UBICACIÓN</button>
                    @else
                        <span class="rounded-full bg-green-100 text-green-800 px-3 py-2 text-sm font-semibold">Ubicación completa</span>
                    @endif
                </article>
            @empty
                <p class="text-sm text-gray-500">No existen liberaciones disponibles para asignar ubicación.</p>
            @endforelse
        </section>

        <section id="panel" class="hidden bg-white rounded-2xl shadow p-4 space-y-4">
            <div><h3 class="font-bold text-gray-800 text-lg">Registrar ubicación del pallet</h3><p id="liberacionSeleccionada" class="text-sm text-gray-500"></p></div>
            <div class="grid md:grid-cols-2 gap-4">
                <div><label for="pallet" class="block text-sm font-semibold mb-1">NRO DE PALLET</label><select id="pallet" class="w-full rounded-lg border-gray-300" disabled><option value="">Seleccione pallet</option></select></div>
                <div><label for="ubicacion" class="block text-sm font-semibold mb-1">UBICACIÓN (escanear o escribir código)</label><input id="ubicacion" type="text" autocomplete="off" class="w-full rounded-lg border-gray-300 uppercase" placeholder="Escanee el código de ubicación" disabled><p id="ayudaUbicacion" class="text-xs text-gray-500 mt-1">El lector puede escribir directamente en este campo.</p></div>
            </div>
            <div id="detallePallet" class="hidden rounded-xl bg-gray-50 border p-3 text-sm"></div>
            <div class="flex flex-col sm:flex-row gap-3">
                <button id="confirmar" type="button" disabled class="rounded-xl bg-green-600 hover:bg-green-700 disabled:opacity-40 text-white font-bold px-5 py-3">CONFIRMAR UBICACIÓN</button>
                <button id="cancelar" type="button" class="rounded-xl border px-5 py-3">Volver al listado</button>
            </div>
        </section>
    </div></div>
    <script>
    (() => {
        const base = @json(url('/wms/ingreso-pt-liberado'));
        const urlUbicaciones = @json(route('wms.ingreso-pt-liberado.ubicaciones'));
        const csrf = @json(csrf_token());
        const $ = id => document.getElementById(id);
        let entregaId = null, pallets = [], ubicaciones = [];
        function mensaje(texto, error = false) {
            $('mensaje').textContent = texto;
            $('mensaje').className = 'rounded-xl border p-3 ' + (error ? 'bg-red-50 border-red-200 text-red-800' : 'bg-green-50 border-green-200 text-green-800');
            $('mensaje').scrollIntoView({behavior:'smooth',block:'nearest'});
        }
        function limpiarMensaje() { $('mensaje').className = 'hidden'; $('mensaje').textContent = ''; }
        function validar() { $('confirmar').disabled = !($('pallet').value && $('ubicacion').value.trim()); }
        async function cargarPallets() {
            const r = await fetch(base + '/' + entregaId + '/pallets', {headers:{Accept:'application/json'}});
            const d = await r.json(); if (!r.ok) throw new Error(d.message || 'No se pudieron cargar los pallets.');
            pallets = d.pallets || [];
            $('pallet').innerHTML = '<option value="">Seleccione pallet</option>';
            pallets.filter(p => !p.ubicado).forEach(p => {
                const o = document.createElement('option'); o.value = p.id;
                o.textContent = p.numero + ' · ' + (p.formato || 'Sin formato') + ' · ' + p.cantidad + ' cajas'; $('pallet').appendChild(o);
            });
            $('pallet').disabled = false; $('ubicacion').disabled = false; $('ubicacion').value = ''; $('detallePallet').classList.add('hidden'); validar();
        }
        async function cargarUbicaciones() {
            const r = await fetch(urlUbicaciones, {headers:{Accept:'application/json'}});
            const d = await r.json(); if (!r.ok) throw new Error(d.message || 'No se pudieron cargar las ubicaciones.');
            ubicaciones = d.ubicaciones || [];
        }
        document.querySelectorAll('.seleccionar').forEach(b => b.addEventListener('click', async () => {
            limpiarMensaje(); entregaId = b.dataset.entrega; $('liberacionSeleccionada').textContent = b.dataset.documento;
            $('panel').classList.remove('hidden'); $('pallet').disabled = true; $('ubicacion').disabled = true;
            try { await Promise.all([cargarPallets(), cargarUbicaciones()]); $('panel').scrollIntoView({behavior:'smooth'}); $('pallet').focus(); }
            catch(e) { mensaje(e.message, true); }
        }));
        $('pallet').addEventListener('change', () => {
            const p = pallets.find(x => String(x.id) === $('pallet').value);
            $('detallePallet').innerHTML = '';
            if (!p) { $('detallePallet').classList.add('hidden'); validar(); return; }
            const title = document.createElement('p'); title.className = 'font-semibold'; title.textContent = 'Pallet ' + p.numero + ' · ' + p.cantidad + ' cajas'; $('detallePallet').appendChild(title);
            const detail = document.createElement('p'); detail.className = 'mt-1 text-gray-600'; detail.textContent = (p.detalles || []).map(d => d.codigo + ' · Lote ' + (d.lote || '—') + ' · ' + d.cantidad + ' cajas').join(' | '); $('detallePallet').appendChild(detail);
            $('detallePallet').classList.remove('hidden'); validar(); $('ubicacion').focus();
        });
        $('ubicacion').addEventListener('input', validar);
        $('ubicacion').addEventListener('change', () => {
            const codigo = $('ubicacion').value.trim().toUpperCase(); $('ubicacion').value = codigo;
            const u = ubicaciones.find(x => x.codigo.toUpperCase() === codigo);
            $('ayudaUbicacion').textContent = u ? 'Ubicación válida: ' + u.texto : 'Debe corresponder a una ubicación NORMAL activa.';
            $('ayudaUbicacion').className = 'text-xs mt-1 ' + (u ? 'text-green-700' : 'text-gray-500'); validar();
        });
        $('confirmar').addEventListener('click', async () => {
            limpiarMensaje(); $('confirmar').disabled = true; $('confirmar').textContent = 'GUARDANDO...';
            try {
                const r = await fetch(base + '/' + entregaId + '/confirmar', {
                    method:'POST', headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':csrf},
                    body:JSON.stringify({hu_id:$('pallet').value,ubicacion:$('ubicacion').value.trim()})
                });
                const d = await r.json(); if (!r.ok) throw new Error(d.message || Object.values(d.errors || {}).flat().join(' ') || 'No se pudo confirmar.');
                mensaje(d.message || 'Ubicación confirmada.');
                if (d.resultado?.todos_ubicados) {
                    const button = document.querySelector('.seleccionar[data-entrega="' + entregaId + '"]');
                    if (button) { const badge = document.createElement('span'); badge.className = 'rounded-full bg-green-100 text-green-800 px-3 py-2 text-sm font-semibold'; badge.textContent = 'Ubicación completa'; button.replaceWith(badge); }
                    $('panel').classList.add('hidden');
                } else { await cargarPallets(); }
            } catch(e) { mensaje(e.message, true); }
            finally { $('confirmar').textContent = 'CONFIRMAR UBICACIÓN'; validar(); }
        });
        $('cancelar').addEventListener('click', () => { $('panel').classList.add('hidden'); entregaId = null; limpiarMensaje(); });
    })();
    </script>
</x-app-layout>
