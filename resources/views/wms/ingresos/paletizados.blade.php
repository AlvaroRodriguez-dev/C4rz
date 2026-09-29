<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">WMS - Ingreso desde Paletización</h2>
    </x-slot>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-rc.0/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-rc.0/js/select2.min.js"></script>

    <div class="py-4 px-3 sm:py-6 sm:px-4">
        <div class="max-w-6xl mx-auto">
            <a href="{{ route('wms.ingresos.create') }}" class="text-sm text-gray-600 mb-4 inline-flex items-center gap-1">&larr; Volver al ingreso manual</a>

            <div id="alertBox" class="hidden mb-4 p-3 rounded-lg text-sm"></div>

            <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 mb-4">
                <h3 class="font-semibold text-blue-900">Ingreso generado desde HU</h3>
                <p class="text-sm text-blue-800 mt-1">
                    Esta pantalla recupera los pallets creados en Paletización. Aquí no se crean pallets ni se digitan cantidades:
                    únicamente se asigna la ubicación WMS a cada HU.
                </p>
                <p class="text-xs text-blue-700 mt-2">
                    La tabla <strong>wms_ingreso</strong> no se modifica en esta etapa.
                </p>
            </div>

            <div class="bg-white shadow rounded-xl p-4 sm:p-5 mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Entrega de producción paletizada</label>
                <select id="selectEntrega" class="w-full" style="width:100%"></select>
                <p class="text-xs text-gray-500 mt-2">Busca por documento, folio u origen de producción.</p>
            </div>

            <div id="detalleWrapper" class="hidden">
                <div id="resumenEntrega" class="bg-white shadow rounded-xl p-4 sm:p-5 mb-4"></div>
                <div id="tablaHus" class="space-y-3 mb-24 sm:mb-5"></div>

                <div class="fixed sm:static bottom-0 left-0 right-0 bg-white sm:bg-transparent border-t sm:border-0 border-gray-200 p-3 sm:p-0 shadow-[0_-2px_8px_rgba(0,0,0,0.06)] sm:shadow-none z-20">
                    <div class="max-w-6xl mx-auto">
                        <button id="btnUbicar" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-4 rounded-xl shadow text-lg">
                            GUARDAR UBICACIONES
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const routeBuscar = "{{ route('wms.ingresos.paletizados.buscar') }}";
        const routeDetalle = "{{ url('wms/ingresos/paletizados') }}";
        const routeGuardarBase = "{{ url('wms/ingresos/paletizados') }}";
        const routeOpcionesUbicacion = "{{ route('wms.maestros.ubicaciones.opciones') }}";
        const routeUbicacionesGalpon = "{{ url('wms/maestros/ubicaciones/galpon') }}";
        const csrfToken = "{{ csrf_token() }}";

        let galpones = [];
        const ubicaciones = {};
        let hus = [];
        let entregaId = null;

        $(document).ready(function () {
            $('#selectEntrega').select2({
                placeholder: 'Selecciona una entrega paletizada...',
                minimumInputLength: 0,
                width: '100%',
                ajax: {
                    url: routeBuscar,
                    dataType: 'json',
                    delay: 250,
                    data: params => ({ q: params.term ?? '' }),
                    processResults: data => ({ results: data.results })
                }
            });

            $('#selectEntrega').on('select2:select', function (e) {
                entregaId = e.params.data.id;
                cargarDetalle(entregaId);
            });

            $('#btnUbicar').on('click', guardarUbicaciones);
            cargarGalpones();
        });

        function cargarGalpones() {
            fetch(routeOpcionesUbicacion)
                .then(res => res.json())
                .then(data => { galpones = data.galpones ?? []; })
                .catch(() => mostrarAlerta('No fue posible cargar los galpones WMS.', 'error'));
        }

        function cargarUbicaciones(galponId) {
            if (!galponId || ubicaciones[galponId]) return Promise.resolve();
            return fetch(routeUbicacionesGalpon + '/' + encodeURIComponent(galponId))
                .then(res => { if (!res.ok) throw new Error(); return res.json(); })
                .then(data => { ubicaciones[galponId] = data.ubicaciones ?? []; });
        }

        function opcionesGalpon(seleccionado) {
            return '<option value="">Selecciona galpón...</option>' + galpones.map(g =>
                `<option value="${g.codigo}" ${g.codigo === seleccionado ? 'selected' : ''}>${g.codigo} · ${g.nombre}</option>`
            ).join('');
        }

        function opcionesUbicacion(galponCodigo, seleccionada) {
            const galpon = galpones.find(g => g.codigo === galponCodigo);
            const lista = galpon ? (ubicaciones[galpon.id] ?? []) : [];
            return '<option value="">Selecciona ubicación...</option>' + lista.map(u =>
                `<option value="${u.codigo}" ${String(u.codigo) === String(seleccionada) ? 'selected' : ''}>${u.codigo}</option>`
            ).join('');
        }

        function cargarDetalle(id) {
            $('#detalleWrapper').addClass('hidden');
            $('#tablaHus').empty();
            $('#resumenEntrega').empty();
            hus = [];

            fetch(`${routeDetalle}/${encodeURIComponent(id)}/detalle`)
                .then(async res => {
                    const data = await res.json();
                    if (!res.ok) throw new Error(data.message ?? 'No fue posible recuperar la entrega.');
                    return data;
                })
                .then(data => {
                    hus = (data.hus ?? []).map(hu => ({
                        ...hu,
                        galpon: '',
                        ubicacion: hu.ubicacion ?? ''
                    }));
                    entregaId = data.entrega.id;

                    $('#resumenEntrega').html(`
                        <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                            <div><div class="text-xs text-gray-500">Documento</div><div class="font-mono font-semibold">${data.entrega.documento ?? '—'}</div></div>
                            <div><div class="text-xs text-gray-500">Folio</div><div class="font-semibold">${data.entrega.folio_fisico ?? '—'}</div></div>
                            <div><div class="text-xs text-gray-500">Origen</div><div>${data.entrega.origen ?? '—'}</div></div>
                            <div><div class="text-xs text-gray-500">Fecha</div><div>${data.entrega.fecha ?? '—'}</div></div>
                            <div><div class="text-xs text-gray-500">HUs</div><div class="font-semibold">${hus.length}</div></div>
                        </div>
                    `);

                    renderHus();
                    $('#detalleWrapper').removeClass('hidden');
                })
                .catch(error => mostrarAlerta(error.message, 'error'));
        }

        function renderHus() {
            const cont = $('#tablaHus');
            cont.empty();

            if (hus.length === 0) {
                cont.html('<div class="bg-yellow-50 border border-yellow-200 rounded-xl p-4 text-sm text-yellow-800">La entrega no tiene HU generados.</div>');
                return;
            }

            hus.forEach((hu, idx) => {
                const detalleHtml = (hu.detalles ?? []).map(d => `
                    <div class="flex justify-between text-xs py-1 border-t border-gray-100 first:border-0">
                        <span><span class="font-mono">${d.codigo}</span> · Lote ${d.lote ?? 'S/L'}</span>
                        <span class="font-semibold">${d.cantidad}</span>
                    </div>
                `).join('');

                cont.append(`
                    <div class="bg-white shadow rounded-xl p-4 border ${hu.estado === 'UBICADO' ? 'border-green-300' : 'border-gray-200'}">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-3">
                            <div>
                                <div class="text-xs text-gray-500">HU / Pallet</div>
                                <div class="font-mono text-lg font-bold">${hu.numero}</div>
                            </div>
                            <div class="flex gap-2 text-xs">
                                <span class="px-2 py-1 rounded-full bg-gray-100 text-gray-700">${hu.tipo}</span>
                                <span class="px-2 py-1 rounded-full ${hu.estado === 'UBICADO' ? 'bg-green-100 text-green-700' : 'bg-blue-100 text-blue-700'}">${hu.estado}</span>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-3 text-sm">
                            <div><div class="text-xs text-gray-500">Formato</div><div class="font-semibold">${hu.formato ?? '—'}</div></div>
                            <div><div class="text-xs text-gray-500">Capacidad</div><div>${hu.capacidad}</div></div>
                            <div><div class="text-xs text-gray-500">Cantidad</div><div class="font-semibold">${hu.cantidad}</div></div>
                            <div><div class="text-xs text-gray-500">Ubicación actual</div><div>${hu.ubicacion ?? 'PENDIENTE'}</div></div>
                        </div>

                        <div class="border-t pt-2 mb-3">${detalleHtml}</div>

                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="block text-xs font-medium text-gray-500 mb-1">Galpón</label>
                                <select class="hu-input w-full border-gray-300 rounded-lg p-2.5" data-index="${idx}" data-field="galpon">
                                    ${opcionesGalpon(hu.galpon)}
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-500 mb-1">Ubicación</label>
                                <select class="hu-input w-full border-gray-300 rounded-lg p-2.5" data-index="${idx}" data-field="ubicacion" ${hu.galpon ? '' : 'disabled'}>
                                    ${opcionesUbicacion(hu.galpon, hu.ubicacion)}
                                </select>
                            </div>
                        </div>
                    </div>
                `);
            });

            $('.hu-input').on('change', async function () {
                const idx = Number($(this).data('index'));
                const field = $(this).data('field');
                const value = $(this).val() ?? '';

                if (field === 'galpon') {
                    hus[idx].galpon = value;
                    hus[idx].ubicacion = '';
                    const galpon = galpones.find(g => g.codigo === value);
                    if (galpon) await cargarUbicaciones(galpon.id);
                    renderHus();
                    return;
                }

                hus[idx].ubicacion = value;
            });
        }

        function guardarUbicaciones() {
            if (!entregaId || hus.length === 0) return mostrarAlerta('Selecciona una entrega paletizada.', 'error');

            const faltantes = hus.filter(hu => !hu.galpon || !hu.ubicacion);
            if (faltantes.length > 0) {
                mostrarAlerta(`Completa la ubicación de ${faltantes.length} HU antes de guardar.`, 'error');
                return;
            }

            const boton = $('#btnUbicar');
            boton.prop('disabled', true).text('GUARDANDO UBICACIONES...');

            fetch(`${routeGuardarBase}/${encodeURIComponent(entregaId)}/ubicar`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                body: JSON.stringify({
                    hus: hus.map(hu => ({ id: hu.id, galpon: hu.galpon, ubicacion: hu.ubicacion }))
                })
            })
                .then(async res => {
                    const data = await res.json();
                    if (!res.ok) throw new Error(Object.values(data.errors ?? {}).flat().join(' ') || data.message || 'No fue posible guardar las ubicaciones.');
                    return data;
                })
                .then(data => {
                    mostrarAlerta(data.message, 'success');
                    cargarDetalle(entregaId);
                })
                .catch(error => mostrarAlerta(error.message, 'error'))
                .finally(() => boton.prop('disabled', false).text('GUARDAR UBICACIONES'));
        }

        function mostrarAlerta(msg, tipo) {
            const box = $('#alertBox');
            box.removeClass('hidden bg-green-100 text-green-800 bg-red-100 text-red-800');
            box.addClass(tipo === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800');
            box.text(msg);
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    </script>
</x-app-layout>
