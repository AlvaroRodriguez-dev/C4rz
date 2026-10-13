<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">WMS - Verificación física de pallets</h2>
    </x-slot>
    <div class="py-4 px-3 sm:py-6 sm:px-4"><div class="max-w-6xl mx-auto">
        <div class="flex items-center justify-between gap-3 mb-4">
            <a href="{{ route('wms.index') }}" class="text-sm text-gray-600">&larr; Volver al WMS</a>
            <span class="text-xs text-gray-500">Almacén de Producto Terminado</span>
        </div>
        <div class="bg-white shadow rounded-xl p-4 sm:p-5 mb-4">
            <p class="text-sm text-gray-600 mb-4">Seleccione una liberación para verificar físicamente sus pallets. Registre las diferencias encontradas; Producción/Calidad realizará la corrección.</p>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div><label for="search" class="block text-sm font-medium text-gray-700 mb-1">Buscar liberación</label><input id="search" type="search" placeholder="Documento, folio u origen..." class="w-full border-gray-300 rounded-lg"></div>
                <div><label for="estado" class="block text-sm font-medium text-gray-700 mb-1">Estado</label><select id="estado" class="w-full border-gray-300 rounded-lg"><option value="">Pendientes de verificación</option><option value="PENDIENTE_VERIFICACION">Pendiente de verificación</option><option value="EN_VERIFICACION">En verificación</option><option value="PENDIENTE_REVERIFICACION">Pendiente de reverificación</option><option value="VERIFICADA">Verificada</option></select></div>
                <div class="flex items-end"><button id="btnBuscar" class="w-full bg-violet-600 hover:bg-violet-700 text-white font-semibold py-2.5 rounded-lg">BUSCAR</button></div>
            </div>
        </div>
        <div id="alertBox" class="hidden mb-4 p-3 rounded-lg text-sm"></div>
        <div class="bg-white shadow rounded-xl overflow-hidden"><div class="overflow-x-auto"><table class="w-full text-sm">
            <thead class="bg-gray-100 text-gray-600"><tr><th class="text-left p-3">Documento WMS</th><th class="text-left p-3">Folio</th><th class="text-left p-3">Origen</th><th class="text-left p-3">Fecha</th><th class="text-right p-3">Pallets</th><th class="text-left p-3">Estado</th><th class="text-right p-3">Acción</th></tr></thead>
            <tbody id="tabla"><tr><td colspan="7" class="p-6 text-center text-gray-500">Cargando liberaciones...</td></tr></tbody>
        </table></div><div id="paginacion" class="p-3 border-t"></div></div>
    </div></div>
    <script>
        const routeBuscar = @json(route('wms.produccion.liberacion.create', ['buscar' => 1]));
        const routeVerificacion = @json(url('wms/produccion-liberacion'));
        document.addEventListener('DOMContentLoaded', () => {
            document.getElementById('btnBuscar').addEventListener('click', () => cargar(1));
            document.getElementById('search').addEventListener('keydown', e => { if (e.key === 'Enter') cargar(1); });
            document.getElementById('estado').addEventListener('change', () => cargar(1));
            cargar(1);
        });
        async function cargar(page) {
            const params = new URLSearchParams({q: document.getElementById('search').value.trim(), estado: document.getElementById('estado').value, scope: 'verificacion', page});
            try {
                const res = await fetch(routeBuscar + '&' + params.toString(), {headers: {'Accept':'application/json'}, credentials:'same-origin'});
                if (!res.ok) throw new Error('No fue posible cargar las liberaciones.');
                const data = await res.json();
                const filtro = document.getElementById('estado').value;
                const filas = data.data.filter(x => ['PENDIENTE_VERIFICACION','EN_VERIFICACION','PENDIENTE_REVERIFICACION','VERIFICADA'].includes(x.estado) && Number(x.pallets)>0 && (!filtro || x.estado===filtro));
                document.getElementById('tabla').innerHTML = filas.length ? filas.map(fila).join('') : '<tr><td colspan="7" class="p-6 text-center text-gray-500">No hay liberaciones que coincidan con el filtro.</td></tr>';
                document.getElementById('paginacion').innerHTML = data.last_page>1 ? '<div class="flex justify-between items-center"><button class="px-3 py-2 border rounded-lg" '+(data.current_page<=1?'disabled':'')+' onclick="cargar('+(data.current_page-1)+')">Anterior</button><span class="text-sm text-gray-500">Página '+data.current_page+' de '+data.last_page+'</span><button class="px-3 py-2 border rounded-lg" '+(data.current_page>=data.last_page?'disabled':'')+' onclick="cargar('+(data.current_page+1)+')">Siguiente</button></div>' : '';
            } catch(e) { mostrarAlerta(e.message); }
        }
        function fila(x) {
            const link=routeVerificacion+'/'+x.id+'/verificacion-pallets';
            const accion=['PENDIENTE_VERIFICACION','EN_VERIFICACION','PENDIENTE_REVERIFICACION'].includes(x.estado) ? '<a class="inline-flex px-3 py-2 rounded-lg bg-violet-600 hover:bg-violet-700 text-white font-semibold" href="'+link+'">VERIFICAR PALLETS</a>' : '<a class="inline-flex px-3 py-2 rounded-lg bg-gray-600 hover:bg-gray-700 text-white font-semibold" href="'+routeVerificacion+'/'+x.id+'/pallets">CONSULTAR PALLETS</a>';
            return '<tr class="border-t hover:bg-gray-50"><td class="p-3 font-mono font-semibold">'+esc(x.documento)+'</td><td class="p-3">'+esc(x.folio_fisico)+'</td><td class="p-3">'+esc(x.origen)+'</td><td class="p-3">'+esc(x.fecha_entrega)+'</td><td class="p-3 text-right font-semibold">'+x.pallets+'</td><td class="p-3">'+esc(x.estado)+'</td><td class="p-3 text-right">'+accion+'</td></tr>';
        }
        function esc(v) { return String(v??'—').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c])); }
        function mostrarAlerta(msg) { const b=document.getElementById('alertBox'); b.className='mb-4 p-3 rounded-lg text-sm bg-red-100 text-red-800'; b.textContent=msg; b.classList.remove('hidden'); }
    </script>
</x-app-layout>