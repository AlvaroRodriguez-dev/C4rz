<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            WMS - Inventario
        </h2>
    </x-slot>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-rc.0/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-rc.0/js/select2.min.js"></script>

    <style>
        .select2-container .select2-selection--single {
            height: 48px !important;
            display: flex;
            align-items: center;
            border-radius: 0.75rem !important;
            border-color: #d1d5db !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 48px !important;
            font-size: 16px;
            padding-left: 12px !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 46px !important;
        }

        .select2-dropdown {
            border-radius: 0.75rem !important;
        }
    
        /* WMS Inventory - SAP Fiori inspired visual layer */
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap');
        .wms-inventory-shell { font-family: Inter,system-ui,sans-serif; }
        .wms-inventory-shell input:not([readonly]), .wms-inventory-shell select:not(.select2-hidden-accessible) {
          border:1px solid #cbd5e1; border-radius:8px; background:#fff; transition:border-color .15s,box-shadow .15s;
        }
        .wms-inventory-shell input:focus, .wms-inventory-shell select:focus {
          border-color:#0070F2; box-shadow:0 0 0 3px rgba(0,112,242,.12); outline:none;
        }
        .wms-inventory-shell section, .wms-inventory-shell #resultado > div, .wms-inventory-shell #resultado > section {
          border:1px solid #e2e8f0; border-radius:12px;
        }
        .wms-inventory-shell h3 { color:#1e293b; letter-spacing:-.02em; }
        .wms-inventory-shell table { border-collapse:separate; border-spacing:0; width:100%; }
        .wms-inventory-shell table thead { background:#f1f5f9; color:#475569; text-transform:uppercase; font-size:11px; letter-spacing:.06em; }
        .wms-inventory-shell table th { padding:12px 10px; }
        .wms-inventory-shell table tbody tr:nth-child(even) { background:#f8fafc; }
        .wms-inventory-shell table tbody tr:hover { background:#eff6ff; }
        .wms-inventory-shell table td { padding:10px; border-bottom:1px solid #e2e8f0; }
        .wms-inventory-shell .select2-container .select2-selection--single { border-color:#cbd5e1!important; background:#fff!important; }
        .wms-inventory-shell #inventarioConsulta .rounded-lg.bg-blue-50 { border:1px solid #bfdbfe; background:#eff6ff; }
        .wms-inventory-shell #resumenPorAlmacen > div { border:1px solid #e2e8f0; border-radius:12px; }
        .wms-inventory-shell #detallePallet > div, .wms-inventory-shell #detalleUbicacion > div { border-radius:12px; }
        @media(max-width:640px){ .wms-inventory-shell table { min-width:900px; } }

    </style>

    <div class="wms-inventory-shell py-4 px-3 sm:py-6 sm:px-4" style="background:#F8FAFC;">
        <div class="max-w-7xl mx-auto">

            <a href="{{ route('wms.index') }}" class="text-sm text-gray-600 mb-3 inline-flex items-center gap-1">&larr;
                Volver</a>

            @if($almacenes->isEmpty())
                <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 mb-4 text-amber-800">No tiene almacenes WMS activos asignados para consultar inventario.</div>
            @else
                <section class="bg-white rounded-xl shadow p-4 mb-5">
                    <h3 class="font-semibold text-lg mb-3">Consulta de inventario físico</h3>
                    <label class="block text-sm font-medium mb-1" for="almacenInventario">Almacén</label>
                    <select id="almacenInventario" class="w-full border-gray-300 rounded-lg p-3 mb-3">
                        @if($almacenes->count()>1)<option value="all">Inventario consolidado de almacenes asignados</option>@endif
                        @foreach($almacenes as $almacen)<option value="{{ $almacen->id }}">{{ $almacen->codigo }} · {{ $almacen->nombre }}</option>@endforeach
                    </select>
                    <label class="block text-sm font-medium mb-1">Buscar producto (opcional)</label>
                    <select id="selectProductoInventario" class="w-full mb-3" style="width:100%"></select>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 mb-3">
                        <input id="fCodigo" class="border rounded-lg p-2" placeholder="Código de producto" readonly>
                        <input id="fPallet" class="border rounded-lg p-2" placeholder="Pallet">
                        <input id="fGalpon" class="border rounded-lg p-2" placeholder="Galpón">
                        <input id="fUbicacion" class="border rounded-lg p-2" placeholder="Ubicación">
                        <input id="fLote" class="border rounded-lg p-2" placeholder="Lote">
                    </div>
                    <button type="button" id="btnConsultarInventario" class="bg-blue-600 hover:bg-blue-700 text-white rounded-lg px-4 py-3 w-full">Consultar inventario</button>
                    <p id="mensajeInventario" class="text-sm text-gray-500 mt-2"></p>
                    <div id="inventarioConsulta" class="hidden mt-4">
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-4">
                            <div class="rounded-lg bg-blue-50 p-3"><div class="text-xs font-semibold">TOTAL DE CAJAS</div><div id="metricCajas" class="text-2xl font-bold">0</div></div>
                            <div class="rounded-lg bg-slate-50 p-3"><div class="text-xs font-semibold">TOTAL DE PALLET</div><div id="metricPallets" class="text-2xl font-bold">0</div></div>
                            <div class="rounded-lg bg-slate-50 p-3"><div class="text-xs font-semibold">TOTAL DE UBICACIONES</div><div id="metricUbicaciones" class="text-2xl font-bold">0</div></div>
                        </div>
                        <div id="resumenPorAlmacen" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 mb-4"></div>
                        <div class="bg-white border rounded-lg p-3 mb-3">
                            <input id="textoFiltrarResultados" class="w-full border-gray-300 rounded-lg p-3" placeholder="Buscar en resultados: almacén, galpón, ubicación, pallet, producto o lote...">
                            <p id="contadorDetalle" class="text-xs text-gray-500 mt-2"></p>
                        </div>
                        <div class="overflow-x-auto"><table class="min-w-full text-sm">
                            <thead class="bg-gray-100"><tr><th class="p-2 text-left">Almacén</th><th class="p-2 text-left">Galpón</th><th class="p-2 text-left">Ubicación</th><th class="p-2 text-left">Pallet</th><th class="p-2 text-left">Código / Producto</th><th class="p-2 text-left">Lote</th><th class="p-2 text-right">Saldo</th></tr></thead>
                            <tbody id="tablaInventario"></tbody>
                        </table></div>
                    </div>
                </section>
            @endif

            <div class="bg-white shadow rounded-xl p-4 sm:p-5 mb-4">
                <label for="selectProducto" class="block text-sm font-medium text-gray-700 mb-2">Buscar por
                    Producto</label>

                <select id="selectProducto" class="w-full" style="width:100%"></select>
                <p class="text-xs text-gray-500 mt-2">Busca por código o descripción.</p>
            </div>


            <div id="resultado" class="hidden space-y-4">

                {{-- Total general --}}
                <div class="bg-blue-600 text-white rounded-xl p-5 shadow text-center">
                    <p class="text-sm opacity-80">TOTAL GENERAL</p>
                    <div class="flex items-center justify-center gap-6 mt-2">
                        <div>
                            <p id="totalGeneral" class="text-4xl font-bold">0</p>
                            <p class="text-xs opacity-80">cajas</p>
                        </div>
                        <div class="w-px h-10 bg-blue-400"></div>
                        <div>
                            <p id="totalPalletsGeneral" class="text-4xl font-bold">0</p>
                            <p class="text-xs opacity-80">pallets</p>
                        </div>
                    </div>
                </div>

                {{-- Buscador (filtra en el navegador, sin nueva consulta al servidor) --}}
                <div class="bg-white shadow rounded-xl p-3">
                    <div class="relative">
                        <input type="text" id="buscadorInventario"
                            class="w-full border-gray-300 rounded-xl p-3 pl-10 text-base"
                            placeholder="Filtrar por pallet, galpón, ubicación o lote...">
                        <svg class="w-5 h-5 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none"
                            stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z" />
                        </svg>
                    </div>
                    <p id="contadorFiltro" class="text-xs text-gray-500 mt-2"></p>
                </div>

                {{-- Detalle por Pallet --}}
                <div>
                    <h3 class="font-semibold text-gray-700 mb-2 px-1">Detalle por Pallet</h3>
                    <div id="detallePallet" class="space-y-3"></div>
                    <p id="sinResultadosPallet" class="hidden text-sm text-gray-400 text-center py-3">Sin
                        coincidencias.</p>
                </div>

                {{-- Detalle por Ubicación --}}
                <div>
                    <h3 class="font-semibold text-gray-700 mb-2 px-1">Detalle por Ubicación</h3>
                    <div id="detalleUbicacion" class="space-y-3"></div>
                    <p id="sinResultadosUbicacion" class="hidden text-sm text-gray-400 text-center py-3">Sin
                        coincidencias.</p>
                </div>

            </div>



            <div id="sinDatos" class="hidden text-center text-gray-500 mt-6">
                Este producto no tiene saldo disponible actualmente.
            </div>

        </div>
    </div>

    <script>
        const routeConsultaInventario = "{{ route('wms.inventario.consulta') }}";
        const routeBuscarProductosInventario = "{{ route('wms.inventario.productos.buscar') }}";
        let detalleInventarioCompleto = [];
        $(document).ready(function(){
            $('#selectProductoInventario').select2({
                placeholder:'Buscar por código o descripción...',
                minimumInputLength:1, width:'100%', allowClear:true,
                ajax:{url:routeBuscarProductosInventario,dataType:'json',delay:300,data:params=>({q:params.term}),processResults:data=>({results:data.results})}
            }).on('select2:select',e=>$('#fCodigo').val(e.params.data.id))
              .on('select2:clear',()=>$('#fCodigo').val(''));
            $('#textoFiltrarResultados').on('input',function(){renderTablaInventario($(this).val());});
        });
        function escapeHtml(v){return $('<div>').text(v??'').html();}
        function renderTablaInventario(texto=''){
            const term=(texto||'').trim().toLowerCase();
            const filtrado=detalleInventarioCompleto.filter(x=>!term||[x.almacen,x.galpon,x.ubicacion,x.pallet,x.codigo,x.descrip,x.lote,x.saldo].join(' ').toLowerCase().includes(term));
            $('#tablaInventario').html(filtrado.map(x=>'<tr class="border-b"><td class="p-2">'+escapeHtml(x.almacen)+'</td><td class="p-2">'+escapeHtml(x.galpon)+'</td><td class="p-2">'+escapeHtml(x.ubicacion)+'</td><td class="p-2">'+escapeHtml(x.pallet)+'</td><td class="p-2">'+escapeHtml(x.codigo)+' · '+escapeHtml(x.descrip)+'</td><td class="p-2">'+escapeHtml(x.lote)+'</td><td class="p-2 text-right font-semibold">'+escapeHtml(x.saldo)+'</td></tr>').join(''));
            $('#contadorDetalle').text('Mostrando '+filtrado.length+' de '+detalleInventarioCompleto.length+' registros');
        }
        $('#btnConsultarInventario').on('click', function() {
            const params = new URLSearchParams({ almacen: $('#almacenInventario').val() || '' });
            [['codigo','#fCodigo'],['pallet','#fPallet'],['galpon','#fGalpon'],['ubicacion','#fUbicacion'],['lote','#fLote']].forEach(([k,s]) => { const v=$(s).val().trim(); if(v) params.set(k,v); });
            $('#mensajeInventario').text('Consultando…'); $('#inventarioConsulta').addClass('hidden');
            fetch(routeConsultaInventario+'?'+params.toString()).then(async r=>{const d=await r.json();if(!r.ok)throw new Error(d.message||'Error de consulta');return d;})
            .then(d=>{
                $('#metricCajas').text(d.total_cajas);$('#metricPallets').text(d.total_pallets);$('#metricUbicaciones').text(d.total_ubicaciones);
                $('#resumenPorAlmacen').html((d.resumen_almacenes||[]).map(a=>'<div class="border rounded-xl p-3 bg-white shadow-sm"><div class="font-semibold mb-2">'+escapeHtml(a.almacen)+'</div><div class="grid grid-cols-3 gap-2 text-sm"><div><div class="text-gray-500">Cajas</div><div class="font-bold">'+a.cajas+'</div></div><div><div class="text-gray-500">Pallets</div><div class="font-bold">'+a.pallets+'</div></div><div><div class="text-gray-500">Ubicaciones</div><div class="font-bold">'+a.ubicaciones+'</div></div></div></div>').join(''));
                detalleInventarioCompleto=d.detalle||[]; renderTablaInventario($('#textoFiltrarResultados').val());
                $('#mensajeInventario').text(detalleInventarioCompleto.length+' registro(s)');$('#inventarioConsulta').removeClass('hidden');
            }).catch(e=>$('#mensajeInventario').text(e.message));
        });

        const routeBuscarProductos = "{{ route('wms.inventario.productos.buscar') }}";
        const routeSaldos = "{{ url('wms/inventario') }}"; // + /{codigo}/saldos

        let inventarioData = {
            por_pallet: [],
            por_ubicacion: []
        };

        $(document).ready(function() {
            $('#selectProducto').select2({
                placeholder: 'Escribe código o descripción...',
                minimumInputLength: 1,
                width: '100%',
                ajax: {
                    url: routeBuscarProductos,
                    dataType: 'json',
                    delay: 300,
                    data: params => ({
                        q: params.term
                    }),
                    processResults: data => ({
                        results: data.results
                    })
                }
            });

            $('#selectProducto').on('select2:select', function(e) {
                cargarSaldos(e.params.data.id);
            });

            $('#buscadorInventario').on('input', function() {
                renderInventarioFiltrado($(this).val().trim().toLowerCase());
            });
        });

        function cargarSaldos(codigo) {
            $('#resultado').addClass('hidden');
            $('#sinDatos').addClass('hidden');
            $('#buscadorInventario').val('');

            fetch(`${routeSaldos}/${encodeURIComponent(codigo)}/saldos?almacen=${encodeURIComponent($('#almacenInventario').val() || '')}`)
                .then(res => res.json())
                .then(data => {
                    if (data.total_general <= 0) {
                        $('#sinDatos').removeClass('hidden');
                        return;
                    }

                    $('#totalGeneral').text(data.total_general);
                    $('#totalPalletsGeneral').text(data.total_pallets); // <-- nuevo

                    inventarioData = {
                        por_pallet: data.por_pallet,
                        por_ubicacion: data.por_ubicacion
                    };

                    renderInventarioFiltrado('');
                    $('#resultado').removeClass('hidden');
                });
        }

        /**
         * Renderiza el detalle por Pallet y por Ubicación aplicando un filtro de texto libre
         * (sin consultar al servidor). Coincide si el texto aparece en pallet, galpón,
         * ubicación, o en cualquiera de los lotes contenidos.
         */
        function renderInventarioFiltrado(texto) {
            const coincideTexto = (valor) => (valor ?? '').toString().toLowerCase().includes(texto);

            const coincidePallet = (p) =>
                coincideTexto(p.pallet) || coincideTexto(p.galpon) || coincideTexto(p.ubicacion) ||
                p.lotes.some(l => coincideTexto(l.clote));

            const coincideUbicacion = (u) =>
                coincideTexto(u.galpon) || coincideTexto(u.ubicacion) ||
                u.lotes.some(l => coincideTexto(l.clote));

            const palletsFiltrados = texto === '' ? inventarioData.por_pallet : inventarioData.por_pallet.filter(
                coincidePallet);
            const ubicacionesFiltradas = texto === '' ? inventarioData.por_ubicacion : inventarioData.por_ubicacion.filter(
                coincideUbicacion);

            // --- Detalle por Pallet ---
            $('#detallePallet').empty();
            palletsFiltrados.forEach(p => {
                const lotesHtml = p.lotes.map(l => `
            <div class="flex justify-between text-sm py-1 border-t border-gray-100 first:border-0">
                <span class="text-gray-500">Lote: ${l.clote}</span>
                <span class="font-semibold text-gray-700">${l.total}</span>
            </div>
        `).join('');

                $('#detallePallet').append(`
            <div class="bg-white border border-gray-200 rounded-xl p-4 shadow-sm">
                <div class="flex justify-between items-center mb-1">
                    <p class="font-mono font-semibold text-gray-800">${p.pallet}</p>
                    <p class="text-lg font-bold text-blue-700">${p.total_pallet}</p>
                </div>
                <p class="text-xs text-gray-400 mb-2">Galpón ${p.galpon} · Ubic. ${p.ubicacion}</p>
                ${lotesHtml}
            </div>
        `);
            });
            $('#sinResultadosPallet').toggleClass('hidden', palletsFiltrados.length > 0);

            // --- Detalle por Ubicación ---
            $('#detalleUbicacion').empty();
            ubicacionesFiltradas.forEach(u => {
                const lotesHtml = u.lotes.map(l => `
            <div class="flex justify-between text-sm py-1 border-t border-gray-100 first:border-0">
                <span class="text-gray-500">Lote: ${l.clote}</span>
                <span class="font-semibold text-gray-700">${l.total}</span>
            </div>
        `).join('');

                $('#detalleUbicacion').append(`
            <div class="bg-white border border-gray-200 rounded-xl p-4 shadow-sm">
                <div class="flex justify-between items-center mb-1">
                    <p class="font-semibold text-gray-800">Galpón ${u.galpon} · Ubic. ${u.ubicacion}</p>
                    <p class="text-lg font-bold text-green-700">${u.total_ubicacion}</p>
                </div>
                ${lotesHtml}
            </div>
        `);
            });
            $('#sinResultadosUbicacion').toggleClass('hidden', ubicacionesFiltradas.length > 0);

            // --- Contador de resultados ---
            if (texto === '') {
                $('#contadorFiltro').text(
                    `${inventarioData.por_pallet.length} pallet(s) · ${inventarioData.por_ubicacion.length} ubicación(es)`
                );
            } else {
                $('#contadorFiltro').text(
                    `Filtrando "${texto}": ${palletsFiltrados.length} pallet(s) · ${ubicacionesFiltradas.length} ubicación(es)`
                );
            }
        }
    </script>
</x-app-layout>
