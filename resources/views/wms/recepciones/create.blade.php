<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">
            WMS - INGRESO X RECEPCIÓN
        </h2>
    </x-slot>

    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-rc.0/css/select2.min.css" rel="stylesheet">

    <style>
        .select2-container,
        .select2-container .select2-selection--single {
            width: 100% !important;
        }

        .select2-container .select2-selection--single {
            height: 46px !important;
            display: flex;
            align-items: center;
            border: 1px solid #d1d5db !important;
            border-radius: .5rem !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 44px !important;
            padding-left: 12px !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 44px !important;
        }

        .campo {
            width: 100%;
            border: 1px solid #d1d5db;
            border-radius: .5rem;
            padding: .55rem .7rem;
            background: white;
        }

        .btn {
            padding: .55rem .9rem;
            border-radius: .5rem;
            font-size: .875rem;
            font-weight: 600;
        }

        .btn:disabled {
            opacity: .5;
            cursor: not-allowed;
        }
    </style>

    <div class="py-6 px-4">
        <div class="max-w-6xl mx-auto">

            <div class="flex flex-wrap items-center justify-between gap-3">
                <a href="{{ route('wms.index') }}"
                   class="text-sm text-gray-600 hover:text-blue-600">
                    &larr; Volver al menú WMS
                </a>
                <a href="{{ route('wms.recepciones.discrepancias') }}"
                   class="text-sm font-semibold text-indigo-700 hover:text-indigo-900">
                    Consultar discrepancias de recepción
                </a>
            </div>

            {{-- Búsqueda --}}
            <div class="bg-white rounded-xl shadow p-5 mt-4">
                <label for="recepcion" class="block font-medium mb-2">
                    Buscar recepción de SAS-ERP
                </label>

                <select id="recepcion" class="w-full"></select>

                <p class="text-xs text-gray-500 mt-2">
                    Busca el código de recepción y selecciona el documento que deseas ingresar.
                </p>

                <div id="mensaje"
                     role="status"
                     aria-live="polite"
                     class="hidden mt-4 p-3 rounded-lg text-sm">
                </div>
            </div>

            {{-- Detalle --}}
            <div id="panelDetalle" class="hidden bg-white rounded-xl shadow p-5 mt-4">
                <h3 class="font-semibold text-lg">Detalle de la recepción</h3>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-3 text-sm">
                    <div>
                        Recepción:
                        <strong id="codigoRecepcion"></strong>
                    </div>
                    <div>
                        Total pendiente:
                        <strong id="totalPendiente">0</strong> cajas
                    </div>
                </div>

                <div class="overflow-x-auto mt-4">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b text-left bg-gray-50">
                                <th class="p-2">Producto</th>
                                <th class="p-2">Lote origen</th>
                                <th class="p-2">Despachado</th>
                                <th class="p-2">Asignado</th>
                                <th class="p-2">Pendiente</th>
                            </tr>
                        </thead>
                        <tbody id="filasDetalle"></tbody>
                    </table>
                </div>

                <p id="sinPendientes" class="hidden mt-4 text-sm text-gray-500">
                    Esta recepción no tiene cantidades pendientes.
                </p>
            </div>

            {{-- Preparación --}}
            <div id="panelPreparacion" class="hidden bg-white rounded-xl shadow p-5 mt-4">

                <div class="flex flex-wrap justify-between items-center gap-3">
                    <div>
                        <h3 class="font-semibold text-lg">Preparación de pallets</h3>
                        <p class="text-sm text-gray-500">
                            Los pallets se generan automáticamente por formato, producto y lote. Puedes fusionar saldos del mismo formato y copiar ubicaciones, respetando siempre la capacidad configurada.
                        </p>
                    </div>
                </div>

                <div id="listaPallets" class="space-y-4 mt-5"></div>

                <div id="resumenPreparacion"
                     class="hidden border-t mt-6 pt-4">
                    <h4 class="font-semibold">Resumen de preparación</h4>

                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 mt-3">
                        <div class="bg-gray-50 rounded-lg p-3">
                            <p class="text-xs text-gray-500">Pallets preparados</p>
                            <strong id="resumenPallets">0</strong>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-3">
                            <p class="text-xs text-gray-500">Productos</p>
                            <strong id="resumenProductos">0</strong>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-3">
                            <p class="text-xs text-gray-500">Cajas</p>
                            <strong id="resumenCajas">0</strong>
                        </div>
                    </div>

                    <p class="text-xs text-amber-700 mt-3">
                        La capacidad máxima de cada formato y las autorizaciones
                        de excepciones serán verificadas nuevamente por el servidor.
                    </p>

                    <button type="button" id="btnRegistrar"
                            class="btn bg-green-600 hover:bg-green-700 text-white mt-4">
                        Confirmar ingreso de todos los pallets
                    </button>
                </div>
            </div>

            {{-- Resultado --}}
            <div id="panelResultado"
                 class="hidden bg-white rounded-xl shadow p-5 mt-4">
                <h3 class="font-semibold text-lg text-green-700">
                    Ingreso registrado
                </h3>
                <div id="detalleResultado" class="mt-3 text-sm"></div>
            </div>

        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-rc.0/js/select2.min.js"></script>

    <script>
        const URL_BUSCAR = @json(route('wms.recepciones.buscar'));
        const URL_DETALLE = @json(url('wms/recepciones'));
        const URL_REGISTRAR = @json(route('wms.recepciones.store'));
        const URL_OPCIONES = @json(route('wms.maestros.ubicaciones.opciones'));
        const URL_GALPON = @json(url('wms/maestros/ubicaciones/galpon'));
        const CSRF = @json(csrf_token());

        const campoRecepcion = window.jQuery('#recepcion');

        const estado = {
            recepcion: null,
            items: [],
            galpones: [],
            pallets: [],
            siguientePallet: 1,
            consulta: 0,
            enviando: false
        };

        const $ = id => document.getElementById(id);

        function mensaje(texto, tipo = 'info') {
            const elemento = $('mensaje');

            const clases = {
                info: 'bg-blue-100 text-blue-800',
                success: 'bg-green-100 text-green-800',
                error: 'bg-red-100 text-red-800'
            };

            elemento.textContent = texto;
            elemento.className =
                'mt-4 p-3 rounded-lg text-sm ' + clases[tipo];
        }

        function ocultarMensaje() {
            $('mensaje').textContent = '';
            $('mensaje').className = 'hidden';
        }

        function elemento(tag, texto = '', clase = '') {
            const nodo = document.createElement(tag);
            nodo.textContent = texto;
            if (clase) nodo.className = clase;
            return nodo;
        }

        function crearCelda(valor) {
            return elemento('td', String(valor ?? ''), 'p-2 border-b');
        }

        function crearCeldaProducto(item) {
            const celda = elemento('td', '', 'p-2 border-b');
            celda.appendChild(elemento('div', item.codigo || '', 'font-medium'));

            if (item.descrip) {
                celda.appendChild(
                    elemento('div', item.descrip, 'text-xs text-gray-600')
                );
            }

            if (item.descrip1) {
                celda.appendChild(
                    elemento('div', item.descrip1, 'text-xs text-gray-500')
                );
            }

            return celda;
        }

        function limpiarEstado() {
            estado.recepcion = null;
            estado.items = [];
            estado.pallets = [];
            estado.siguientePallet = 1;

            $('panelDetalle').classList.add('hidden');
            $('panelPreparacion').classList.add('hidden');
            $('panelResultado').classList.add('hidden');

            $('filasDetalle').replaceChildren();
            $('listaPallets').replaceChildren();
            $('resumenPreparacion').classList.add('hidden');
        }

        function claveItem(item) {
            return `${item.codigo}|${item.lote_origen || ''}`;
        }

        function pendientesPorDetalle(item) {
            return item.detalles_origen.reduce((total, detalle) => {
                return total + Number(detalle.cantidad_pendiente || 0);
            }, 0);
        }

        function cantidadUtilizada(idDetalle) {
            let total = 0;

            estado.pallets.forEach(pallet => {
                pallet.items.forEach(item => {
                    item.asignaciones.forEach(asignacion => {
                        if (Number(asignacion.id_detalle_origen) === Number(idDetalle)) {
                            total += Number(asignacion.cantidad);
                        }
                    });
                });
            });

            return total;
        }

        function cantidadPreparada(itemOrigen) {
            let total = 0;
            const clave = claveItem(itemOrigen);

            estado.pallets.forEach(pallet => {
                pallet.items.forEach(item => {
                    if (item.clave === clave) {
                        total += Number(item.cantidad);
                    }
                });
            });

            return total;
        }

        function restante(itemOrigen) {
            return Math.max(
                0,
                Number(itemOrigen.cantidad_pendiente) - cantidadPreparada(itemOrigen)
            );
        }

        function obtenerItemOrigen(clave) {
            return estado.items.find(item => claveItem(item) === clave);
        }

        function totalCajasPallet(pallet) {
            return pallet.items.reduce((total, item) => total + Number(item.cantidad || 0), 0);
        }

        // Conserva la trazabilidad de cada caja contra el detalle original de SAS-ERP.
        function crearAsignacionesAutomaticas(origen, cantidad) {
            let porAsignar = Number(cantidad);
            const asignaciones = [];

            for (const detalle of origen.detalles_origen) {
                if (porAsignar <= 0) break;

                const pendiente = Number(detalle.cantidad_pendiente || 0);
                const utilizado = cantidadUtilizada(detalle.id_detalle_origen);
                const disponible = Math.max(0, pendiente - utilizado);
                const asignado = Math.min(porAsignar, disponible);

                if (asignado > 0) {
                    asignaciones.push({
                        id_detalle_origen: Number(detalle.id_detalle_origen),
                        cantidad: asignado
                    });
                    porAsignar -= asignado;
                }
            }

            if (porAsignar > 0) {
                throw new Error(
                    `No fue posible asignar ${porAsignar} cajas del producto ${origen.codigo} al detalle original.`
                );
            }

            return asignaciones;
        }

        function prepararPalletsAutomaticamente() {
            estado.pallets = [];
            estado.siguientePallet = 1;

            // Opción A: la distribución inicial se realiza por formato + código + lote.
            // Cada combinación genera sus propios pallets completos y, si corresponde,
            // un pallet de saldo. Los saldos podrán fusionarse manualmente después.
            for (const origen of estado.items) {
                let pendiente = Number(origen.cantidad_pendiente || 0);
                const formato = String(
                    origen.formato || String(origen.codigo).substring(5, 9)
                ).toUpperCase();
                const capacidad = Number(origen.capacidad_pallet || 0);
                const clave = claveItem(origen);

                if (pendiente <= 0) continue;

                // Sin capacidad configurada se muestra el pendiente en un pallet
                // individual, pero la validación impide registrarlo hasta configurar.
                if (!capacidad) {
                    const pallet = {
                        id: estado.siguientePallet++,
                        galponId: '',
                        galpon: '',
                        ubicacion: '',
                        formato,
                        capacidad: null,
                        items: [{
                            clave,
                            codigo: origen.codigo,
                            descrip: origen.descrip || '',
                            descrip1: origen.descrip1 || '',
                            clote: origen.lote_origen || '',
                            cantidad: pendiente,
                            observacion_exceso: '',
                            observacion_lote: '',
                            asignaciones: crearAsignacionesAutomaticas(origen, pendiente)
                        }]
                    };
                    estado.pallets.push(pallet);
                    continue;
                }

                // No se reutiliza el pallet de la combinación anterior:
                // así no se mezclan automáticamente códigos ni lotes diferentes.
                while (pendiente > 0) {
                    const cantidad = Math.min(pendiente, capacidad);
                    const pallet = {
                        id: estado.siguientePallet++,
                        galponId: '',
                        galpon: '',
                        ubicacion: '',
                        formato,
                        capacidad,
                        items: [{
                            clave,
                            codigo: origen.codigo,
                            descrip: origen.descrip || '',
                            descrip1: origen.descrip1 || '',
                            clote: origen.lote_origen || '',
                            cantidad,
                            observacion_exceso: '',
                            observacion_lote: '',
                            asignaciones: crearAsignacionesAutomaticas(origen, cantidad)
                        }]
                    };

                    estado.pallets.push(pallet);
                    pendiente -= cantidad;
                }
            }

            renderizarPallets();
            actualizarResumen();

            const formatosSinConfig = [...new Set(
                estado.items
                    .filter(item => item.sin_config || !Number(item.capacidad_pallet))
                    .map(item => item.formato || String(item.codigo).substring(5, 9))
            )];

            if (formatosSinConfig.length) {
                mensaje(
                    'No existe una capacidad válida configurada para los formatos: ' +
                    formatosSinConfig.join(', ') +
                    '. Se muestran sus cantidades, pero debes configurar el límite antes de registrar.',
                    'error'
                );
            } else {
                mensaje(
                    'Pallets generados automáticamente. Puedes fusionar saldos compatibles desde cada tarjeta. Las discrepancias se gestionan desde las opciones avanzadas.',
                    'success'
                );
            }
        }

        async function consultarRecepcion(codigo) {
            const consecutivo = ++estado.consulta;

            limpiarEstado();
            ocultarMensaje();

            if (!codigo) return;

            mensaje('Consultando detalle de la recepción...', 'info');

            try {
                const response = await fetch(
                    `${URL_DETALLE}/${encodeURIComponent(codigo)}/detalle`,
                    {
                        headers: { 'Accept': 'application/json' }
                    }
                );

                const data = await response.json();

                if (consecutivo !== estado.consulta) return;

                if (!response.ok) {
                    throw new Error(data.message || 'No fue posible consultar la recepción.');
                }

                estado.recepcion = data.recepcion;
                estado.items = Array.isArray(data.items) ? data.items : [];

                $('codigoRecepcion').textContent = data.recepcion;
                $('totalPendiente').textContent = data.total_cajas_pendientes ?? 0;

                estado.items.forEach(item => {
                    const tr = document.createElement('tr');

                    tr.append(
                        crearCeldaProducto(item),
                        crearCelda(item.lote_origen || 'Sin lote'),
                        crearCelda(item.cantidad_despacho),
                        crearCelda(item.cantidad_asignada),
                        crearCelda(item.cantidad_pendiente)
                    );

                    $('filasDetalle').appendChild(tr);
                });

                $('sinPendientes').classList.toggle('hidden', estado.items.length > 0);
                $('panelDetalle').classList.remove('hidden');

                if (estado.items.length > 0) {
                    await cargarGalpones();

                    if (consecutivo !== estado.consulta) return;

                    $('panelPreparacion').classList.remove('hidden');
                    prepararPalletsAutomaticamente();
                }

                mensaje('Recepción consultada correctamente.', 'success');

            } catch (error) {
                if (consecutivo !== estado.consulta) return;

                limpiarEstado();
                mensaje(error.message || 'Error al consultar la recepción.', 'error');
            }
        }

        async function cargarGalpones() {
            const response = await fetch(URL_OPCIONES, {
                headers: { 'Accept': 'application/json' }
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || 'No fue posible cargar los galpones.');
            }

            estado.galpones = Array.isArray(data.galpones) ? data.galpones : [];
        }

        async function cargarUbicaciones(pallet, selectUbicacion) {
            const galponId = pallet.galponId;

            selectUbicacion.replaceChildren(
                new Option('Cargando ubicaciones...', '')
            );

            if (!galponId) {
                selectUbicacion.replaceChildren(
                    new Option('Seleccione primero un galpón', '')
                );
                return;
            }

            try {
                const response = await fetch(
                    `${URL_GALPON}/${encodeURIComponent(galponId)}`,
                    { headers: { 'Accept': 'application/json' } }
                );

                const data = await response.json();

                if (!response.ok) {
                    throw new Error(
                        data.message || 'No fue posible cargar ubicaciones.'
                    );
                }

                // Ignorar respuestas correspondientes a un galpón anterior.
                if (String(pallet.galponId) !== String(galponId)) return;

                selectUbicacion.replaceChildren(
                    new Option('Seleccione ubicación', '')
                );

                (data.ubicaciones || []).forEach(ubicacion => {
                    selectUbicacion.appendChild(
                        new Option(
                            ubicacion.codigo,
                            ubicacion.codigo
                        )
                    );
                });

                // Mantener la ubicación previamente seleccionada al redibujar.
                selectUbicacion.value = pallet.ubicacion;

            } catch (error) {
                // No mostrar errores de una petición que ya quedó obsoleta.
                if (String(pallet.galponId) !== String(galponId)) return;

                selectUbicacion.replaceChildren(
                    new Option('Error al cargar ubicaciones', '')
                );

                mensaje(error.message, 'error');
            }
        }

        function crearSelectGalpon(pallet) {
            const select = elemento('select', '', 'campo');

            select.appendChild(new Option('Seleccione galpón', ''));

            estado.galpones.forEach(galpon => {
                select.appendChild(
                    new Option(`${galpon.codigo} · ${galpon.nombre}`, galpon.id)
                );
            });

            select.addEventListener('change', async () => {
                pallet.galponId = select.value;

                const galpon = estado.galpones.find(
                    item => String(item.id) === String(select.value)
                );

                pallet.galpon = galpon ? galpon.codigo : '';

                const ubicacionSelect = select.closest('[data-pallet]')
                    .querySelector('[data-ubicacion]');

                await cargarUbicaciones(pallet, ubicacionSelect);
            });

            return select;
        }

        function crearSelectUbicacion(pallet) {
            const select = elemento('select', '', 'campo');
            select.dataset.ubicacion = '1';
            select.appendChild(new Option('Seleccione primero un galpón', ''));

            select.addEventListener('change', () => {
                pallet.ubicacion = select.value;
            });

            return select;
        }

        function agregarPallet() {
            const pallet = {
                id: estado.siguientePallet++,
                galponId: '',
                galpon: '',
                ubicacion: '',
                items: []
            };

            estado.pallets.push(pallet);
            renderizarPallets();
            actualizarResumen();
        }

        function eliminarPallet(id) {
            estado.pallets = estado.pallets.filter(p => p.id !== id);
            renderizarPallets();
            actualizarResumen();
        }

        function agregarProducto(palletId, clave, cantidad, loteFisico, obsExceso, obsLote) {
            const pallet = estado.pallets.find(p => p.id === palletId);
            const origen = obtenerItemOrigen(clave);

            if (!pallet || !origen) return;

            const codigo = origen.codigo;
            const formato = String(codigo).substring(5, 9).toUpperCase();

            if (pallet.items.length > 0) {
                const formatoExistente = String(pallet.items[0].codigo)
                    .substring(5, 9).toUpperCase();

                if (formatoExistente !== formato) {
                    mensaje('No se pueden mezclar formatos distintos en un mismo pallet.', 'error');
                    return;
                }
            }

            const cantidadIngresada = Number(cantidad);
            const capacidad = Number(origen.capacidad_pallet || 0);
            const totalActual = totalCajasPallet(pallet);

            if (!capacidad) {
                mensaje(`El formato ${formato} no tiene capacidad configurada en WMS.`, 'error');
                return;
            }

            if (totalActual + cantidadIngresada > capacidad) {
                mensaje(`El pallet del formato ${formato} admite como máximo ${capacidad} cajas. Espacio disponible: ${capacidad - totalActual}.`, 'error');
                return;
            }

            if (!Number.isInteger(cantidadIngresada) || cantidadIngresada <= 0) {
                mensaje('La cantidad debe ser un número entero mayor a cero.', 'error');
                return;
            }

            if (!loteFisico.trim()) {
                mensaje('Debe indicar el lote físico del producto.', 'error');
                return;
            }

            const disponible = restante(origen);
            const excede = cantidadIngresada > disponible;
            const discrepancia =
                loteFisico.trim().toUpperCase() !==
                String(origen.lote_origen || '').trim().toUpperCase();

            if (excede && !obsExceso.trim()) {
                mensaje('Debe ingresar una observación para justificar el exceso.', 'error');
                return;
            }

            if (discrepancia && !obsLote.trim()) {
                mensaje('Debe ingresar una observación para justificar la diferencia de lote.', 'error');
                return;
            }

            // Distribución de la cantidad entre los detalles originales.
            // Si existe excedente, se asigna al último detalle para que el
            // backend determine y registre la excepción correspondiente.
            let porAsignar = cantidadIngresada;
            const asignaciones = [];

            for (const detalle of origen.detalles_origen) {
                if (porAsignar <= 0) break;

                const pendiente = Number(detalle.cantidad_pendiente || 0);
                const usado = cantidadUtilizada(detalle.id_detalle_origen);
                const disponibleDetalle = Math.max(0, pendiente - usado);

                const asignado = Math.min(porAsignar, disponibleDetalle);

                if (asignado > 0) {
                    asignaciones.push({
                        id_detalle_origen: Number(detalle.id_detalle_origen),
                        cantidad: asignado
                    });

                    porAsignar -= asignado;
                }
            }

            if (porAsignar > 0) {
                const ultimo = origen.detalles_origen[origen.detalles_origen.length - 1];

                if (!ultimo) {
                    mensaje('No existe un detalle de origen para asignar la cantidad.', 'error');
                    return;
                }

                const existente = asignaciones.find(
                    a => a.id_detalle_origen === Number(ultimo.id_detalle_origen)
                );

                if (existente) {
                    existente.cantidad += porAsignar;
                } else {
                    asignaciones.push({
                        id_detalle_origen: Number(ultimo.id_detalle_origen),
                        cantidad: porAsignar
                    });
                }
            }

            pallet.formato = formato;
            pallet.capacidad = capacidad;

            pallet.items.push({
                clave,
                codigo,
                descrip: origen.descrip || '',
                descrip1: origen.descrip1 || '',
                clote: loteFisico.trim(),
                cantidad: cantidadIngresada,
                observacion_exceso: obsExceso.trim(),
                observacion_lote: obsLote.trim(),
                asignaciones
            });

            renderizarPallets();
            actualizarResumen();
            mensaje('Producto agregado al pallet.', 'success');
        }

        function renderizarPallets() {
            const contenedor = $('listaPallets');
            contenedor.replaceChildren();

            estado.pallets.forEach((pallet, indice) => {
                const total = totalCajasPallet(pallet);
                const capacidad = Number(pallet.capacidad || 0);
                const esSaldo = capacidad > 0 && total < capacidad;
                const sinConfig = !capacidad;
                const card = elemento(
                    'div',
                    '',
                    `border rounded-xl p-4 shadow-sm ${sinConfig ? 'border-orange-300 bg-orange-50' : (esSaldo ? 'border-blue-300 bg-blue-50' : 'border-gray-200 bg-white')}`
                );
                card.dataset.pallet = pallet.id;

                const encabezado = elemento('div', '', 'flex items-center justify-between mb-2');
                encabezado.appendChild(elemento('h4', `Pallet Local ${indice + 1}`, 'font-semibold text-gray-800'));

                const etiqueta = elemento(
                    'span',
                    sinConfig ? 'SIN CONFIG.' : (esSaldo ? 'SALDO' : 'PALLET COMPLETO'),
                    `inline-block text-[11px] font-semibold rounded-full px-2 py-0.5 ${sinConfig ? 'text-orange-700 bg-orange-100' : (esSaldo ? 'text-blue-700 bg-blue-100' : 'text-gray-600 bg-gray-100')}`
                );
                encabezado.appendChild(etiqueta);
                card.appendChild(encabezado);

                pallet.items.forEach(item => {
                    const fila = elemento('div', '', 'flex justify-between gap-3 text-sm py-2 border-t border-gray-100 first:border-0');
                    const detalle = elemento('div', '', 'min-w-0');
                    detalle.appendChild(elemento('p', item.codigo, 'font-mono text-gray-800 break-all'));
                    if (item.descrip) detalle.appendChild(elemento('p', item.descrip, 'text-xs text-gray-600'));
                    if (item.descrip1) detalle.appendChild(elemento('p', item.descrip1, 'text-xs text-gray-500'));
                    detalle.appendChild(elemento('p', `Lote: ${item.clote || 'Sin lote'}`, 'text-xs text-gray-400'));
                    fila.append(detalle, elemento('span', String(item.cantidad), 'font-semibold text-gray-800 shrink-0'));
                    card.appendChild(fila);
                });

                const totalFila = elemento('div', '', 'flex justify-between items-center mt-2 pt-2 border-t border-gray-100');
                totalFila.append(
                    elemento('span', 'Total en este pallet', 'text-xs text-gray-500'),
                    elemento('span', `${total} / ${capacidad || '—'}`, 'font-bold text-gray-800')
                );
                card.appendChild(totalFila);

                if (esSaldo) {
                    const compatibles = estado.pallets.filter(otro =>
                        otro.id !== pallet.id &&
                        Number(otro.capacidad || 0) === capacidad &&
                        String(otro.formato || '').toUpperCase() === String(pallet.formato || '').toUpperCase() &&
                        totalCajasPallet(otro) < capacidad &&
                        totalCajasPallet(otro) + total <= capacidad
                    );

                    if (compatibles.length) {
                        const fusion = elemento('div', '', 'mt-3 pt-3 border-t border-blue-100');
                        fusion.appendChild(elemento('label', 'Fusionar con otro pallet de saldo (mismo formato)', 'block text-xs font-medium text-gray-600 mb-1'));
                        const controles = elemento('div', '', 'flex gap-2');
                        const select = elemento('select', '', 'campo flex-1');
                        select.appendChild(new Option('Selecciona pallet destino...', ''));
                        compatibles.forEach(otro => {
                            const numero = estado.pallets.findIndex(p => p.id === otro.id) + 1;
                            select.appendChild(new Option(`Pallet Local ${numero} (${totalCajasPallet(otro)}/${capacidad})`, otro.id));
                        });
                        const boton = elemento('button', 'Fusionar', 'btn bg-blue-600 hover:bg-blue-700 text-white');
                        boton.type = 'button';
                        boton.addEventListener('click', () => fusionarPallets(pallet.id, select.value));
                        controles.append(select, boton);
                        fusion.appendChild(controles);
                        card.appendChild(fusion);
                    }
                }

                const ubicaciones = elemento('div', '', 'grid grid-cols-1 sm:grid-cols-2 gap-2 mt-3');
                const campoGalpon = elemento('div');
                campoGalpon.appendChild(elemento('label', 'Galpón', 'block text-xs font-medium text-gray-500 mb-1'));
                campoGalpon.appendChild(crearSelectGalpon(pallet));
                const campoUbicacion = elemento('div');
                campoUbicacion.appendChild(elemento('label', 'Ubicación', 'block text-xs font-medium text-gray-500 mb-1'));
                campoUbicacion.appendChild(crearSelectUbicacion(pallet));
                ubicaciones.append(campoGalpon, campoUbicacion);

                const galponSelect = campoGalpon.querySelector('select');
                galponSelect.value = pallet.galponId;
                if (pallet.galponId) {
                    const ubicacionSelect = campoUbicacion.querySelector('select');
                    cargarUbicaciones(pallet, ubicacionSelect).then(() => {
                        ubicacionSelect.value = pallet.ubicacion;
                    });
                }
                card.appendChild(ubicaciones);

                if (estado.pallets.length > 1) {
                    const copiar = elemento('div', '', 'mt-3 pt-2 border-t border-gray-100');
                    const botonMostrar = elemento('button', '📋 Copiar esta ubicación a otros pallets', 'text-xs text-blue-600 font-medium');
                    botonMostrar.type = 'button';
                    const panel = elemento('div', '', 'hidden mt-2 bg-gray-50 border border-gray-200 rounded-lg p-3 space-y-2');
                    panel.appendChild(elemento('p', 'Selecciona los pallets destino:', 'text-xs text-gray-500'));
                    const checks = [];
                    estado.pallets.forEach((otro, otroIndice) => {
                        if (otro.id === pallet.id) return;
                        const label = elemento('label', '', 'flex items-center gap-2 text-sm text-gray-700');
                        const check = elemento('input');
                        check.type = 'checkbox';
                        check.value = otro.id;
                        label.append(check, document.createTextNode(`Pallet Local ${otroIndice + 1}`));
                        panel.appendChild(label);
                        checks.push(check);
                    });
                    const acciones = elemento('div', '', 'flex gap-3 pt-1');
                    const todos = elemento('button', 'Seleccionar todos', 'text-xs text-blue-600');
                    todos.type = 'button';
                    todos.addEventListener('click', () => checks.forEach(check => check.checked = true));
                    const aplicar = elemento('button', 'Aplicar', 'btn bg-blue-600 text-white ml-auto');
                    aplicar.type = 'button';
                    aplicar.addEventListener('click', () => {
                        const destinos = checks.filter(check => check.checked).map(check => check.value);
                        if (!destinos.length) return mensaje('Selecciona al menos un pallet destino.', 'error');
                        if (!pallet.galponId || !pallet.ubicacion) return mensaje('Completa Galpón y Ubicación en este pallet antes de copiar.', 'error');
                        destinos.forEach(id => {
                            const destino = estado.pallets.find(p => String(p.id) === String(id));
                            if (destino) {
                                destino.galponId = pallet.galponId;
                                destino.galpon = pallet.galpon;
                                destino.ubicacion = pallet.ubicacion;
                            }
                        });
                        renderizarPallets();
                        mensaje(`Ubicación copiada a ${destinos.length} pallet(s).`, 'success');
                    });
                    acciones.append(todos, aplicar);
                    panel.appendChild(acciones);
                    botonMostrar.addEventListener('click', () => panel.classList.toggle('hidden'));
                    copiar.append(botonMostrar, panel);
                    card.appendChild(copiar);
                }

                // Las operaciones excepcionales se mantienen disponibles, pero fuera del flujo habitual.
                const avanzado = elemento('details', '', 'mt-3 border-t border-gray-200 pt-3');
                const resumenAvanzado = elemento('summary', 'Opciones avanzadas · discrepancias', 'text-xs font-medium text-gray-600 cursor-pointer');
                avanzado.appendChild(resumenAvanzado);
                const formulario = elemento('div', '', 'mt-3 space-y-2');
                const selectProducto = elemento('select', '', 'campo');
                selectProducto.appendChild(new Option('Seleccione producto pendiente', ''));
                estado.items.forEach(origen => {
                    const disponible = restante(origen);
                    if (disponible <= 0) return;
                    const descripcion = [origen.codigo, origen.descrip, origen.descrip1, `Lote ${origen.lote_origen || 'Sin lote'}`, `Pendiente ${disponible}`].filter(Boolean).join(' · ');
                    selectProducto.appendChild(new Option(descripcion, claveItem(origen)));
                });
                const cantidad = elemento('input', '', 'campo');
                cantidad.type = 'number'; cantidad.min = '1'; cantidad.step = '1'; cantidad.placeholder = 'Cantidad de cajas';
                const lote = elemento('input', '', 'campo');
                lote.type = 'text'; lote.maxLength = 30; lote.placeholder = 'Lote físico';
                const obsExceso = elemento('textarea', '', 'campo');
                obsExceso.rows = 2; obsExceso.maxLength = 500; obsExceso.placeholder = 'Justificación por exceso de cantidad (si corresponde)';
                const obsLote = elemento('textarea', '', 'campo');
                obsLote.rows = 2; obsLote.maxLength = 500; obsLote.placeholder = 'Justificación por diferencia de lote (si corresponde)';
                selectProducto.addEventListener('change', () => {
                    const origen = obtenerItemOrigen(selectProducto.value);
                    cantidad.value = origen ? restante(origen) : '';
                    lote.value = origen ? (origen.lote_origen || '') : '';
                });
                const agregar = elemento('button', 'Agregar producto / registrar discrepancia', 'btn bg-indigo-600 hover:bg-indigo-700 text-white');
                agregar.type = 'button';
                agregar.addEventListener('click', () => {
                    agregarProducto(pallet.id, selectProducto.value, cantidad.value, lote.value, obsExceso.value, obsLote.value);
                });
                formulario.append(selectProducto, cantidad, lote, obsExceso, obsLote, agregar);
                avanzado.appendChild(formulario);
                card.appendChild(avanzado);

                contenedor.appendChild(card);
            });
        }

        function fusionarPallets(origenId, destinoId) {
            const origen = estado.pallets.find(p => String(p.id) === String(origenId));
            const destino = estado.pallets.find(p => String(p.id) === String(destinoId));
            if (!origen || !destino) return mensaje('Selecciona un pallet válido para fusionar.', 'error');
            if (origen.id === destino.id) return mensaje('No puedes fusionar un pallet consigo mismo.', 'error');

            const formatoOrigen = String(origen.formato || '').toUpperCase();
            const formatoDestino = String(destino.formato || '').toUpperCase();
            const capacidad = Number(destino.capacidad || 0);
            if (!capacidad || formatoOrigen !== formatoDestino || Number(origen.capacidad || 0) !== capacidad) {
                return mensaje('Solo se pueden fusionar pallets del mismo formato y capacidad.', 'error');
            }
            if (totalCajasPallet(origen) >= capacidad || totalCajasPallet(destino) >= capacidad) {
                return mensaje('La fusión solo está permitida entre pallets de saldo.', 'error');
            }
            if (totalCajasPallet(origen) + totalCajasPallet(destino) > capacidad) {
                return mensaje(`La fusión supera la capacidad máxima de ${capacidad} cajas.`, 'error');
            }

            destino.items.push(...origen.items);
            if (!destino.galponId && origen.galponId) {
                destino.galponId = origen.galponId;
                destino.galpon = origen.galpon;
                destino.ubicacion = origen.ubicacion;
            }
            estado.pallets = estado.pallets.filter(p => p.id !== origen.id);
            renderizarPallets();
            actualizarResumen();
            mensaje('Saldos fusionados correctamente.', 'success');
        }

        function actualizarResumen() {
            const productos = estado.pallets.reduce(
                (total, pallet) => total + pallet.items.length, 0
            );

            const cajas = estado.pallets.reduce(
                (total, pallet) => total +
                    pallet.items.reduce((suma, item) => suma + Number(item.cantidad), 0),
                0
            );

            $('resumenPreparacion').classList.toggle('hidden', productos === 0);
            $('resumenPallets').textContent = estado.pallets.filter(p => p.items.length).length;
            $('resumenProductos').textContent = productos;
            $('resumenCajas').textContent = cajas;
        }

        function validarPreparacion() {
            const pallets = estado.pallets.filter(p => p.items.length > 0);

            if (!estado.recepcion || pallets.length === 0) {
                throw new Error('Debe preparar al menos un pallet con productos.');
            }

            for (const [index, pallet] of pallets.entries()) {
                if (!pallet.galpon || !pallet.ubicacion) {
                    throw new Error(`Debe seleccionar galpón y ubicación para el pallet ${index + 1}.`);
                }

                const formatos = new Set(
                    pallet.items.map(item =>
                        String(item.codigo).substring(5, 9).toUpperCase()
                    )
                );

                if (formatos.size !== 1) {
                    throw new Error(`El pallet ${index + 1} contiene formatos diferentes.`);
                }

                const capacidad = Number(pallet.capacidad || 0);
                const totalPallet = totalCajasPallet(pallet);

                if (!capacidad) {
                    throw new Error(`El formato ${[...formatos][0]} del pallet ${index + 1} no tiene capacidad configurada.`);
                }

                if (totalPallet > capacidad) {
                    throw new Error(`El pallet ${index + 1} supera su capacidad de ${capacidad} cajas.`);
                }

                for (const item of pallet.items) {
                    if (!item.asignaciones.length) {
                        throw new Error(`El producto ${item.codigo} no tiene asignaciones de origen.`);
                    }

                    const origen = obtenerItemOrigen(item.clave);
                    const disponible = restanteAntesDeEsteItem(item, pallet, pallets);

                    if (!origen) {
                        throw new Error(`No se encontró el detalle original de ${item.codigo}.`);
                    }

                    if (item.cantidad > disponible && !item.observacion_exceso) {
                        throw new Error(`Falta observación por exceso para ${item.codigo}.`);
                    }

                    if (
                        item.clote.trim().toUpperCase() !==
                        String(origen.lote_origen || '').trim().toUpperCase() &&
                        !item.observacion_lote
                    ) {
                        throw new Error(`Falta observación por diferencia de lote para ${item.codigo}.`);
                    }
                }
            }

            return pallets;
        }

        // Obtiene el saldo que tenía el producto antes de agregar este item.
        // El servidor vuelve a realizar el cálculo definitivo.
        function restanteAntesDeEsteItem(itemActual, palletActual, pallets) {
            const origen = obtenerItemOrigen(itemActual.clave);
            if (!origen) return 0;

            let utilizadoAntes = 0;
            let encontrado = false;

            for (const pallet of pallets) {
                for (const item of pallet.items) {
                    if (item === itemActual) {
                        encontrado = true;
                        break;
                    }

                    if (item.clave === itemActual.clave) {
                        utilizadoAntes += Number(item.cantidad);
                    }
                }

                if (encontrado) break;
            }

            return Math.max(0, Number(origen.cantidad_pendiente) - utilizadoAntes);
        }

        async function registrarIngreso() {
            if (estado.enviando) return;

            let pallets;

            try {
                pallets = validarPreparacion();
            } catch (error) {
                mensaje(error.message, 'error');
                return;
            }

            const totalCajas = pallets.reduce(
                (total, pallet) => total +
                    pallet.items.reduce((suma, item) => suma + Number(item.cantidad), 0),
                0
            );

            if (!confirm(
                `¿Confirmas el ingreso de ${pallets.length} pallets y ${totalCajas} cajas?`
            )) {
                return;
            }

            const payload = {
                recepcion: estado.recepcion,
                grupos: pallets.map(pallet => ({
                    galpon: pallet.galpon,
                    ubicacion: pallet.ubicacion,
                    items: pallet.items.map(item => ({
                        codigo: item.codigo,
                        clote: item.clote,
                        cantidad: item.cantidad,
                        observacion_exceso: item.observacion_exceso,
                        observacion_lote: item.observacion_lote,
                        asignaciones: item.asignaciones
                    }))
                }))
            };

            estado.enviando = true;
            $('btnRegistrar').disabled = true;

            mensaje('Registrando pallets. No cierres esta ventana...', 'info');

            try {
                const response = await fetch(URL_REGISTRAR, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': CSRF
                    },
                    body: JSON.stringify(payload)
                });

                const data = await response.json();

                if (!response.ok) {
                    const errores = data.errors
                        ? Object.values(data.errors).flat().join(' ')
                        : '';

                    throw new Error(
                        errores || data.message || 'No fue posible registrar el ingreso.'
                    );
                }

                $('panelResultado').classList.remove('hidden');
                $('detalleResultado').replaceChildren();

                $('detalleResultado').appendChild(
                    elemento('p', data.message || 'Ingreso registrado correctamente.')
                );

                if (Array.isArray(data.pallets)) {
                    const lista = elemento('ul', '', 'list-disc pl-5 mt-3');

                    data.pallets.forEach(pallet => {
                        lista.appendChild(
                            elemento('li', pallet.pallet || JSON.stringify(pallet))
                        );
                    });

                    $('detalleResultado').appendChild(lista);
                }

                if (Number(data.total_discrepancias || 0) > 0) {
                    const aviso = elemento(
                        'div',
                        `Se registraron ${data.total_discrepancias} discrepancia(s). Todas quedaron pendientes de revisión posterior.`,
                        'mt-4 rounded-lg border border-amber-300 bg-amber-50 p-3 text-sm text-amber-900'
                    );
                    $('detalleResultado').appendChild(aviso);

                    const enlace = elemento('a', 'Ver registro de discrepancias', 'inline-block mt-2 font-semibold text-indigo-700 underline');
                    enlace.href = @json(route('wms.recepciones.discrepancias'));
                    $('detalleResultado').appendChild(enlace);
                } else {
                    $('detalleResultado').appendChild(
                        elemento('p', 'El ingreso no generó discrepancias.', 'mt-3 text-sm text-green-700')
                    );
                }

                $('panelPreparacion').classList.add('hidden');
                mensaje('Registro completado correctamente.', 'success');

                $('panelResultado').scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });

            } catch (error) {
                mensaje(error.message || 'Error al registrar el ingreso.', 'error');

            } finally {
                estado.enviando = false;
                $('btnRegistrar').disabled = false;
            }
        }

        $('btnRegistrar').addEventListener('click', registrarIngreso);

        window.jQuery(document).ready(function () {
            campoRecepcion.select2({
                placeholder: 'Escribe el código de recepción...',
                minimumInputLength: 2,
                width: '100%',
                allowClear: true,
                ajax: {
                    url: URL_BUSCAR,
                    dataType: 'json',
                    delay: 300,
                    data: params => ({ q: params.term }),
                    processResults: data => ({
                        results: data.results || []
                    }),
                    cache: true
                },
                language: {
                    inputTooShort: () => 'Escribe al menos 2 caracteres.',
                    searching: () => 'Buscando recepciones...',
                    noResults: () => 'No se encontraron recepciones.',
                    errorLoading: () => 'No se pudieron cargar los resultados.'
                }
            });

            campoRecepcion.on('select2:select', function (e) {
                consultarRecepcion(e.params.data.id);
            });

            campoRecepcion.on('select2:clear', function () {
                estado.consulta++;
                limpiarEstado();
                ocultarMensaje();
            });
        });
    </script>
</x-app-layout>