<x-app-layout>

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-800">
                    WMS FABOCE
                </h2>
                <p class="text-sm text-gray-500">
                    Modelo funcional
                </p>
            </div>
        </div>
    </x-slot>

    <div class="py-5">

        <div class="max-w-5xl mx-auto px-3">

            @if (session('success'))
                <div class="mb-4 rounded-xl bg-green-100 border border-green-300 text-green-800 p-3">
                    {{ session('success') }}
                </div>
            @endif

            <!-- OPERACIONES -->

            <h3 class="font-bold text-gray-700 mb-3 uppercase tracking-wide">
                Registros
            </h3>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">

                <!-- Registrar Ingreso -->
                @can('wms.ingresos.create')
                    <a href="{{ route('wms.ingresos.create') }}"
                        class="bg-green-600 hover:bg-green-700 active:scale-95 rounded-2xl shadow-md text-white p-6 transition flex flex-col items-center justify-center text-center">
                        <div class="flex justify-center">
                            <!-- Ícono: Caja recibida / Entrada -->
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10m8-6l3-3m0 0l-3-3m3 3H9" />
                            </svg>
                        </div>
                        <div class="mt-3 font-bold text-base leading-tight">Registrar</div>
                        <div class="text-xs opacity-90">Ingreso</div>
                    </a>
                @endcan

                <!-- Ver Ingresos -->
                @can('wms.ingresos.ver')
                    <a href="{{ route('wms.ingresos.ver.index') }}"
                        class="bg-emerald-500 hover:bg-emerald-600 active:scale-95 rounded-2xl shadow-md text-white p-6 transition flex flex-col items-center justify-center text-center">
                        <div class="flex justify-center">
                            <!-- Ícono: Historial de Entradas / Lista con flecha hacia abajo -->
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                            </svg>
                        </div>
                        <div class="mt-3 font-bold text-base leading-tight">Ver</div>
                        <div class="text-xs opacity-90">Ingresos</div>
                    </a>
                @endcan

                <!-- Ingreso Sin Nota (Ajuste) -->
                @can('wms.ingresos.ajuste')
                    <a href="{{ route('wms.ingresos.ajuste.create') }}"
                        class="bg-emerald-600 hover:bg-emerald-700 active:scale-95 rounded-2xl shadow-md text-white p-6 transition flex flex-col items-center justify-center text-center">
                        <div class="flex justify-center">
                            <!-- Ícono: Documento con más / Ajuste rápido -->
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 9v6m3-3H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="mt-3 font-bold text-base leading-tight">Ingreso</div>
                        <div class="text-xs opacity-90">Sin Nota</div>
                    </a>
                @endcan

                <!-- Registrar Salida -->
                @can('wms.salidas.create')
                    <a href="{{ route('wms.salidas.create') }}"
                        class="bg-red-600 hover:bg-red-700 active:scale-95 rounded-2xl shadow-md text-white p-6 transition flex flex-col items-center justify-center text-center">
                        <div class="flex justify-center">
                            <!-- Ícono: Caja saliendo / Despacho -->
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                            </svg>
                        </div>
                        <div class="mt-3 font-bold text-base leading-tight">Registrar</div>
                        <div class="text-xs opacity-90">Salida</div>
                    </a>
                @endcan

                <!-- Ver Salidas -->
                @can('wms.salidas.ver')
                    <a href="{{ route('wms.salidas.ver.index') }}"
                        class="bg-rose-500 hover:bg-rose-600 active:scale-95 rounded-2xl shadow-md text-white p-6 transition flex flex-col items-center justify-center text-center">
                        <div class="flex justify-center">
                            <!-- Ícono: Documento de envíos / Salidas -->
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <div class="mt-3 font-bold text-base leading-tight">Ver</div>
                        <div class="text-xs opacity-90">Salidas</div>
                    </a>
                @endcan

                <!-- Reubicar -->
                @can('wms.reubicacion')
                    <a href="{{ route('wms.reubicacion.index') }}"
                        class="bg-amber-500 hover:bg-amber-600 active:scale-95 rounded-2xl shadow-md text-white p-6 transition flex flex-col items-center justify-center text-center">
                        <div class="flex justify-center">
                            <!-- Ícono: Movimiento de Pallet / Flechas dobles -->
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                            </svg>
                        </div>
                        <div class="mt-3 font-bold text-base leading-tight">Reubicar</div>
                        <div class="text-xs opacity-90">Pallets</div>
                    </a>
                @endcan

                <!-- Ordenes de Trabajo -->
                @can('wms.ordenes.trabajo')
                    <a href="{{ route('wms.ordenes.trabajo.index') }}"
                        class="bg-orange-500 hover:bg-orange-600 active:scale-95 rounded-2xl shadow-md text-white p-6 transition flex flex-col items-center justify-center text-center">
                        <div class="flex justify-center">
                            <!-- Ícono: Checklist / Orden de trabajo -->
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                            </svg>
                        </div>
                        <div class="mt-3 font-bold text-base leading-tight">Orden de</div>
                        <div class="text-xs opacity-90">Trabajo</div>
                    </a>
                @endcan

                <!-- Cambios de Lote -->
                @can('wms.excepciones.despacho')
                    <a href="{{ route('wms.excepciones.despacho.index') }}"
                        class="bg-amber-600 hover:bg-amber-700 active:scale-95 rounded-2xl shadow-md text-white p-6 transition flex flex-col items-center justify-center text-center">
                        <div class="flex justify-center">
                            <!-- Ícono: Etiqueta / Cambio de Lote -->
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                            </svg>
                        </div>
                        <div class="mt-3 font-bold text-base leading-tight uppercase">Ver</div>
                        <div class="text-xs opacity-90 uppercase">Cambios de Lote</div>
                    </a>
                @endcan

                @can('wms.ordenes.trabajo.ver')
                    <a href="{{ route('wms.ordenes.trabajo.ver.index') }}"
                        class="bg-amber-600 hover:bg-amber-700 active:scale-95 rounded-2xl shadow-md text-white p-6 transition flex flex-col items-center justify-center text-center">
                        <div class="flex justify-center">
                            <!-- Ícono: Etiqueta / Cambio de Lote -->
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                            </svg>
                        </div>
                        <div class="mt-3 font-bold text-base leading-tight uppercase">Ver</div>
                        <div class="text-xs opacity-90 uppercase">Ordenes de Trabajo</div>
                    </a>
                @endcan
                
            </div>

            <!-- CONSULTAS -->

            <h3 class="font-bold text-gray-700 mb-3 uppercase tracking-wide">
                Reportes (consultas)
            </h3>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">

                <!-- xPallets -->
                @can('wms.pallet.ver')
                    <a href="{{ route('wms.pallet.ver.index') }}"
                        class="bg-blue-600 hover:bg-blue-700 active:scale-95 rounded-2xl shadow-md text-white p-6 transition flex flex-col items-center justify-center text-center">
                        <div class="flex justify-center">
                            <!-- Ícono: Estructura de Pallet / Cajas acumuladas -->
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                            </svg>
                        </div>
                        <div class="font-bold mt-3 text-base leading-tight">xPallets</div>
                    </a>
                @endcan

                <!-- xUbicaciones -->
                @can('wms.ubicacion.ver')
                    <a href="{{ route('wms.ubicacion.ver.index') }}"
                        class="bg-cyan-600 hover:bg-cyan-700 active:scale-95 rounded-2xl shadow-md text-white p-6 transition flex flex-col items-center justify-center text-center">
                        <div class="flex justify-center">
                            <!-- Ícono: Mapa/Ubicación dentro del almacén -->
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </div>
                        <div class="font-bold mt-3 text-base leading-tight">xUbicaciones</div>
                    </a>
                @endcan

                <!-- Inventario -->
                @can('wms.inventario')
                    <a href="{{ route('wms.inventario.index') }}"
                        class="bg-sky-700 hover:bg-sky-800 active:scale-95 rounded-2xl shadow-md text-white p-6 transition flex flex-col items-center justify-center text-center">
                        <div class="flex justify-center">
                            <!-- Ícono: Reporte / Tabla de stock -->
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                            </svg>
                        </div>
                        <div class="font-bold mt-3 text-base leading-tight">Inventario</div>
                    </a>
                @endcan

                <!-- Kardex -->
                @can('wms.kardex')
                    <a href="{{ route('wms.kardex.index') }}"
                        class="bg-slate-700 hover:bg-slate-800 active:scale-95 rounded-2xl shadow-md text-white p-6 transition flex flex-col items-center justify-center text-center">
                        <div class="flex justify-center">
                            <!-- Ícono: Kardex / Histórico analítico -->
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M9 19v-6a2 2 0 012-2h2a2 2 0 012 2v6m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                        </div>
                        <div class="font-bold mt-3 text-base leading-tight">Kardex</div>
                    </a>
                @endcan

                <!-- REPORTE DE DESPACHO -->
                @can('wms.reporte.despacho')
                    <a href="{{ route('wms.reporte.despacho.index') }}"
                        class="bg-slate-700 hover:bg-slate-800 active:scale-95 rounded-2xl shadow-md text-white p-6 transition flex flex-col items-center justify-center text-center">
                        <div class="flex justify-center">
                            <!-- Ícono: Kardex / Histórico analítico -->
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M9 19v-6a2 2 0 012-2h2a2 2 0 012 2v6m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                        </div>
                        <div class="font-bold mt-3 text-base leading-tight">REPORTE NOTAS DESPACHADAS</div>
                    </a>
                @endcan

            </div>

            <!-- CONFIGURACIÓN -->
            <h3 class="font-bold text-gray-700 mb-3 uppercase tracking-wide">
                Codificador
            </h3>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                @can('wms.configurar')
                    <a href="{{ route('wms.configurar.index') }}"
                        class="bg-gray-700 hover:bg-gray-800 active:scale-95 rounded-2xl shadow-md text-white p-6 transition flex flex-col items-center justify-center text-center">
                        <div class="flex justify-center">
                            <!-- Ícono: Ajustes / Engranaje -->
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </div>
                        <div class="font-bold mt-3 text-base leading-tight">Configuración</div>
                    </a>
                @endcan

            </div>

        </div>

    </div>

</x-app-layout>
