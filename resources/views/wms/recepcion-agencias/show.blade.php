<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div><h2 class="font-semibold text-xl text-gray-800">Recepción de agencia #{{ $cabecera->id }}</h2>
                <p class="text-sm text-gray-500">Camión: {{ $cabecera->camion ?: 'Sin identificar' }} · {{ \Carbon\Carbon::parse($cabecera->fecha_recepcion)->format('d/m/Y H:i') }}</p></div>
            <span class="rounded-full px-3 py-1 text-sm {{ $cabecera->estado === 'abierta' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-700' }}">{{ strtoupper($cabecera->estado) }}</span>
        </div>
    </x-slot>

    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-rc.0/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-rc.0/js/select2.min.js"></script>
    <script src="https://unpkg.com/html5-qrcode"></script>
    <style>
        .select2-container .select2-selection--single {height:44px!important;display:flex;align-items:center;border:1px solid #d1d5db!important;border-radius:9px!important}
        .select2-container--default .select2-selection--single .select2-selection__rendered {line-height:44px!important;padding-left:12px!important}
        .select2-container--default .select2-selection--single .select2-selection__arrow {height:42px!important}
        .field {width:100%;border:1px solid #d1d5db;border-radius:8px;padding:8px}
    </style>

    <div class="py-5 px-3 sm:px-6">
        <div class="max-w-7xl mx-auto space-y-5">
            <div class="flex items-center justify-between">
                <a href="{{ route('wms.recepcion-agencias.index') }}" class="text-sm text-gray-600 hover:text-gray-900">← Recepciones</a>
                @if($cabecera->estado === 'abierta')
                    <button id="btnCerrar" class="bg-gray-700 hover:bg-gray-800 text-white text-sm font-semibold rounded-lg px-4 py-2">Cerrar recepción</button>
                @endif
            </div>

            <div class="bg-white rounded-xl shadow p-4">
                <h3 class="font-semibold text-gray-800 mb-2">Documentos asociados</h3>
                <div class="flex flex-wrap gap-2">
                    @foreach($documentos as $d)
                        <span class="rounded-lg bg-indigo-50 text-indigo-800 px-3 py-2 text-sm">Tipo {{ $d->tipo_registro }} · #{{ $d->id_registro }} · {{ $d->glosa }}</span>
                    @endforeach
                </div>
                <p class="text-xs text-gray-500 mt-3">Los documentos son referencias logísticas. La recepción física y el saldo WMS se controlan por pallet.</p>
            </div>

            <div class="bg-white rounded-xl shadow p-4 sm:p-5">
                <div class="flex flex-col md:flex-row md:items-end gap-3">
                    <div class="flex-1">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Buscar pallet de esta recepción</label>
                        <select id="selectPallet" class="w-full"></select>
                    </div>
                    <button id="btnScan" class="rounded-lg border border-indigo-300 text-indigo-700 font-semibold px-4 py-3">▦ Escanear QR</button>
                </div>
                <div id="readerWrap" class="hidden mt-4 border rounded-xl p-3">
                    <div id="reader" class="max-w-md mx-auto"></div>
                    <button id="btnStopScan" class="mt-2 text-sm text-red-700">Detener cámara</button>
                </div>
                <div id="alerta" class="hidden mt-4 rounded-lg p-3 text-sm"></div>
            </div>

            <div id="gestion" class="hidden bg-white rounded-xl shadow p-4 sm:p-5">
                <div class="flex flex-wrap items-start justify-between gap-3 border-b pb-3 mb-4">
                    <div><div class="text-xs text-gray-500">Pallet seleccionado</div><h3 id="palletTitulo" class="text-xl font-bold font-mono"></h3>
                        <p id="palletMeta" class="text-sm text-gray-500"></p></div>
                    <span id="estadoPallet" class="rounded-full px-3 py-1 text-xs font-bold"></span>
                </div>

                <section id="panelDescarga" class="hidden space-y-3">
                    <h4 class="font-semibold">1. Registrar descarga temporal</h4>
                    <p class="text-sm text-gray-600">Ubica físicamente el pallet en una zona temporal. Esto no genera stock WMS.</p>
                    <select id="ubicacionTemporal" class="w-full"></select>
                    <button id="btnDescargar" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg px-5 py-3">Registrar descarga</button>
                </section>

                <section id="panelVerificacion" class="hidden space-y-3">
                    <h4 class="font-semibold">2. Verificación física del pallet</h4>
                    <p class="text-sm text-gray-600">Compara cada línea con el contenido físico. Puedes corregir código, lote y cantidad; las diferencias bloquearán únicamente este pallet.</p>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-gray-50"><tr><th class="p-2 text-left">Esperado</th><th class="p-2 text-left">Código físico</th><th class="p-2 text-left">Lote físico</th><th class="p-2 text-left">Cajas físicas</th><th class="p-2 text-left">Observación</th></tr></thead>
                            <tbody id="detalleVerificacion" class="divide-y"></tbody>
                        </table>
                    </div>
                    <button id="btnAgregarDetalle" class="text-sm text-indigo-700 font-semibold">+ Agregar producto no declarado</button>
                    <div><label class="block text-sm font-medium mb-1">Observación general (daño, humedad, embalaje, etc.)</label>
                        <textarea id="observacionGeneral" rows="2" class="field" placeholder="Dejar vacío si no existen observaciones"></textarea></div>
                    <button id="btnVerificar" class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg px-5 py-3">Guardar verificación del pallet</button>
                    @if(auth()->user()->hasAnyRole(['WMS-ADMIN','SIS-ADMIN']))
                        <button id="btnAutorizar" class="hidden bg-amber-600 hover:bg-amber-700 text-white font-semibold rounded-lg px-5 py-3">Autorizar excepción de este pallet</button>
                    @endif
                </section>

                <section id="panelIngreso" class="hidden space-y-3">
                    <h4 class="font-semibold">3. Ubicación definitiva e ingreso WMS</h4>
                    <p class="text-sm text-gray-600">Solo los pallets conformes o con excepción autorizada pueden ingresar al stock físico.</p>
                    <select id="ubicacionFinal" class="w-full"></select>
                    <button id="btnIngresar" class="bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg px-5 py-3">Confirmar ubicación e ingreso definitivo</button>
                </section>
                <div id="detalleEstado" class="mt-4 text-sm text-gray-600"></div>
                <div id="observacionesPallet" class="hidden mt-3 rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900"></div>
            </div>

            <div class="bg-white rounded-xl shadow overflow-hidden">
                <div class="p-4 border-b"><h3 class="font-semibold text-gray-800">Pallets de la recepción</h3>
                    <p class="text-xs text-gray-500">El estado de cada pallet es independiente. Cerrar la recepción no ingresa ni elimina los pallets pendientes.</p></div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50"><tr><th class="p-3 text-left">Pallet</th><th class="p-3 text-left">Documento</th><th class="p-3 text-left">Estado</th><th class="p-3 text-left">Temporal</th><th class="p-3 text-left">Definitiva</th><th class="p-3"></th></tr></thead>
                        <tbody class="divide-y">
                            @foreach($pallets as $p)
                                <tr data-row="{{ $p->id }}">
                                    <td class="p-3 font-mono font-semibold">{{ $p->pallet }}</td>
                                    <td class="p-3">{{ $p->id_registro }} <span class="text-xs text-gray-400">(tipo {{ $p->tipo_registro }})</span></td>
                                    <td class="p-3"><span class="estado-row rounded-full px-2 py-1 text-xs">{{ strtoupper(str_replace('_',' ',$p->estado)) }}</span></td>
                                    <td class="p-3">{{ $p->ubicacion_temporal ?: '—' }}</td>
                                    <td class="p-3">{{ $p->ubicacion_final ?: '—' }}</td>
                                    <td class="p-3 text-right"><button class="text-indigo-700 font-semibold hover:underline" onclick="seleccionarPallet({{ $p->id }})">Gestionar</button></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        const base = @json(url('/wms/recepcion-agencias/' . $cabecera->id));
        const csrf = @json(csrf_token());
        const recepcionCerrada = @json($cabecera->estado !== 'abierta');
        let palletActual = null, scanner = null;

        function alerta(texto, error=false) {
            $('#alerta').removeClass('hidden bg-red-100 text-red-800 bg-green-100 text-green-800')
                .addClass(error ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800').text(texto);
        }
        async function api(url, method='GET', body=null) {
            const opts = {method, headers:{'Accept':'application/json','X-CSRF-TOKEN':csrf}};
            if (body !== null) { opts.headers['Content-Type']='application/json'; opts.body=JSON.stringify(body); }
            const r = await fetch(url, opts); const data = await r.json();
            if (!r.ok) throw new Error(data.message || Object.values(data.errors || {}).flat().join(' ') || 'Error en la operación.');
            return data;
        }

        $('#selectPallet').select2({placeholder:'Buscar número de pallet...',width:'100%',minimumInputLength:0,
            ajax:{url:base+'/pallets/buscar',dataType:'json',delay:200,data:p=>({q:p.term}),processResults:d=>({results:d.results})}});
        $('#ubicacionTemporal').select2({placeholder:'Selecciona zona temporal',width:'100%',ajax:{url:@json(route('wms.recepcion-agencias.ubicaciones-temporales')),dataType:'json',processResults:d=>({results:d.results})}});
        $('#ubicacionFinal').select2({placeholder:'Selecciona galpón y ubicación definitiva',width:'100%',ajax:{url:@json(route('wms.recepcion-agencias.ubicaciones-normales')),dataType:'json',processResults:d=>({results:d.results})}});
        $('#selectPallet').on('select2:select', e => seleccionarPallet(e.params.data.id));

        async function seleccionarPallet(id) {
            try {
                const d = await api(base+'/pallets/'+id+'/detalle');
                palletActual = {id, data:d.pallet, detalles:d.detalles};
                const p = d.pallet;
                $('#gestion').removeClass('hidden');
                $('#palletTitulo').text(p.pallet);
                $('#palletMeta').text('Documento '+p.id_registro+' · Tipo '+p.tipo_registro);
                $('#estadoPallet').text(p.estado.replaceAll('_',' ').toUpperCase())
                    .attr('class','rounded-full px-3 py-1 text-xs font-bold '+(p.estado==='ingresado'?'bg-green-100 text-green-800':p.estado==='observado'?'bg-amber-100 text-amber-800':'bg-blue-100 text-blue-800'));
                $('#panelDescarga,#panelVerificacion,#panelIngreso').addClass('hidden');
                $('#detalleEstado').text('');
                const pendientes=d.observaciones.filter(o => o.estado !== 'resuelta');
                if (pendientes.length) {
                    $('#observacionesPallet').removeClass('hidden').html('<strong>Observaciones registradas:</strong><ul class="list-disc pl-5 mt-1"></ul>');
                    pendientes.forEach(o => $('#observacionesPallet ul').append($('<li>').text(o.descripcion+' · '+o.estado.toUpperCase())));
                } else {
                    $('#observacionesPallet').addClass('hidden').empty();
                }
                if (p.estado==='pendiente_descarga') $('#panelDescarga').removeClass('hidden');
                if (['descargado','observado'].includes(p.estado)) {
                    $('#panelVerificacion').removeClass('hidden');
                    renderDetalles(d.detalles);
                    $('#btnAutorizar').toggleClass('hidden',p.estado!=='observado');
                }
                if (['verificado','autorizado'].includes(p.estado)) $('#panelIngreso').removeClass('hidden');
                if (p.estado==='ingresado') $('#detalleEstado').text('Ingreso WMS registrado. El pallet ya está disponible en su ubicación definitiva.');
                if (recepcionCerrada) $('#panelDescarga,#panelVerificacion,#panelIngreso').addClass('hidden');
                actualizarFila(id,p);
            } catch(e) { alerta(e.message,true); }
        }

        const escapeHtml = value => $('<div>').text(value ?? '').html();

        function renderDetalles(items) {
            const html = items.map(x => `
                <tr data-detail-id="${x.id}">
                    <td class="p-2 min-w-48"><div class="font-mono font-semibold">${escapeHtml(x.codigo_esperado || 'Adicional')}</div><div class="text-xs text-gray-500">Lote ${escapeHtml(x.lote_esperado || '—')} · ${x.cantidad_esperada || 0} cajas</div></td>
                    <td class="p-2"><input class="field codigo-fisico" value="${escapeHtml(x.codigo_fisico || x.codigo_esperado || '')}" required></td>
                    <td class="p-2"><input class="field lote-fisico" value="${escapeHtml(x.lote_fisico || x.lote_esperado || '')}"></td>
                    <td class="p-2"><input class="field cantidad-fisica" type="number" min="0" value="${x.cantidad_fisica ?? x.cantidad_esperada ?? 0}" required></td>
                    <td class="p-2"><input class="field observacion-linea" value="${escapeHtml(x.observacion || '')}" placeholder="Opcional"></td>
                </tr>`).join('');
            $('#detalleVerificacion').html(html);
            $('#observacionGeneral').val('');
        }
        $('#btnAgregarDetalle').on('click',()=>$('#detalleVerificacion').append(`
            <tr data-detail-id="">
                <td class="p-2 text-amber-700 font-semibold">Producto adicional</td>
                <td class="p-2"><input class="field codigo-fisico" placeholder="Código" required></td>
                <td class="p-2"><input class="field lote-fisico" placeholder="Lote"></td>
                <td class="p-2"><input class="field cantidad-fisica" type="number" min="0" value="0" required></td>
                <td class="p-2"><input class="field observacion-linea" placeholder="Motivo del adicional"></td>
            </tr>`));

        async function actualizarFila(id,p) {
            const row=$(`tr[data-row="${id}"]`);
            row.find('.estado-row').text(p.estado.replaceAll('_',' ').toUpperCase())
                .attr('class','estado-row rounded-full px-2 py-1 text-xs '+(p.estado==='ingresado'?'bg-green-100 text-green-800':p.estado==='observado'?'bg-amber-100 text-amber-800':'bg-blue-100 text-blue-800'));
        }
        $('#btnDescargar').on('click',async()=>{
            try {
                const loc=$('#ubicacionTemporal').val(); if(!loc) throw new Error('Selecciona una ubicación temporal.');
                await api(base+'/pallets/'+palletActual.id+'/descargar','POST',{ubicacion_temporal_id:loc});
                alerta('Descarga registrada. Ahora puedes verificar este pallet.');
                await seleccionarPallet(palletActual.id);
                setTimeout(()=>location.reload(),700);
            } catch(e){alerta(e.message,true);}
        });
        $('#btnVerificar').on('click',async()=>{
            try {
                const detalles=[];
                $('#detalleVerificacion tr').each(function(){
                    detalles.push({id:$(this).attr('data-detail-id')||null,codigo_fisico:$(this).find('.codigo-fisico').val(),
                        lote_fisico:$(this).find('.lote-fisico').val(),cantidad_fisica:parseInt($(this).find('.cantidad-fisica').val(),10),
                        observacion:$(this).find('.observacion-linea').val()});
                });
                const d=await api(base+'/pallets/'+palletActual.id+'/verificar','POST',{detalles,observacion_general:$('#observacionGeneral').val()});
                alerta(d.message); setTimeout(()=>location.reload(),900);
            } catch(e){alerta(e.message,true);}
        });
        $('#btnAutorizar').on('click',async()=>{
            if(!confirm('¿Autorizar excepcionalmente el ingreso de este pallet con las diferencias registradas?')) return;
            try {const d=await api(base+'/pallets/'+palletActual.id+'/autorizar','POST',{});alerta(d.message);setTimeout(()=>location.reload(),700);}
            catch(e){alerta(e.message,true);}
        });
        $('#btnIngresar').on('click',async()=>{
            try {
                const loc=$('#ubicacionFinal').val(); if(!loc) throw new Error('Selecciona la ubicación definitiva.');
                if(!confirm('¿Confirmas el ingreso definitivo de este pallet al stock WMS?')) return;
                const d=await api(base+'/pallets/'+palletActual.id+'/ingresar','POST',{ubicacion_id:loc});
                alerta(d.message);setTimeout(()=>location.reload(),900);
            } catch(e){alerta(e.message,true);}
        });

        $('#btnCerrar').on('click',async()=>{
            if(!confirm('¿Cerrar esta recepción? Los pallets pendientes conservarán su estado y no ingresarán al stock.')) return;
            try {const d=await api(base+'/cerrar','POST',{});alerta(d.message);setTimeout(()=>location.reload(),900);}
            catch(e){alerta(e.message,true);}
        });

        $('#btnScan').on('click',async()=>{
            if(!window.Html5Qrcode) return alerta('No se pudo cargar el lector QR. Utiliza la búsqueda manual.',true);
            $('#readerWrap').removeClass('hidden');
            scanner = new Html5Qrcode('reader');
            try {
                await scanner.start({facingMode:'environment'},{fps:10,qrbox:{width:230,height:180}},
                    async decoded => {
                        await scanner.stop(); $('#readerWrap').addClass('hidden');
                        const value=decoded.trim();
                        try {
                            const encontrados=await api(base+'/pallets/buscar?q='+encodeURIComponent(value));
                            const match=encontrados.results.find(x => String(x.pallet).toUpperCase()===value.toUpperCase());
                            if(!match) throw new Error('El QR no corresponde a un pallet de esta recepción.');
                            const option=new Option(match.text,match.id,true,true);
                            $('#selectPallet').append(option).trigger('change');
                            seleccionarPallet(match.id);
                        } catch(err) { alerta(err.message,true); }
                    },()=>{});
            } catch(e){alerta('No se pudo activar la cámara. Verifica los permisos del navegador.',true);}
        });
        $('#btnStopScan').on('click',async()=>{if(scanner){try{await scanner.stop();}catch(e){}}$('#readerWrap').addClass('hidden');});
    </script>
</x-app-layout>
