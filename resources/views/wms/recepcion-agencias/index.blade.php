<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">WMS · Recepción en agencias</h2>
    </x-slot>

    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-rc.0/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-rc.0/js/select2.min.js"></script>
    <style>
        .select2-container .select2-selection--multiple { min-height:46px!important;border:1px solid #d1d5db!important;border-radius:10px!important;padding:5px }
        .select2-container--default .select2-selection--multiple .select2-selection__choice { padding:4px 7px }
    </style>

    <div class="py-6 px-3 sm:px-6">
        <div class="max-w-6xl mx-auto space-y-5">
            <a href="{{ route('wms.index') }}" class="text-sm text-gray-600 hover:text-gray-900">← Volver al WMS</a>

            @if(!$almacen)
                <div class="rounded-xl border border-amber-300 bg-amber-50 p-5 text-amber-900" role="alert">
                    <div class="flex items-start gap-3">
                        <span class="text-2xl" aria-hidden="true">⚠️</span>
                        <div>
                            <h3 class="font-bold">Almacén principal no asignado</h3>
                            <p class="mt-1 text-sm">{{ $advertenciaAlmacen }}</p>
                            <p class="mt-2 text-sm font-semibold">La recepción de agencias está bloqueada hasta que se configure tu almacén principal.</p>
                        </div>
                    </div>
                </div>
            @else
            <div class="bg-white rounded-xl shadow p-4 sm:p-6">
                <h3 class="font-bold text-gray-800 text-lg">Nueva recepción de camión</h3>
                <p class="text-sm text-gray-500 mt-1">Asocia una o varias notas logísticas. Cada pallet conserva su documento de despacho original.</p>
                <div id="alerta" class="hidden mt-4 rounded-lg p-3 text-sm"></div>
                <form id="formRecepcion" class="mt-5 space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Identificación del camión (opcional)</label>
                        <input name="camion" maxlength="80" class="w-full rounded-lg border-gray-300" placeholder="Placa o referencia del camión">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Notas de despacho / programación / tránsito</label>
                        <select id="documentos" name="documentos[]" multiple class="w-full"></select>
                        <p class="text-xs text-gray-500 mt-1">Se muestran documentos dirigidos al almacén {{ $almacen->codigo }}. Los que aún no tienen salida física registrada en WMS aparecerán identificados y no podrán seleccionarse.</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Observación general</label>
                        <textarea name="observacion" rows="2" maxlength="500" class="w-full rounded-lg border-gray-300" placeholder="Observaciones de llegada del camión (opcional)"></textarea>
                    </div>
                    <button id="btnCrear" class="w-full sm:w-auto bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg px-6 py-3">Crear recepción y cargar pallets</button>
                </form>
            </div>
            @endif

            @if($almacen)
            @can('wms.configurar')
                <div class="bg-amber-50 border border-amber-200 rounded-xl p-4">
                    <h3 class="font-semibold text-amber-900">Zonas temporales de descarga</h3>
                    <p class="text-sm text-amber-800 mt-1">Crea las posiciones de staging que utilizará la agencia. Se guardan como ubicaciones tipo RECEPCION y no forman parte del stock normal.</p>
                    <form id="formZona" class="mt-3 flex flex-col sm:flex-row gap-2">
                        <input id="codigoZona" class="rounded-lg border-gray-300 flex-1" maxlength="30" placeholder="Ej. REC-01" required>
                        <button class="bg-amber-700 hover:bg-amber-800 text-white rounded-lg px-5 py-2 font-semibold">Crear zona temporal</button>
                    </form>
                    <div id="alertaZona" class="hidden text-sm mt-2"></div>
                </div>
            @endcan
            @endif

            @if($almacen)
            <div class="bg-white rounded-xl shadow overflow-hidden">
                <div class="p-4 sm:p-6 border-b">
                    <h3 class="font-bold text-gray-800 text-lg">Recepciones recientes</h3>
                    <p class="text-sm text-gray-500">Puedes mantener varias recepciones abiertas y procesar cada pallet independientemente.</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50 text-gray-600"><tr>
                            <th class="text-left p-3">Recepción</th><th class="text-left p-3">Camión</th>
                            <th class="text-left p-3">Fecha</th><th class="text-center p-3">Pallets</th>
                            <th class="text-center p-3">Ingresados</th><th class="text-center p-3">Observados</th>
                            <th class="text-center p-3">Estado</th><th class="p-3"></th>
                        </tr></thead>
                        <tbody class="divide-y">
                            @forelse($recepciones as $r)
                                <tr>
                                    <td class="p-3 font-semibold">#{{ $r->id }}</td>
                                    <td class="p-3">{{ $r->camion ?: '—' }}</td>
                                    <td class="p-3">{{ \Carbon\Carbon::parse($r->fecha_recepcion)->format('d/m/Y H:i') }}</td>
                                    <td class="p-3 text-center">{{ $r->total_pallets }}</td>
                                    <td class="p-3 text-center text-green-700 font-semibold">{{ $r->pallets_ingresados ?? 0 }}</td>
                                    <td class="p-3 text-center text-amber-700 font-semibold">{{ $r->pallets_observados ?? 0 }}</td>
                                    <td class="p-3 text-center"><span class="rounded-full px-2 py-1 text-xs {{ $r->estado === 'abierta' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-700' }}">{{ strtoupper($r->estado) }}</span></td>
                                    <td class="p-3 text-right"><a class="text-indigo-700 font-semibold hover:underline" href="{{ route('wms.recepcion-agencias.show',$r->id) }}">Abrir →</a></td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="p-8 text-center text-gray-500">Todavía no existen recepciones de agencias.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @endif
        </div>
    </div>

    <script>
        const buscarDocumentosUrl = @json(route('wms.recepcion-agencias.documentos.buscar'));
        const crearUrl = @json(route('wms.recepcion-agencias.store'));
        const csrf = @json(csrf_token());

        $('#documentos').select2({
            placeholder: 'Busca por número de documento o glosa...',
            width: '100%', minimumInputLength: 1,
            ajax: { url: buscarDocumentosUrl, dataType: 'json', delay: 300,
                data: params => ({q: params.term}),
                processResults: data => ({results: data.results.map(x => ({...x, disabled: x.disabled}))}) }
        });

        @can('wms.configurar')
        $('#formZona').on('submit', async function(e) {
            e.preventDefault();
            const box=$('#alertaZona');
            try {
                const r=await fetch(@json(route('wms.recepcion-agencias.ubicaciones-temporales.store')), {
                    method:'POST',headers:{'X-CSRF-TOKEN':csrf,'Accept':'application/json','Content-Type':'application/json'},
                    body:JSON.stringify({codigo:$('#codigoZona').val()})
                });
                const d=await r.json();
                if(!r.ok) throw new Error(d.message || Object.values(d.errors || {}).flat().join(' '));
                box.removeClass('hidden text-red-800').addClass('text-green-800').text('Zona '+d.codigo+' creada correctamente.');
                $('#codigoZona').val('');
            } catch(err) { box.removeClass('hidden text-green-800').addClass('text-red-800').text(err.message); }
        });
        @endcan

        function mostrarAlerta(texto, error=false) {
            $('#alerta').removeClass('hidden bg-red-100 text-red-800 bg-green-100 text-green-800')
                .addClass(error ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800').text(texto);
        }

        $('#formRecepcion').on('submit', async function(e) {
            e.preventDefault();
            const documentos = $('#documentos').val() || [];
            if (!documentos.length) return mostrarAlerta('Selecciona al menos un documento.', true);
            const btn = $('#btnCrear').prop('disabled', true).text('Creando recepción...');
            try {
                const fd = new FormData(this);
                fd.delete('documentos[]');
                documentos.forEach(id => fd.append('documentos[]', id));
                const response = await fetch(crearUrl, {method:'POST', headers:{'X-CSRF-TOKEN':csrf,'Accept':'application/json'}, body:fd});
                const data = await response.json();
                if (!response.ok) throw new Error(data.message || Object.values(data.errors || {}).flat().join(' '));
                window.location.href = data.url;
            } catch (error) {
                mostrarAlerta(error.message || 'No fue posible crear la recepción.', true);
                btn.prop('disabled', false).text('Crear recepción y cargar pallets');
            }
        });
    </script>
</x-app-layout>
