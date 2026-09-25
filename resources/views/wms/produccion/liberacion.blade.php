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

<div class="bg-white shadow rounded-xl p-4 sm:p-5"><div class="flex justify-between items-center gap-3 mb-4"><div><h3 class="font-bold">Detalle de producción</h3><p class="text-xs text-gray-500">Seleccione primero la calidad y luego busque el producto. El catálogo se filtra por planta, formato y calidad.</p></div><button type="button" id="btnAgregar" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-2 rounded-lg font-semibold">+ Agregar fila</button></div>
<div id="lineas" class="space-y-3"></div><div class="mt-4 border-t pt-4 flex flex-col sm:flex-row justify-between gap-3"><div><label class="block text-sm font-medium mb-1">Observaciones</label><textarea name="observaciones" rows="2" class="w-full sm:w-96 border-gray-300 rounded-lg"></textarea></div><div class="text-right self-end"><p class="text-xs text-gray-500 uppercase">Total general declarado</p><p id="total" class="text-3xl font-bold">0</p></div></div></div>
<div class="flex justify-end"><button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-bold px-5 py-3 rounded-lg">EMITIR LIBERACIÓN</button></div>
</form></div></div>

<template id="lineaTemplate"><div class="linea border rounded-xl p-3 bg-gray-50"><div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end">
<div class="md:col-span-3"><label class="block text-xs font-medium mb-1">Calidad</label><select class="calidad w-full border-gray-300 rounded-lg" required><option value="">Seleccione...</option><option value="EXTRA">EXTRA</option><option value="COMERCIAL">COMERCIAL</option><option value="ECONOMICO">ECONOMICO</option></select></div>
<div class="md:col-span-5"><label class="block text-xs font-medium mb-1">Producto / Modelo</label><select class="producto w-full border-gray-300 rounded-lg" required disabled></select></div>
<div class="md:col-span-2"><label class="block text-xs font-medium mb-1">Cajas</label><input class="cantidad w-full border-gray-300 rounded-lg" type="number" min="1" required></div>
<div class="md:col-span-1 extra"><label class="block text-xs font-medium mb-1">Tono</label><input class="tono w-full border-gray-300 rounded-lg" type="number" min="0" max="999"></div>
<div class="md:col-span-1 extra"><label class="block text-xs font-medium mb-1">Calibre</label><input class="calibre w-full border-gray-300 rounded-lg" type="number" min="0" max="99"></div>
<div class="md:col-span-1"><button type="button" class="eliminar w-full bg-red-100 text-red-700 px-2 py-2 rounded-lg font-semibold">×</button></div>
</div><div class="mt-2 text-xs text-gray-500 preview"></div></div></template>

<script>
const form=document.getElementById('formLiberacion'),contenedor=document.getElementById('lineas'),template=document.getElementById('lineaTemplate'),formato=document.getElementById('formato');
document.getElementById('btnAgregar').addEventListener('click',agregarFila);form.addEventListener('submit',guardar);
function agregarFila(){if(!formato.value){mostrarAlerta('Primero selecciona el formato.','error');return;}const fila=template.content.cloneNode(true).querySelector('.linea'),producto=fila.querySelector('.producto'),calidad=fila.querySelector('.calidad');producto.innerHTML='<option value="">Seleccione producto...</option>';
fila.querySelector('.eliminar').addEventListener('click',()=>{fila.remove();recalcular();});fila.querySelector('.cantidad').addEventListener('input',recalcular);calidad.addEventListener('change',()=>{limpiarProducto(fila);actualizarExtraFila(fila);});contenedor.appendChild(fila);configurarBusqueda(producto,calidad);actualizarExtraFila(fila);}
function limpiarProducto(fila){const producto=fila.querySelector('.producto');if($(producto).hasClass('select2-hidden-accessible')){$(producto).val(null).trigger('change');}producto.disabled=!fila.querySelector('.calidad').value;$(producto).removeData('producto');fila.querySelector('.preview').textContent='';}
function configurarBusqueda(select,calidad){
  $(select).select2({placeholder:'Seleccione calidad primero...',allowClear:true,minimumInputLength:1,width:'100%',ajax:{url:"{{ route('wms.produccion.liberacion.productos.buscar') }}",dataType:'json',delay:300,data:params=>({q:params.term||'',formato:formato.value,calidad:calidad.value}),processResults:data=>({results:data.results||[]}),cache:true,error:function(xhr){const mensaje=xhr.responseJSON?.message||'No fue posible consultar el catálogo de productos.';console.error('WMS RG-CB-36 - búsqueda de productos',xhr.status,xhr.responseText);mostrarAlerta(mensaje,'error');}}});
  $(select).on('select2:opening',function(e){if(!calidad.value){e.preventDefault();mostrarAlerta('Primero selecciona la calidad del producto.','error');}});
  $(select).on('select2:select',function(e){const data=e.params.data;$(this).data('producto',data);const fila=$(this).closest('.linea')[0];const calidadValor=data.calidad||calidad.value||'OTRO';fila.querySelector('.preview').textContent=data.descripcion2?(data.codigo+' · '+data.descripcion+' · '+data.descripcion2):(data.codigo+' · '+(data.descripcion||''));actualizarExtraFila(fila);});
  $(select).on('select2:clear',function(){ $(this).removeData('producto');const fila=$(this).closest('.linea')[0];fila.querySelector('.preview').textContent='';actualizarExtraFila(fila); });
}
function actualizarExtraFila(fila){const calidad=fila.querySelector('.calidad').value;fila.querySelectorAll('.extra').forEach(el=>el.style.display=calidad==='EXTRA'?'':'none');}
function recalcular(){let total=0;contenedor.querySelectorAll('.cantidad').forEach(i=>total+=Number(i.value||0));document.getElementById('total').textContent=total;}
async function guardar(e){e.preventDefault();const lineas=[...contenedor.querySelectorAll('.linea')].map(fila=>{const producto=fila.querySelector('.producto'),calidad=fila.querySelector('.calidad').value;return{codigo:producto.value,calidad:calidad,cantidad:Number(fila.querySelector('.cantidad').value),tono:fila.querySelector('.tono').value||null,calibre:fila.querySelector('.calibre').value||null};});if(!lineas.length){mostrarAlerta('Agrega al menos una línea de producción.','error');return;}if(lineas.some(l=>!l.calidad||!l.codigo)){mostrarAlerta('Completa la calidad y selecciona el producto en todas las líneas.','error');return;}const payload=Object.fromEntries(new FormData(form).entries());payload.lineas=lineas;const res=await fetch("{{ route('wms.produccion.liberacion.store') }}",{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('[name="_token"]').value,'Accept':'application/json'},body:JSON.stringify(payload)});const data=await res.json();if(!res.ok){mostrarAlerta(data.message||Object.values(data.errors||{}).flat().join(' ')||'No fue posible emitir la liberación.','error');return;}window.location.href=data.redirect;}
function mostrarAlerta(mensaje,tipo){const box=document.getElementById('alertBox');box.className='mb-4 p-3 rounded-lg text-sm '+(tipo==='success'?'bg-green-100 text-green-800':'bg-red-100 text-red-800');box.textContent=mensaje;box.classList.remove('hidden');}agregarFila();
</script></x-app-layout>