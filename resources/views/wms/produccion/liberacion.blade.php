<x-app-layout>
<x-slot name="header"><h2 class="font-semibold text-xl text-gray-800 leading-tight">WMS - Liberación de Producción RG-CB-36</h2></x-slot>

<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-rc.0/css/select2.min.css" rel="stylesheet" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-rc.0/js/select2.min.js"></script>
<style>
.select2-container { width: 100% !important; }
.select2-container .select2-selection--single { height: 42px !important; display:flex; align-items:center; border-radius:.5rem !important; border-color:#d1d5db !important; }
.select2-container--default .select2-selection--single .select2-selection__rendered { line-height:42px !important; padding-left:12px !important; font-size:.875rem; }
.select2-container--default .select2-selection--single .select2-selection__arrow { height:40px !important; }
</style>
<div class="py-4 px-3 sm:py-6 sm:px-4"><div class="max-w-6xl mx-auto">
<a href="{{ route('wms.index') }}" class="text-sm text-gray-600 mb-3 inline-flex items-center gap-1">&larr; Volver</a><div id="alertBox" class="hidden mb-4 p-3 rounded-lg text-sm"></div>
<form id="formLiberacion" class="space-y-4">@csrf
<div class="bg-white shadow rounded-xl p-4 sm:p-5"><h3 class="font-bold mb-4">Datos de la liberación</h3>
<div class="grid grid-cols-1 md:grid-cols-4 gap-3">
<div><label class="block text-sm font-medium mb-1">Almacén operativo</label><input value="{{ $almacen->codigo }} · {{ $almacen->nombre }}" readonly class="w-full bg-gray-100 border-gray-300 rounded-lg text-gray-700 font-semibold"><p class="text-xs text-gray-500 mt-1">Asignado automáticamente al usuario.</p></div>
<div><label class="block text-sm font-medium mb-1">Prefijo documental</label><input value="{{ $almacen->prefijo_documento }}" readonly class="w-full bg-gray-100 border-gray-300 rounded-lg text-gray-700 font-semibold"><p class="text-xs text-gray-500 mt-1">Se utilizará en la documentación WMS.</p></div>
<div><label class="block text-sm font-medium mb-1">Fecha de entrega</label><input name="fecha_entrega" type="date" value="{{ now()->toDateString() }}" required class="w-full border-gray-300 rounded-lg"></div>
<div><label class="block text-sm font-medium mb-1">Folio físico RG-CB-36</label><input name="folio_fisico" class="w-full border-gray-300 rounded-lg" placeholder="Ej. 008845"></div>
</div>
<div class="mt-3"><label class="block text-sm font-medium mb-1">Formato</label><select name="formato" id="formato" required class="w-full border-gray-300 rounded-lg"><option value="">Seleccione...</option>@foreach($formatos as $formato)<option value="{{ $formato->codigo }}">{{ $formato->codigo }} · {{ $formato->descripcion }}</option>@endforeach</select></div></div>

<div class="bg-white shadow rounded-xl p-4 sm:p-5"><div class="flex justify-between items-center gap-3 mb-4"><div><h3 class="font-bold">Detalle de producción</h3><p class="text-xs text-gray-500">El producto se selecciona del catálogo; calidad y descripción se obtienen automáticamente del maestro de stock.</p></div><button type="button" id="btnAgregar" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-2 rounded-lg font-semibold">+ Agregar fila</button></div>
<div id="lineas" class="space-y-3"></div><div class="mt-4 border-t pt-4 flex flex-col sm:flex-row justify-between gap-3"><div><label class="block text-sm font-medium mb-1">Observaciones</label><textarea name="observaciones" rows="2" class="w-full sm:w-96 border-gray-300 rounded-lg"></textarea></div><div class="text-right self-end"><p class="text-xs text-gray-500 uppercase">Total general declarado</p><p id="total" class="text-3xl font-bold">0</p></div></div></div>
<div class="flex justify-end"><button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-bold px-5 py-3 rounded-lg">EMITIR LIBERACIÓN</button></div>
</form></div></div>

<template id="lineaTemplate"><div class="linea border rounded-xl p-3 bg-gray-50"><div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end">
<div class="md:col-span-5"><label class="block text-xs font-medium mb-1">Producto / Modelo</label><select class="producto w-full border-gray-300 rounded-lg" required></select></div>
<div class="md:col-span-2"><label class="block text-xs font-medium mb-1">Calidad</label><div class="calidad w-full min-h-[42px] flex items-center px-3 border border-gray-300 rounded-lg bg-gray-100 font-semibold text-gray-700">—</div></div>
<div class="md:col-span-2"><label class="block text-xs font-medium mb-1">Cajas</label><input class="cantidad w-full border-gray-300 rounded-lg" type="number" min="1" required></div>
<div class="md:col-span-1 extra"><label class="block text-xs font-medium mb-1">Tono</label><input class="tono w-full border-gray-300 rounded-lg" type="number" min="0" max="999"></div>
<div class="md:col-span-1 extra"><label class="block text-xs font-medium mb-1">Calibre</label><input class="calibre w-full border-gray-300 rounded-lg" type="number" min="0" max="99"></div>
<div class="md:col-span-1"><button type="button" class="eliminar w-full bg-red-100 text-red-700 px-2 py-2 rounded-lg font-semibold">×</button></div>
</div><div class="mt-2 text-xs text-gray-500 preview"></div></div></template>

<script>
const form=document.getElementById('formLiberacion'),contenedor=document.getElementById('lineas'),template=document.getElementById('lineaTemplate'),formato=document.getElementById('formato');
document.getElementById('btnAgregar').addEventListener('click',agregarFila);form.addEventListener('submit',guardar);
function agregarFila(){if(!formato.value){mostrarAlerta('Primero selecciona el formato.','error');return;}const fila=template.content.cloneNode(true).querySelector('.linea'),producto=fila.querySelector('.producto');producto.innerHTML='<option value="">Seleccione producto...</option>';
fila.querySelector('.eliminar').addEventListener('click',()=>{fila.remove();recalcular();});fila.querySelector('.cantidad').addEventListener('input',recalcular);contenedor.appendChild(fila);configurarBusqueda(producto);actualizarExtraFila(fila);}
function configurarBusqueda(select){
  $(select).select2({placeholder:'Escribe código, descripción o modelo...',allowClear:true,minimumInputLength:1,width:'100%',ajax:{url:"{{ route('wms.produccion.liberacion.productos.buscar') }}",dataType:'json',delay:300,data:params=>({q:params.term||'',formato:formato.value}),processResults:data=>({results:data.results||[]}),cache:true}});
  $(select).on('select2:select',function(e){const data=e.params.data;$(this).data('producto',data);const fila=$(this).closest('.linea')[0];const calidad=fila.querySelector('.calidad');calidad.textContent=data.calidad||'OTRO';actualizarExtraFila(fila);fila.querySelector('.preview').textContent=data.descripcion2?(data.codigo+' · '+data.descripcion+' · '+data.descripcion2):(data.codigo+' · '+(data.descripcion||''));});
  $(select).on('select2:clear',function(){ $(this).removeData('producto');const fila=$(this).closest('.linea')[0];fila.querySelector('.calidad').textContent='—';fila.querySelector('.preview').textContent='';actualizarExtraFila(fila); });
}
function actualizarExtraFila(fila){const calidad=fila.querySelector('.calidad').textContent.trim();fila.querySelectorAll('.extra').forEach(el=>el.style.display=calidad==='EXTRA'?'':'none');}
function recalcular(){let total=0;contenedor.querySelectorAll('.cantidad').forEach(i=>total+=Number(i.value||0));document.getElementById('total').textContent=total;}
async function guardar(e){e.preventDefault();const lineas=[...contenedor.querySelectorAll('.linea')].map(fila=>{const producto=fila.querySelector('.producto'),data=$(producto).data('producto')||{};return{codigo:producto.value, cantidad:Number(fila.querySelector('.cantidad').value), tono:fila.querySelector('.tono').value||null, calibre:fila.querySelector('.calibre').value||null};});if(!lineas.length){mostrarAlerta('Agrega al menos una línea de producción.','error');return;}const payload=Object.fromEntries(new FormData(form).entries());payload.lineas=lineas;const res=await fetch("{{ route('wms.produccion.liberacion.store') }}",{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('[name="_token"]').value,'Accept':'application/json'},body:JSON.stringify(payload)});const data=await res.json();if(!res.ok){mostrarAlerta(data.message||Object.values(data.errors||{}).flat().join(' ')||'No fue posible emitir la liberación.','error');return;}window.location.href=data.redirect;}
function mostrarAlerta(mensaje,tipo){const box=document.getElementById('alertBox');box.className='mb-4 p-3 rounded-lg text-sm '+(tipo==='success'?'bg-green-100 text-green-800':'bg-red-100 text-red-800');box.textContent=mensaje;box.classList.remove('hidden');}agregarFila();
</script></x-app-layout>