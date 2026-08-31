<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">WMS - Re-Ubicación</h2>
    </x-slot>

    <script src="https://cdn.jsdelivr.net/npm/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>

    <div class="py-4 px-3 sm:py-6 sm:px-4">
        <div class="max-w-3xl mx-auto">
            <a href="{{ route('wms.index') }}" class="text-sm text-gray-600 mb-3 inline-flex items-center gap-1">&larr;
                Volver</a>

            <div id="alertBox" class="hidden mb-4 p-3 rounded-lg text-sm"></div>

            {{-- Selector de modo --}}
            <div class="grid grid-cols-2 gap-2 mb-4">
                <button id="tabCompleto"
                    class="modo-btn py-3 rounded-xl font-semibold text-sm bg-gray-800 text-white">Pallet
                    Completo</button>
                <button id="tabParcial"
                    class="modo-btn py-3 rounded-xl font-semibold text-sm bg-gray-200 text-gray-700">Completar
                    Pallet</button>
            </div>

            {{-- MODO 1: Pallet completo --}}
            <div id="panelCompleto" class="space-y-4">

                <div class="bg-white shadow rounded-xl p-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Pallet Origen</label>
                    <div class="flex gap-2">
                        <input type="text" id="palletOrigenCompleto"
                            class="flex-1 border-gray-300 rounded-lg p-2.5 text-base" placeholder="Escribe o escanea">
                        <button type="button" data-target="palletOrigenCompleto"
                            class="btn-scan bg-gray-800 text-white rounded-lg px-4">📷</button>
                    </div>
                    <p id="ubicacionActualCompleto"
                        class="hidden text-xs font-medium text-blue-700 bg-blue-50 rounded-lg px-3 py-2 mt-2"></p>
                    <button id="btnVerContenidoCompleto" class="mt-2 text-sm text-blue-600 font-medium">Ver contenido de
                        este pallet</button>
                </div>

                <div id="contenidoOrigenCompleto"
                    class="hidden bg-white border border-gray-200 rounded-xl p-4 space-y-2"></div>

                <div class="bg-white shadow rounded-xl p-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Nueva Ubicación (destino)</label>
                    <div class="grid grid-cols-2 gap-2 mb-2">
                        <input type="text" id="galponDestinoCompleto"
                            class="border-gray-300 rounded-lg p-2.5 text-base" placeholder="Galpón">
                        <input type="text" id="ubicacionDestinoCompleto"
                            class="border-gray-300 rounded-lg p-2.5 text-base" placeholder="Ubicación">
                    </div>
                    <button type="button" data-ubic-target="galponDestinoCompleto|ubicacionDestinoCompleto"
                        class="btn-scan-ubic w-full bg-gray-800 text-white rounded-lg py-2.5">📷 Escanear QR de
                        ubicación</button>
                </div>

                <button id="btnGuardarCompleto"
                    class="w-full bg-blue-600 text-white font-semibold py-4 rounded-xl shadow text-lg">GUARDAR
                    RE-UBICACIÓN</button>
            </div>

            {{-- MODO 2: Completar pallet (cantidad parcial) --}}
            <div id="panelParcial" class="space-y-4 hidden">

                <div class="bg-white shadow rounded-xl p-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Pallet Origen</label>
                    <div class="flex gap-2">
                        <input type="text" id="palletOrigenParcial"
                            class="flex-1 border-gray-300 rounded-lg p-2.5 text-base" placeholder="Escribe o escanea">
                        <button type="button" data-target="palletOrigenParcial"
                            class="btn-scan bg-gray-800 text-white rounded-lg px-4">📷</button>
                    </div>
                    <p id="ubicacionActualParcial"
                        class="hidden text-xs font-medium text-blue-700 bg-blue-50 rounded-lg px-3 py-2 mt-2"></p>
                    <button id="btnCargarItemsOrigen" class="mt-2 text-sm text-blue-600 font-medium">Cargar productos de
                        este pallet</button>
                </div>

                <div id="itemsOrigenParcial" class="space-y-2"></div>

                <div id="formCantidad"
                    class="hidden bg-white border border-blue-200 bg-blue-50 rounded-xl p-4 space-y-3">
                    <p class="text-sm font-semibold text-gray-700">Producto seleccionado: <span
                            id="productoSeleccionadoTxt"></span></p>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Cantidad a mover (saldo disponible:
                            <span id="saldoDisponibleTxt"></span>)</label>
                        <input type="number" id="cantidadMover" min="1"
                            class="w-full border-gray-300 rounded-lg p-2.5 text-base">
                    </div>

                    <label class="block text-sm font-medium text-gray-700">Pallet Destino</label>
                    <div class="flex gap-2">
                        <input type="text" id="palletDestinoParcial"
                            class="flex-1 border-gray-300 rounded-lg p-2.5 text-base" placeholder="Escribe o escanea">
                        <button type="button" data-target="palletDestinoParcial"
                            class="btn-scan bg-gray-800 text-white rounded-lg px-4">📷</button>
                    </div>
                    <p id="ubicacionDestinoInfo" class="text-xs text-gray-500"></p>

                    <div id="ubicacionManualDestino" class="hidden grid grid-cols-2 gap-2">
                        <input type="text" id="galponDestinoParcial"
                            class="border-gray-300 rounded-lg p-2.5 text-base" placeholder="Galpón (pallet nuevo)">
                        <input type="text" id="ubicacionDestinoParcial"
                            class="border-gray-300 rounded-lg p-2.5 text-base" placeholder="Ubicación (pallet nuevo)">
                    </div>

                    <button id="btnGuardarParcial"
                        class="w-full bg-blue-600 text-white font-semibold py-4 rounded-xl shadow text-lg">GUARDAR
                        RE-UBICACIÓN</button>
                </div>
            </div>

            <div id="qrReader" class="hidden mt-4 rounded-xl overflow-hidden border border-gray-200"></div>
        </div>
    </div>

    <script>
        const routeContenidoPallet = "{{ url('wms/reubicacion/pallet') }}";
        const routeUbicacionPallet = (p) => `{{ url('wms/reubicacion/pallet') }}/${encodeURIComponent(p)}/ubicacion`;
        const routeGuardarCompleto = "{{ route('wms.reubicacion.pallet-completo') }}";
        const routeGuardarParcial = "{{ route('wms.reubicacion.completar-pallet') }}";
        const csrfToken = "{{ csrf_token() }}";

        let html5QrCode = null;
        let itemSeleccionado = null;
        let itemsOrigenCache = [];

        // --- Tabs ---
        document.getElementById('tabCompleto').addEventListener('click', () => cambiarModo('completo'));
        document.getElementById('tabParcial').addEventListener('click', () => cambiarModo('parcial'));

        function cambiarModo(modo) {
            const esCompleto = modo === 'completo';
            document.getElementById('panelCompleto').classList.toggle('hidden', !esCompleto);
            document.getElementById('panelParcial').classList.toggle('hidden', esCompleto);
            document.getElementById('tabCompleto').className =
                `modo-btn py-3 rounded-xl font-semibold text-sm ${esCompleto ? 'bg-gray-800 text-white' : 'bg-gray-200 text-gray-700'}`;
            document.getElementById('tabParcial').className =
                `modo-btn py-3 rounded-xl font-semibold text-sm ${!esCompleto ? 'bg-gray-800 text-white' : 'bg-gray-200 text-gray-700'}`;
        }

        // --- Escaneo genérico para inputs de texto (pallet) ---
        document.querySelectorAll('.btn-scan').forEach(btn => {
            btn.addEventListener('click', () => iniciarScanner((texto) => {
                const inputId = btn.dataset.target;
                document.getElementById(inputId).value = texto.trim();

                // Disparamos la consulta de ubicación actual según el input escaneado
                if (inputId === 'palletOrigenCompleto') {
                    mostrarUbicacionActual(texto.trim(), 'ubicacionActualCompleto');
                } else if (inputId === 'palletOrigenParcial') {
                    mostrarUbicacionActual(texto.trim(), 'ubicacionActualParcial');
                }
            }));
        });

        // --- Escaneo para ubicación (formato esperado "GALPON|UBICACION") ---
        document.querySelectorAll('.btn-scan-ubic').forEach(btn => {
            btn.addEventListener('click', () => iniciarScanner((texto) => {
                const [galpon, ubicacion] = texto.trim().split('|');
                const [idGalpon, idUbicacion] = btn.dataset.ubicTarget.split('|');
                document.getElementById(idGalpon).value = galpon ?? '';
                document.getElementById(idUbicacion).value = ubicacion ?? '';
            }));
        });

        function iniciarScanner(onSuccess) {
            const reader = document.getElementById('qrReader');
            reader.classList.remove('hidden');
            html5QrCode = new Html5Qrcode("qrReader");
            html5QrCode.start({
                    facingMode: "environment"
                }, {
                    fps: 10,
                    qrbox: 250
                },
                (decodedText) => {
                    html5QrCode.stop();
                    reader.classList.add('hidden');
                    onSuccess(decodedText);
                }
            );
        }

        // --- MODO 1: Pallet completo ---
        document.getElementById('btnVerContenidoCompleto').addEventListener('click', () => {
            const pallet = document.getElementById('palletOrigenCompleto').value.trim();
            if (!pallet) return;

            fetch(`${routeContenidoPallet}/${encodeURIComponent(pallet)}/contenido`)
                .then(res => res.json())
                .then(data => {
                    const cont = document.getElementById('contenidoOrigenCompleto');
                    cont.innerHTML = '';

                    if (data.items.length === 0) {
                        cont.innerHTML = '<p class="text-sm text-orange-600">Este pallet no tiene saldo.</p>';
                    } else {
                        const totalPallet = data.items.reduce((sum, i) => sum + i.saldo, 0);

                        cont.insertAdjacentHTML('beforeend', `
                    <div class="flex justify-between items-center pb-2 mb-1 border-b border-gray-200">
                        <span class="text-xs font-medium text-gray-500">TOTAL EN EL PALLET</span>
                        <span class="text-lg font-bold text-blue-700">${totalPallet} cajas</span>
                    </div>
                `);

                        data.items.forEach(i => {
                            cont.insertAdjacentHTML('beforeend', `
                        <div class="flex justify-between text-sm border-b border-gray-100 pb-2">
                            <div>
                                <span class="font-mono">${i.descrip ?? ''} ${i.descrip1 ?? ''}</span>
                                <p class="text-xs text-gray-500">${i.codigo} · Lote ${i.clote ?? 'S/L'}</p>
                            </div>
                            <span class="font-semibold shrink-0">${i.saldo} cajas</span>
                        </div>
                    `);
                        });
                    }
                    cont.classList.remove('hidden');
                });
        });

        document.getElementById('btnGuardarCompleto').addEventListener('click', () => {
            const body = {
                pallet_origen: document.getElementById('palletOrigenCompleto').value.trim(),
                galpon_destino: document.getElementById('galponDestinoCompleto').value.trim(),
                ubicacion_destino: document.getElementById('ubicacionDestinoCompleto').value.trim(),
            };

            fetch(routeGuardarCompleto, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify(body)
            }).then(async res => {
                const data = await res.json();
                if (!res.ok) {
                    mostrarAlerta(Object.values(data.errors ?? {}).flat().join(' '), 'error');
                    return;
                }
                mostrarAlerta(data.message, 'success');
                setTimeout(() => window.location.href = "{{ route('wms.index') }}", 1200);
            });
        });

        // --- MODO 2: Completar pallet ---
        document.getElementById('btnCargarItemsOrigen').addEventListener('click', () => {
            const pallet = document.getElementById('palletOrigenParcial').value.trim();
            if (!pallet) return;

            fetch(`${routeContenidoPallet}/${encodeURIComponent(pallet)}/contenido`)
                .then(res => res.json())
                .then(data => {
                    itemsOrigenCache = data.items;
                    const cont = document.getElementById('itemsOrigenParcial');
                    cont.innerHTML = '';
                    document.getElementById('formCantidad').classList.add('hidden');

                    if (data.items.length === 0) {
                        cont.innerHTML = '<p class="text-sm text-orange-600">Este pallet no tiene saldo.</p>';
                        return;
                    }

                    const totalPallet = data.items.reduce((sum, i) => sum + i.saldo, 0);

                    cont.insertAdjacentHTML('beforeend', `
                <div class="flex justify-between items-center pb-2 mb-1 border-b border-gray-200">
                    <span class="text-xs font-medium text-gray-500">TOTAL EN EL PALLET</span>
                    <span class="text-lg font-bold text-blue-700">${totalPallet} cajas</span>
                </div>
            `);

                    data.items.forEach((item, idx) => {
                        cont.insertAdjacentHTML('beforeend', `
                    <button data-idx="${idx}" class="w-full text-left bg-white border border-gray-200 rounded-xl p-3 item-origen">
                        <div class="flex justify-between items-start">
                            <div>
                                <span class="text-sm font-medium">${item.descrip ?? ''} ${item.descrip1 ?? ''}</span>
                                <p class="text-xs text-gray-500">${item.codigo} · Lote ${item.clote ?? 'S/L'}</p>
                            </div>
                            <span class="text-sm font-bold text-blue-700 shrink-0">${item.saldo} cajas</span>
                        </div>
                    </button>
                `);
                    });

                    document.querySelectorAll('.item-origen').forEach(btn => {
                        btn.addEventListener('click', () => seleccionarItemOrigen(itemsOrigenCache[btn
                            .dataset.idx]));
                    });
                });
        });

        function seleccionarItemOrigen(item) {
            itemSeleccionado = item;
            document.getElementById('productoSeleccionadoTxt').textContent =
                `${item.codigo} · Lote ${item.clote ?? 'S/L'}${item.descrip ? ` — ${item.descrip} ${item.descrip1 ?? ''}` : ''}`;
            document.getElementById('saldoDisponibleTxt').textContent = item.saldo;
            document.getElementById('cantidadMover').max = item.saldo;
            document.getElementById('cantidadMover').value = '';
            document.getElementById('ubicacionDestinoInfo').textContent = '';
            document.getElementById('ubicacionManualDestino').classList.add('hidden');
            document.getElementById('palletDestinoParcial').value = '';
            document.getElementById('formCantidad').classList.remove('hidden');
        }

        document.getElementById('palletDestinoParcial').addEventListener('change', function() {
            const pallet = this.value.trim();
            if (!pallet) return;

            fetch(routeUbicacionPallet(pallet))
                .then(res => res.json())
                .then(data => {
                    if (data.ubicacion) {
                        document.getElementById('ubicacionDestinoInfo').textContent =
                            `Ubicación actual: Galpón ${data.ubicacion.galpon} · ${data.ubicacion.ubicacion}`;
                        document.getElementById('ubicacionManualDestino').classList.add('hidden');
                    } else {
                        document.getElementById('ubicacionDestinoInfo').textContent =
                            'Pallet nuevo: indica dónde quedará ubicado.';
                        document.getElementById('ubicacionManualDestino').classList.remove('hidden');
                    }
                });
        });

        document.getElementById('btnGuardarParcial').addEventListener('click', () => {
            const cantidad = parseInt(document.getElementById('cantidadMover').value) || 0;
            const palletDestino = document.getElementById('palletDestinoParcial').value.trim();
            const galponManual = document.getElementById('galponDestinoParcial').value.trim();
            const ubicacionManual = document.getElementById('ubicacionDestinoParcial').value.trim();

            fetch(routeUbicacionPallet(palletDestino))
                .then(res => res.json())
                .then(data => {
                    const destinoUbicacion = data.ubicacion ?? {
                        almacen: itemSeleccionado.almacen,
                        galpon: galponManual,
                        ubicacion: ubicacionManual
                    };

                    const body = {
                        codigo: itemSeleccionado.codigo,
                        clote: itemSeleccionado.clote,
                        descrip: itemSeleccionado.descrip,
                        descrip1: itemSeleccionado.descrip1,
                        cantidad,
                        pallet_origen: itemSeleccionado.pallet,
                        almacen_origen: itemSeleccionado.almacen,
                        galpon_origen: itemSeleccionado.galpon,
                        ubicacion_origen: itemSeleccionado.ubicacion,
                        pallet_destino: palletDestino,
                        almacen_destino: destinoUbicacion.almacen,
                        galpon_destino: destinoUbicacion.galpon,
                        ubicacion_destino: destinoUbicacion.ubicacion,
                    };

                    fetch(routeGuardarParcial, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: JSON.stringify(body)
                    }).then(async res => {
                        const resData = await res.json();
                        if (!res.ok) {
                            mostrarAlerta(Object.values(resData.errors ?? {}).flat().join(' '),
                                'error');
                            return;
                        }
                        mostrarAlerta(resData.message, 'success');
                        setTimeout(() => window.location.href = "{{ route('wms.index') }}", 1200);
                    });
                });
        });

        function mostrarAlerta(msg, tipo) {
            const box = document.getElementById('alertBox');
            box.className =
                `mb-4 p-3 rounded-lg text-sm ${tipo === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}`;
            box.textContent = msg;
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });

        }

        function mostrarUbicacionActual(pallet, elementoId) {
            const el = document.getElementById(elementoId);

            if (!pallet) {
                el.classList.add('hidden');
                return;
            }

            fetch(routeUbicacionPallet(pallet))
                .then(res => res.json())
                .then(data => {
                    if (data.ubicacion) {
                        el.textContent =
                            `📍 Ubicación actual: Almacén ${data.ubicacion.almacen} · Galpón ${data.ubicacion.galpon} · ${data.ubicacion.ubicacion}`;
                        el.classList.remove('hidden');
                    } else {
                        el.textContent = '⚠️ Este pallet no tiene saldo/ubicación registrada.';
                        el.classList.remove('hidden');
                    }
                });
        }

        // Modo Pallet Completo
        document.getElementById('palletOrigenCompleto').addEventListener('change', function() {
            mostrarUbicacionActual(this.value.trim(), 'ubicacionActualCompleto');
        });

        // Modo Completar Pallet
        document.getElementById('palletOrigenParcial').addEventListener('change', function() {
            mostrarUbicacionActual(this.value.trim(), 'ubicacionActualParcial');
        });
    </script>
</x-app-layout>
