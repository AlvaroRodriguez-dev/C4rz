<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800 leading-tight">WMS - Kardex</h2></x-slot>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-rc.0/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-rc.0/js/select2.min.js"></script>
    <style>
      .select2-container .select2-selection--single{height:46px!important;display:flex;align-items:center;border-radius:.75rem!important;border-color:#d1d5db!important}
      .select2-container--default .select2-selection--single .select2-selection__rendered{line-height:46px!important;padding-left:12px!important}
      .kardex-card{transition:box-shadow .15s ease}
      .kardex-card:hover{box-shadow:0 3px 12px rgba(0,0,0,.07)}
    </style>
    <div class="px-3 py-4 sm:px-5 sm:py-6">
      <div class="max-w-5xl mx-auto">
        <a href="{{ route('wms.index') }}" class="text-sm text-gray-600 inline-block mb-3">&larr; Volver</a>
        <div id="alertBox" class="hidden mb-4 p-3 rounded-lg text-sm"></div>
        <section class="bg-white shadow-sm border border-gray-100 rounded-2xl p-4 sm:p-5 mb-5">
          <h3 class="font-semibold text-gray-800 mb-4">Consulta de movimientos</h3>
          <div class="space-y-3">
            <div><label class="block text-sm font-medium mb-1">Almacén</label>
              <select id="selectAlmacen" class="w-full border-gray-300 rounded-lg">
                <option value="">Selecciona un almacén...</option>
                @foreach($almacenes as $almacen)
                  <option value="{{ $almacen->id }}" {{ $almacenes->count() === 1 ? 'selected' : '' }}>{{ $almacen->nombre }}</option>
                @endforeach
              </select>
            </div>
            <div class="grid grid-cols-2 gap-3">
              <div><label class="block text-sm font-medium mb-1">Fecha inicio</label><input type="date" id="fechaInicio" class="w-full border-gray-300 rounded-lg"></div>
              <div><label class="block text-sm font-medium mb-1">Fecha fin</label><input type="date" id="fechaFin" class="w-full border-gray-300 rounded-lg"></div>
            </div>
            <div><label class="block text-sm font-medium mb-1">Producto</label><select id="selectProducto" class="w-full" style="width:100%"></select></div>
            <details class="border border-gray-200 rounded-xl overflow-hidden">
              <summary class="cursor-pointer select-none px-4 py-3 bg-gray-50 hover:bg-gray-100 text-sm font-medium text-gray-700 flex items-center justify-between">
                <span>Filtros adicionales (Lote, Galpón, Ubicación y Pallet)</span>
                <span class="text-gray-400 text-xs">Desplegar / ocultar</span>
              </summary>
              <div class="grid sm:grid-cols-2 gap-3 p-3">
                <div><label class="block text-sm font-medium mb-1">Lote</label><select id="selectLote" class="w-full" disabled></select></div>
                <div><label class="block text-sm font-medium mb-1">Galpón</label><select id="selectGalpon" class="w-full" disabled></select></div>
                <div><label class="block text-sm font-medium mb-1">Ubicación</label><select id="selectUbicacion" class="w-full" disabled></select></div>
                <div><label class="block text-sm font-medium mb-1">Pallet</label><select id="selectPallet" class="w-full" disabled></select></div>
              </div>
            </details>
            <div class="flex gap-2">
              <button id="btnGenerar" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 rounded-xl">GENERAR KARDEX</button>
              <button id="btnLimpiar" class="px-4 border rounded-xl text-gray-700 hover:bg-gray-50">Limpiar</button>
            </div>
          </div>
        </section>
        <div id="resultado" class="hidden space-y-4">
          <section class="grid grid-cols-2 lg:grid-cols-4 gap-3">
            <div class="bg-white border rounded-2xl p-3"><p class="text-xs text-gray-500">Saldo inicial</p><p id="kpiInicial" class="text-xl font-bold text-gray-800">—</p></div>
            <div class="bg-white border rounded-2xl p-3"><p class="text-xs text-gray-500">Entradas</p><p id="totalEntradas" class="text-xl font-bold text-green-700">0</p></div>
            <div class="bg-white border rounded-2xl p-3"><p class="text-xs text-gray-500">Salidas</p><p id="totalSalidas" class="text-xl font-bold text-red-700">0</p></div>
            <div class="bg-blue-50 border border-blue-100 rounded-2xl p-3"><p class="text-xs text-blue-700">Saldo final</p><p id="saldoFinal" class="text-xl font-bold text-blue-800">0</p></div>
          </section>
          <section class="bg-white border rounded-2xl p-3 sm:p-4 space-y-3">
            <div class="flex flex-col sm:flex-row gap-2">
              <input id="buscarMovimiento" type="search" placeholder="Buscar documento, pallet, lote, usuario..." class="flex-1 border-gray-300 rounded-xl">
              <button id="btnExportar" class="border rounded-xl px-4 py-2 text-sm font-medium hover:bg-gray-50">Exportar CSV</button>
            </div>
            <div class="flex flex-wrap gap-2" id="filtrosTipo">
              <button data-tipo="ALL" class="type-filter rounded-full px-3 py-1 text-sm border bg-gray-900 text-white">Todos</button>
              <button data-tipo="INGRESO" class="type-filter rounded-full px-3 py-1 text-sm border">Entradas</button>
              <button data-tipo="SALIDA" class="type-filter rounded-full px-3 py-1 text-sm border">Salidas</button>
              <button data-tipo="REUBICACIÓN" class="type-filter rounded-full px-3 py-1 text-sm border">Reubicaciones</button>
              <button data-tipo="AJUSTE" class="type-filter rounded-full px-3 py-1 text-sm border">Ajustes</button>
            </div>
            <div class="text-xs text-gray-500">Las reubicaciones se muestran como movimientos de ubicación y no representan una entrada o salida real del almacén.</div>
          </section>
          <div id="filas" class="space-y-3"></div>
          <div id="contadorMovimientos" class="text-center text-xs text-gray-500"></div>
        </div>
        <div id="sinDatos" class="hidden text-center text-gray-500 py-8">No hay movimientos con los filtros seleccionados.</div>
      </div>
    </div>
    <div id="detalleModal" class="hidden fixed inset-0 z-50 bg-black/50 p-3 flex items-end sm:items-center justify-center">
      <div id="detalleModalPanel" class="bg-white rounded-2xl w-full max-w-lg p-5 max-h-[85vh] overflow-auto border-t-4 border-gray-400">
        <div class="flex justify-between items-center mb-3"><h3 class="font-semibold text-lg">Detalle del movimiento</h3><button id="cerrarDetalle" class="text-gray-500 text-xl">&times;</button></div>
        <div id="detalleContenido" class="space-y-2 text-sm"></div>
        <button id="detalleFiltrarPallet" class="hidden mt-4 w-full border border-blue-200 text-blue-700 rounded-xl p-2">Consultar este pallet</button>
      </div>
    </div>
<script>
const routeProductos = "{{ route('wms.inventario.productos.buscar') }}";
const routeLotes = c => `{{ url('wms/kardex/producto') }}/${encodeURIComponent(c)}/lotes`;
const routeGalpones = c => `{{ url('wms/kardex/producto') }}/${encodeURIComponent(c)}/galpones`;
const routeUbicaciones = c => `{{ url('wms/kardex/producto') }}/${encodeURIComponent(c)}/ubicaciones`;
const routePallets = c => `{{ url('wms/kardex/producto') }}/${encodeURIComponent(c)}/pallets`;
const routeReporte = "{{ route('wms.kardex.reporte') }}";
let codigoSeleccionado=null, reporteActual=null, filtroTipo='ALL', movimientoDetalle=null, movimientosVisibles=[];
const almacen=()=>$('#selectAlmacen').val();
const conAlmacen=url=>url+(url.includes('?')?'&':'?')+'almacen='+encodeURIComponent(almacen());
$(function(){
 $('#selectProducto').select2({placeholder:'Selecciona un producto...',minimumInputLength:1,width:'100%',ajax:{url:routeProductos,dataType:'json',delay:300,data:p=>({q:p.term}),processResults:d=>({results:d.results})}});
 ['#selectLote','#selectGalpon','#selectUbicacion','#selectPallet'].forEach(s=>$(s).select2({placeholder:'Todos',width:'100%',allowClear:true}));
 $('#selectAlmacen').on('change',resetFiltrosProducto);
 $('#selectProducto').on('select2:select',e=>{
  codigoSeleccionado=e.params.data.id;resetDependientes();
  $('#selectLote,#selectGalpon,#selectUbicacion,#selectPallet').prop('disabled',false);
  cargar(routeLotes(codigoSeleccionado),'#selectLote');
  cargar(routeGalpones(codigoSeleccionado),'#selectGalpon');
  cargar(routeUbicaciones(codigoSeleccionado),'#selectUbicacion');
  cargar(routePallets(codigoSeleccionado),'#selectPallet');
 });
 $('#selectProducto').on('select2:clear',resetFiltrosProducto);
 $('#selectLote').on('change',()=>{if(!codigoSeleccionado)return;const q=new URLSearchParams();if($('#selectLote').val())q.set('clote',$('#selectLote').val());cargar(routeGalpones(codigoSeleccionado)+(q.toString()?'?'+q:''),'#selectGalpon');cargar(routeUbicaciones(codigoSeleccionado)+(q.toString()?'?'+q:''),'#selectUbicacion');});
 $('#selectGalpon').on('change',()=>{if(!codigoSeleccionado)return;const q=new URLSearchParams();if($('#selectLote').val())q.set('clote',$('#selectLote').val());if($('#selectGalpon').val())q.set('galpon',$('#selectGalpon').val());cargar(routeUbicaciones(codigoSeleccionado)+'?'+q,'#selectUbicacion');});
 $('#btnGenerar').on('click',generar);$('#btnLimpiar').on('click',limpiar);
 $('#buscarMovimiento').on('input',renderFiltrado);
 $('.type-filter').on('click',function(){filtroTipo=$(this).data('tipo');$('.type-filter').removeClass('bg-gray-900 text-white').addClass('bg-white text-gray-700');$(this).removeClass('bg-white text-gray-700').addClass('bg-gray-900 text-white');renderFiltrado();});
 $('#btnExportar').on('click',exportarCSV);$('#cerrarDetalle').on('click',()=>$('#detalleModal').addClass('hidden'));
 $('#detalleFiltrarPallet').on('click',()=>{if(movimientoDetalle?.pallet){const p=movimientoDetalle.pallet;let opt=$('#selectPallet option').filter((i,o)=>o.value===p);if(!opt.length)$('#selectPallet').append(new Option(p,p,true,true));$('#selectPallet').val(p).trigger('change');$('#detalleModal').addClass('hidden');generar();}});
});
function resetFiltrosProducto(){codigoSeleccionado=null;$('#selectProducto').val(null).trigger('change');resetDependientes();$('#selectLote,#selectGalpon,#selectUbicacion,#selectPallet').prop('disabled',true);$('#resultado,#sinDatos').addClass('hidden');}
function resetDependientes(){['#selectLote','#selectGalpon','#selectUbicacion','#selectPallet'].forEach(s=>{$(s).empty().append(new Option('', '',false,false)).val(null).trigger('change');});}
function cargar(url,sel){fetch(conAlmacen(url)).then(r=>r.json()).then(d=>{const s=$(sel);s.empty().append(new Option('', '',false,false));(d.results||[]).forEach(x=>s.append(new Option(x.text,x.id,false,false)));s.trigger('change');}).catch(()=>mostrarAlerta('No se pudieron cargar los filtros del producto.','error'));}
function generar(){if(!almacen()){mostrarAlerta('Selecciona un almacén.','error');return;}if(!codigoSeleccionado){mostrarAlerta('Selecciona un producto.','error');return;}if(!$('#fechaInicio').val()||!$('#fechaFin').val()){mostrarAlerta('Selecciona fecha inicial y final.','error');return;}if($('#fechaInicio').val()>$('#fechaFin').val()){mostrarAlerta('La fecha inicial no puede ser posterior a la fecha final.','error');return;}
 const p=new URLSearchParams({codigo:codigoSeleccionado,fecha_inicio:$('#fechaInicio').val(),fecha_fin:$('#fechaFin').val(),almacen:almacen()});[['clote','#selectLote'],['galpon','#selectGalpon'],['ubicacion','#selectUbicacion'],['pallet','#selectPallet']].forEach(([k,s])=>{const v=$(s).val();if(v)p.append(k,v);});
 $('#btnGenerar').prop('disabled',true).text('CONSULTANDO...');
 fetch(routeReporte+'?'+p).then(async r=>{const d=await r.json();if(!r.ok)throw new Error(Object.values(d.errors||{}).flat().join(' ')||d.message||'Error al consultar');reporteActual=d;renderResultado(d);}).catch(e=>mostrarAlerta(e.message,'error')).finally(()=>$('#btnGenerar').prop('disabled',false).text('GENERAR KARDEX'));
}
function renderResultado(d){const filas=d.filas||[];const inicial=filas.find(f=>f.tipo==='SALDO INICIAL');$('#kpiInicial').text(inicial?inicial.saldo:'0');$('#totalEntradas').text(d.total_entradas??0);$('#totalSalidas').text(d.total_salidas??0);$('#saldoFinal').text(d.saldo_final??0);$('#resultado').removeClass('hidden');$('#sinDatos').addClass('hidden');renderFiltrado();}
function renderFiltrado(){if(!reporteActual)return;const rows=reporteActual.filas||[],q=($('#buscarMovimiento').val()||'').toLowerCase().trim();const visibles=rows.filter(f=>{const tipo=(f.tipo||'').toUpperCase();let tipoOk=filtroTipo==='ALL'||(filtroTipo==='INGRESO'&&tipo.includes('INGRESO'))||(filtroTipo==='SALIDA'&&tipo==='SALIDA')||(filtroTipo==='REUBICACIÓN'&&tipo.includes('REUBICACIÓN'))||(filtroTipo==='AJUSTE'&&tipo.includes('AJUSTE'));const texto=[f.tipo,f.documento,f.detalle_ubicacion,f.clote,f.usuario,f.fecha,f.pallet].join(' ').toLowerCase();return tipoOk&&(!q||texto.includes(q));});const c=$('#filas');c.empty();movimientosVisibles=visibles;visibles.forEach((f,i)=>c.append(card(f,i)));$('#contadorMovimientos').text('Mostrando '+visibles.length+' de '+rows.length+' registros');if(!visibles.length){$('#sinDatos').removeClass('hidden');}else $('#sinDatos').addClass('hidden');}
function esc(v){return String(v??'—').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));}
function card(f,i){const t=f.tipo||'',isInitial=t==='SALDO INICIAL',isTransfer=t.includes('REUBICACIÓN'),isAdj=t.includes('AJUSTE'),isIn=Number(f.entrada)>0;const badge=isInitial?'bg-gray-100 text-gray-700':isTransfer?'bg-blue-100 text-blue-700':isAdj?'bg-orange-100 text-orange-700':isIn?'bg-green-100 text-green-700':'bg-red-100 text-red-700';const amount=isTransfer?'text-blue-700':isIn?'text-green-700':'text-red-700';const m=isInitial?'Saldo inicial':isTransfer?'Movimiento interno':isIn?'Entrada':'Salida';return `<button type="button" class="kardex-card text-left w-full bg-white border rounded-2xl p-4" onclick="abrirDetalleIndice(${i})"><div class="flex justify-between gap-3"><div class="min-w-0"><span class="text-xs font-semibold rounded-full px-2 py-1 ${badge}">${esc(t)}</span><div class="font-medium text-gray-800 mt-2 break-words">${esc(f.documento)}</div><div class="text-xs text-gray-500 mt-1">${esc(f.detalle_ubicacion)}</div><div class="text-xs text-gray-500 mt-1">Lote: ${esc(f.clote)}</div><div class="text-xs text-gray-400 mt-2">${esc(f.fecha)} ${isInitial?'':'· '+esc(f.usuario)}</div></div><div class="text-right shrink-0"><div class="font-bold ${amount}">${f.entrada?'+'+esc(f.entrada):f.salida?'-'+esc(f.salida):'—'}</div><div class="text-xs text-gray-500 mt-2">Saldo</div><div class="font-semibold text-gray-800">${esc(f.saldo)}</div></div></div></button>`;}
function abrirDetalleIndice(i){
 const f=movimientosVisibles[i];if(!f)return;movimientoDetalle=f;
 let documento=f.documento||'',pallet=f.pallet||'—';
 const transferencia=documento.match(/Pallet\s+[^→]+→\s+Pallet\s+.+/i);
 if(transferencia){pallet=transferencia[0];documento='Reubicación interna';}
 else {
  const match=documento.match(/\s*·\s*Pallet\s+(.+)$/i);
  if(match){pallet=match[1].trim();documento=documento.replace(match[0],'').trim();}
 }
 const tipo=(f.tipo||'').toUpperCase(),panel=$('#detalleModalPanel');
 panel.removeClass('border-green-500 border-red-500 border-blue-500 border-orange-500 border-gray-400');
 const borderColor=tipo.includes('INGRESO')?'border-green-500':tipo==='SALIDA'?'border-red-500':tipo.includes('REUBICACIÓN')?'border-blue-500':tipo.includes('AJUSTE')?'border-orange-500':'border-gray-400';
 panel.addClass(borderColor);
 const entries=[['Tipo',f.tipo],['Documento',documento],['Fecha',f.fecha],['Ubicación',f.detalle_ubicacion],['Lote',f.clote],['Usuario',f.usuario],['Entrada',f.entrada],['Salida',f.salida],['Saldo',f.saldo],['Pallet',pallet]];
 $('#detalleContenido').html(entries.map(([k,v])=>'<div class="flex justify-between gap-3 border-b py-2"><span class="text-gray-500">'+esc(k)+'</span><span class="font-medium text-right">'+esc(v)+'</span></div>').join(''));
 $('#detalleFiltrarPallet').toggle(!!pallet && pallet!=='—' && !pallet.includes('→'));
 $('#detalleModal').removeClass('hidden');
}
function exportarCSV(){if(!reporteActual)return;const rows=reporteActual.filas||[],cols=['fecha','tipo','documento','detalle_ubicacion','clote','usuario','entrada','salida','saldo'];const csv=[cols.join(';'),...rows.map(r=>cols.map(k=>'"'+String(r[k]??'').replace(/"/g,'""')+'"').join(';'))].join('\r\n');const blob=new Blob(['\ufeff'+csv],{type:'text/csv;charset=utf-8'});const a=document.createElement('a');a.href=URL.createObjectURL(blob);a.download='kardex_'+($('#selectAlmacen option:selected').text().trim())+'_'+($('#fechaInicio').val())+'_'+($('#fechaFin').val())+'.csv';a.click();URL.revokeObjectURL(a.href);}
function limpiar(){resetFiltrosProducto();$('#fechaInicio,#fechaFin,#buscarMovimiento').val('');reporteActual=null;$('#resultado,#sinDatos').addClass('hidden');$('#alertBox').addClass('hidden');filtroTipo='ALL';$('.type-filter').removeClass('bg-gray-900 text-white').addClass('bg-white text-gray-700').first().removeClass('bg-white text-gray-700').addClass('bg-gray-900 text-white');}
function mostrarAlerta(msg,tipo){const b=$('#alertBox');b.removeClass('hidden bg-green-100 text-green-800 bg-red-100 text-red-800').addClass(tipo==='success'?'bg-green-100 text-green-800':'bg-red-100 text-red-800').text(msg);window.scrollTo({top:0,behavior:'smooth'});}
</script>
</x-app-layout>