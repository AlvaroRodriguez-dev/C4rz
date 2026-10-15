<x-app-layout>
    <x-slot name="header">
        <h2 class="text-2xl font-bold text-gray-800">INGRESO PT LIBERADO</h2>
        <p class="text-sm text-gray-500">Asigne ubicación a cada pallet verificado para registrar su stock físico.</p>
    </x-slot>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-rc.0/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-rc.0/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    <style>.select2-container .select2-selection--single{height:48px!important;display:flex;align-items:center;border-radius:.75rem!important;border-color:#d1d5db!important}.select2-container--default .select2-selection--single .select2-selection__rendered{line-height:48px!important;font-size:16px;padding-left:12px!important}</style>
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
                        @if ($activos->contains(fn ($hu) => $hu->ubicacion_id || $hu->ubicado_at))
                            <div class="mt-2 rounded-lg bg-green-50 border border-green-200 p-2 text-sm text-green-900"><div class="font-semibold mb-1">Pallets y ubicaciones registrados</div>
                                @foreach ($activos->filter(fn ($hu) => $hu->ubicacion_id || $hu->ubicado_at) as $hu)
                                    <div>• <span class="font-mono font-semibold">{{ $hu->numero }}</span> → {{ $hu->ubicacion?->galpon?->codigo ?? '—' }} / {{ $hu->ubicacion?->codigo ?? 'Ubicación no disponible' }}</div>
                                @endforeach
                            </div>
                        @endif
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
            <div id="registradosPanel" class="hidden rounded-xl border border-green-200 bg-green-50 p-3 text-sm text-green-900"></div>
            <div class="grid md:grid-cols-2 gap-4">
                <div><label for="pallet" class="block text-sm font-semibold mb-1">NRO DE PALLET</label><div class="flex gap-2"><select id="pallet" class="w-full" style="width:100%" disabled><option value="">Seleccione pallet</option></select><button id="scanPallet" type="button" class="rounded-xl bg-gray-800 text-white px-4" title="Escanear QR del pallet" disabled>📷</button></div></div>
                <div><label for="ubicacion" class="block text-sm font-semibold mb-1">UBICACIÓN</label><div class="flex gap-2"><select id="ubicacion" class="w-full" style="width:100%" disabled><option value="">Seleccione ubicación</option></select><button id="scanUbicacion" type="button" class="rounded-xl bg-gray-800 text-white px-4" title="Escanear QR de la ubicación" disabled>📷</button></div><p id="ayudaUbicacion" class="text-xs text-gray-500 mt-1">Seleccione o escanee el código QR de ubicación.</p></div>
            </div><div id="qrReader" class="hidden rounded-xl overflow-hidden border bg-black"></div>
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
        let entregaId = null, pallets = [], ubicaciones = [], scanner = null, destinoEscaneo = null;
        const jq = window.jQuery;
        jq(document).ready(() => {
            jq('#pallet').select2({placeholder:'Seleccione o escriba un pallet',width:'100%'});
            jq('#ubicacion').select2({placeholder:'Seleccione o escriba una ubicación',width:'100%'});
            jq('#pallet').on('change', mostrarDetallePallet);
            jq('#ubicacion').on('change', () => { const codigo=String(jq('#ubicacion').val()||'').toUpperCase(); const u=ubicaciones.find(x=>x.codigo.toUpperCase()===codigo); $('ayudaUbicacion').textContent=u?'Ubicación válida: '+u.texto:'Seleccione una ubicación NORMAL activa.'; $('ayudaUbicacion').className='text-xs mt-1 '+(u?'text-green-700':'text-gray-500'); validar(); });
            $('scanPallet').addEventListener('click',()=>iniciarScanner('pallet'));
            $('scanUbicacion').addEventListener('click',()=>iniciarScanner('ubicacion'));
        });
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
            jq('#pallet').empty().append(new Option('Seleccione pallet','',true,true));
            pallets.filter(p=>!p.ubicado).forEach(p=>jq('#pallet').append(new Option(p.numero+' · '+(p.formato||'Sin formato')+' · '+p.cantidad+' cajas',String(p.id))));
            jq('#ubicacion').empty().append(new Option('Seleccione ubicación','',true,true));
            ubicaciones.forEach(u=>jq('#ubicacion').append(new Option(u.texto,u.codigo)));
            jq('#pallet').val('').trigger('change.select2'); jq('#ubicacion').val('').trigger('change.select2');
            $('pallet').disabled=false; $('ubicacion').disabled=false; $('scanPallet').disabled=false; $('scanUbicacion').disabled=false;
            $('detallePallet').classList.add('hidden'); validar();
            const ya=pallets.filter(p=>p.ubicado); $('registradosPanel').classList.toggle('hidden',ya.length===0);
            $('registradosPanel').innerHTML='<div class="font-semibold mb-1">Pallets y ubicaciones ya registrados</div>'+ya.map(p=>'<div>• <strong>'+esc(p.numero)+'</strong> → '+esc(p.ubicacion_texto||'Ubicación no disponible')+'</div>').join('');
        }
        async function cargarUbicaciones() {
            const r = await fetch(urlUbicaciones, {headers:{Accept:'application/json'}});
            const d = await r.json(); if (!r.ok) throw new Error(d.message || 'No se pudieron cargar las ubicaciones.');
            ubicaciones = d.ubicaciones || [];
        }
        document.querySelectorAll('.seleccionar').forEach(b => b.addEventListener('click', async () => {
            limpiarMensaje(); entregaId = b.dataset.entrega; $('liberacionSeleccionada').textContent = b.dataset.documento;
            $('panel').classList.remove('hidden'); $('pallet').disabled = true; $('ubicacion').disabled = true;
            try { await cargarUbicaciones(); await cargarPallets(); $('panel').scrollIntoView({behavior:'smooth'}); jq('#pallet').select2('open'); }
            catch(e) { mensaje(e.message, true); }
        }));
        function mostrarDetallePallet() {
            const p=pallets.find(x=>String(x.id)===String(jq('#pallet').val())); $('detallePallet').innerHTML='';
            if(!p){$('detallePallet').classList.add('hidden');validar();return;}
            const title=document.createElement('p');title.className='font-semibold';title.textContent='Pallet '+p.numero+' · '+p.cantidad+' cajas';$('detallePallet').appendChild(title);
            const detail=document.createElement('p');detail.className='mt-1 text-gray-600';detail.textContent=(p.detalles||[]).map(d=>d.codigo+' · Lote '+(d.lote||'—')+' · '+d.cantidad+' cajas').join(' | ');$('detallePallet').appendChild(detail);
            $('detallePallet').classList.remove('hidden');validar();
        }
        async function iniciarScanner(destino){
            if(scanner){await detenerScanner();return;} destinoEscaneo=destino; $('qrReader').classList.remove('hidden'); scanner=new Html5Qrcode('qrReader');
            try{await scanner.start({facingMode:'environment'},{fps:10,qrbox:{width:250,height:250}},async decoded=>{
                const codigo=decoded.trim();await detenerScanner();
                if(destinoEscaneo==='pallet'){const p=pallets.find(x=>String(x.numero).toUpperCase()===codigo.toUpperCase());if(!p||p.ubicado){mensaje('El QR no corresponde a un pallet pendiente de esta liberación.',true);return;}jq('#pallet').val(String(p.id)).trigger('change');}
                else{const u=ubicaciones.find(x=>x.codigo.toUpperCase()===codigo.toUpperCase());if(!u){mensaje('El QR no corresponde a una ubicación NORMAL activa del almacén.',true);return;}jq('#ubicacion').val(u.codigo).trigger('change');}
                validar();
            });}catch(e){await detenerScanner();mensaje('No fue posible acceder a la cámara. Verifique los permisos del navegador.',true);}
        }
        async function detenerScanner(){if(scanner){try{await scanner.stop()}catch(e){}try{scanner.clear()}catch(e){}scanner=null}$('qrReader').classList.add('hidden')}
        function esc(value){return String(value??'').replaceAll('&','&amp;').replaceAll('<','&lt;').replaceAll('>','&gt;').replaceAll('"','&quot;').replaceAll("'",'&#039;')}
        $('confirmar').addEventListener('click', async () => {
            limpiarMensaje(); $('confirmar').disabled = true; $('confirmar').textContent = 'GUARDANDO...';
            try {
                const r = await fetch(base + '/' + entregaId + '/confirmar', {
                    method:'POST', headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':csrf},
                    body:JSON.stringify({hu_id:jq('#pallet').val(),ubicacion:jq('#ubicacion').val()})
                });
                const d = await r.json(); if (!r.ok) throw new Error(d.message || Object.values(d.errors || {}).flat().join(' ') || 'No se pudo confirmar.');
                mensaje(d.message || 'Ubicación confirmada.');
                $('registradosPanel').classList.remove('hidden'); $('registradosPanel').innerHTML='<div class="font-semibold mb-1">Último registro confirmado</div><div>• <strong>'+esc(d.resultado?.pallet)+'</strong> → '+esc(d.resultado?.ubicacion)+'</div>';
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
