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

            <a href="{{ route('wms.index') }}"
               class="text-sm text-gray-600 hover:text-blue-600">
                &larr; Volver al menú WMS
            </a>

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
                            Distribuye los productos pendientes entre uno o varios pallets.
                        </p>
                    </div>

                    <button type="button" id="btnAgregarPallet"
                            class="btn bg-blue-600 hover:bg-blue-700 text-white">
                        + Agregar pallet
                    </button>
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
                    agregarPallet();
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
                const card = elemento('div', '', 'border rounded-xl p-4');
                card.dataset.pallet = pallet.id;

                const encabezado = elemento('div', '', 'flex justify-between items-center mb-4');
                encabezado.append(
                    elemento('h4', `Pallet ${indice + 1}`, 'font-semibold'),
                );

                const btnEliminar = elemento(
                    'button',
                    'Eliminar pallet',
                    'text-sm text-red-600 hover:text-red-800'
                );

                btnEliminar.type = 'button';
                btnEliminar.addEventListener('click', () => eliminarPallet(pallet.id));
                encabezado.appendChild(btnEliminar);
                card.appendChild(encabezado);

                const ubicaciones = elemento('div', '', 'grid grid-cols-1 sm:grid-cols-2 gap-3');
                const campoGalpon = elemento('div');
                campoGalpon.appendChild(elemento('label', 'Galpón', 'block text-sm font-medium mb-1'));
                campoGalpon.appendChild(crearSelectGalpon(pallet));

                const campoUbicacion = elemento('div');
                campoUbicacion.appendChild(elemento('label', 'Ubicación', 'block text-sm font-medium mb-1'));
                campoUbicacion.appendChild(crearSelectUbicacion(pallet));

                ubicaciones.append(campoGalpon, campoUbicacion);
                card.appendChild(ubicaciones);

                // Recuperar las selecciones actuales al volver a renderizar.
                const galponSelect = campoGalpon.querySelector('select');
                galponSelect.value = pallet.galponId;

                if (pallet.galponId) {
                    const ubicacionSelect = campoUbicacion.querySelector('select');
                    cargarUbicaciones(pallet, ubicacionSelect).then(() => {
                        ubicacionSelect.value = pallet.ubicacion;
                    });
                }

                const separador = elemento('div', '', 'border-t mt-4 pt-4');
                separador.appendChild(elemento('h5', 'Agregar producto', 'font-medium text-sm mb-3'));

                const formulario = elemento('div', '', 'grid grid-cols-1 sm:grid-cols-2 gap-3');

                const selectProducto = elemento('select', '', 'campo');
                selectProducto.appendChild(new Option('Seleccione producto pendiente', ''));

                estado.items.forEach(item => {
                    const clave = claveItem(item);
                    const disponible = restante(item);

                    const descripciones = [item.descrip, item.descrip1]
                        .filter(Boolean)
                        .join(' · ');

                    const textoProducto = [
                        item.codigo,
                        descripciones,
                        `Lote ${item.lote_origen || 'Sin lote'}`,
                        `Pendiente ${disponible}`
                    ].filter(Boolean).join(' · ');

                    selectProducto.appendChild(
                        new Option(textoProducto, clave)
                    );
                });

                const inputCantidad = elemento('input', '', 'campo');
                inputCantidad.type = 'number';
                inputCantidad.min = '1';
                inputCantidad.step = '1';
                inputCantidad.placeholder = 'Cantidad de cajas';

                const inputLote = elemento('input', '', 'campo');
                inputLote.type = 'text';
                inputLote.maxLength = 30;
                inputLote.placeholder = 'Lote físico';

                const inputObsExceso = elemento('textarea', '', 'campo');
                inputObsExceso.rows = 2;
                inputObsExceso.maxLength = 500;
                inputObsExceso.placeholder = 'Observación por exceso de cantidad (si corresponde)';

                const inputObsLote = elemento('textarea', '', 'campo');
                inputObsLote.rows = 2;
                inputObsLote.maxLength = 500;
                inputObsLote.placeholder = 'Observación por diferencia de lote (si corresponde)';

                selectProducto.addEventListener('change', () => {
                    const origen = obtenerItemOrigen(selectProducto.value);

                    inputCantidad.value = origen ? restante(origen) : '';
                    inputLote.value = origen ? (origen.lote_origen || '') : '';
                });

                formulario.append(
                    selectProducto,
                    inputCantidad,
                    inputLote,
                    inputObsExceso,
                    inputObsLote
                );

                separador.appendChild(formulario);

                const btnAgregar = elemento(
                    'button',
                    'Agregar producto al pallet',
                    'btn bg-indigo-600 hover:bg-indigo-700 text-white mt-3'
                );

                btnAgregar.type = 'button';
                btnAgregar.addEventListener('click', () => {
                    agregarProducto(
                        pallet.id,
                        selectProducto.value,
                        inputCantidad.value,
                        inputLote.value,
                        inputObsExceso.value,
                        inputObsLote.value
                    );
                });

                separador.appendChild(btnAgregar);
                card.appendChild(separador);

                const tabla = elemento('div', '', 'overflow-x-auto mt-4');
                const table = elemento('table', '', 'w-full text-sm');
                const thead = elemento('thead');
                const trHead = elemento('tr', '', 'border-b text-left');

                ['Producto', 'Lote físico', 'Cantidad', 'Formato', 'Acción'].forEach(t => {
                    trHead.appendChild(elemento('th', t, 'p-2'));
                });

                thead.appendChild(trHead);
                table.appendChild(thead);

                const tbody = elemento('tbody');

                pallet.items.forEach((item, itemIndex) => {
                    const tr = document.createElement('tr');
                    const formato = String(item.codigo).substring(5, 9).toUpperCase();

                    tr.append(
                        crearCeldaProducto(item),
                        crearCelda(item.clote),
                        crearCelda(item.cantidad),
                        crearCelda(formato)
                    );

                    const tdAccion = elemento('td', '', 'p-2 border-b');
                    const btnQuitar = elemento('button', 'Quitar', 'text-red-600');

                    btnQuitar.type = 'button';
                    btnQuitar.addEventListener('click', () => {
                        pallet.items.splice(itemIndex, 1);
                        renderizarPallets();
                        actualizarResumen();
                    });

                    tdAccion.appendChild(btnQuitar);
                    tr.appendChild(tdAccion);
                    tbody.appendChild(tr);
                });

                table.appendChild(tbody);
                tabla.appendChild(table);
                card.appendChild(tabla);
                contenedor.appendChild(card);
            });
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
            $('btnAgregarPallet').disabled = true;

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
                $('btnAgregarPallet').disabled = false;
            }
        }

        $('btnAgregarPallet').addEventListener('click', agregarPallet);
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