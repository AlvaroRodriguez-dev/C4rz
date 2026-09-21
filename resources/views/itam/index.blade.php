<x-app-layout>

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-800">
                    ITAM FABOCE
                </h2>
                <p class="text-sm text-gray-500">
                    Gestión de activos de tecnología de la información
                </p>
            </div>
        </div>
    </x-slot>

    <div class="py-5">
        <div class="max-w-5xl mx-auto px-3">

            <!-- GESTIÓN -->
            <h3 class="font-bold text-gray-700 mb-3 uppercase tracking-wide">
                Gestión
            </h3>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">

                @can('it.solicitudes.view')
                    <a href="{{ route('itam.solicitudes.index') }}"
                        class="bg-blue-600 hover:bg-blue-700 active:scale-95 rounded-2xl shadow-md text-white p-6 transition flex flex-col items-center justify-center text-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414A1 1 0 0119 9.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <div class="mt-3 font-bold text-base leading-tight">Solicitudes</div>
                        <div class="text-xs opacity-90">Requerimientos TI</div>
                    </a>
                @endcan

                @can('it.solicitudes.create')
                    <a href="{{ route('itam.solicitudes.create') }}"
                        class="bg-cyan-600 hover:bg-cyan-700 active:scale-95 rounded-2xl shadow-md text-white p-6 transition flex flex-col items-center justify-center text-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M12 4v16m8-8H4" />
                        </svg>
                        <div class="mt-3 font-bold text-base leading-tight">Nueva</div>
                        <div class="text-xs opacity-90">Solicitud TI</div>
                    </a>
                @endcan

                <div class="bg-gray-400 rounded-2xl shadow-md text-white p-6 flex flex-col items-center justify-center text-center opacity-80">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 5v6m3-3h-6" />
                    </svg>
                    <div class="mt-3 font-bold text-base leading-tight">Evaluación</div>
                    <div class="text-xs opacity-90">Próximamente</div>
                </div>

                <div class="bg-gray-400 rounded-2xl shadow-md text-white p-6 flex flex-col items-center justify-center text-center opacity-80">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10" />
                    </svg>
                    <div class="mt-3 font-bold text-base leading-tight">Activos</div>
                    <div class="text-xs opacity-90">Próximamente</div>
                </div>

            </div>

            <!-- ADQUISICIONES -->
            <h3 class="font-bold text-gray-700 mb-3 uppercase tracking-wide">
                Adquisiciones
            </h3>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">

                <div class="bg-gray-400 rounded-2xl shadow-md text-white p-6 flex flex-col items-center justify-center text-center opacity-80">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M9 17v-2a4 4 0 014-4h6m0 0l-3-3m3 3l-3 3M5 7h4m-4 4h4m-4 4h2" />
                    </svg>
                    <div class="mt-3 font-bold text-base leading-tight">Cotizaciones</div>
                    <div class="text-xs opacity-90">Próximamente</div>
                </div>

                <div class="bg-gray-400 rounded-2xl shadow-md text-white p-6 flex flex-col items-center justify-center text-center opacity-80">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M9 5h6m-6 4h6m-6 4h6m-6 4h6M5 5h.01M5 9h.01M5 13h.01M5 17h.01" />
                    </svg>
                    <div class="mt-3 font-bold text-base leading-tight">Comparación</div>
                    <div class="text-xs opacity-90">Próximamente</div>
                </div>

                <div class="bg-gray-400 rounded-2xl shadow-md text-white p-6 flex flex-col items-center justify-center text-center opacity-80">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M9 12l2 2 4-4m5-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div class="mt-3 font-bold text-base leading-tight">Aprobaciones</div>
                    <div class="text-xs opacity-90">Próximamente</div>
                </div>

                <div class="bg-gray-400 rounded-2xl shadow-md text-white p-6 flex flex-col items-center justify-center text-center opacity-80">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M3 7h18M5 7v12h14V7M8 4h8l1 3H7l1-3z" />
                    </svg>
                    <div class="mt-3 font-bold text-base leading-tight">Órdenes de Compra</div>
                    <div class="text-xs opacity-90">Próximamente</div>
                </div>

            </div>

            <!-- CONTROL -->
            <h3 class="font-bold text-gray-700 mb-3 uppercase tracking-wide">
                Control de Activos
            </h3>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">

                <div class="bg-gray-400 rounded-2xl shadow-md text-white p-6 flex flex-col items-center justify-center text-center opacity-80">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                    </svg>
                    <div class="mt-3 font-bold text-base leading-tight">Inventario</div>
                    <div class="text-xs opacity-90">Próximamente</div>
                </div>

                <div class="bg-gray-400 rounded-2xl shadow-md text-white p-6 flex flex-col items-center justify-center text-center opacity-80">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M17 8l4 4m0 0l-4 4m4-4H3m4-8l-4 4m0 0l4 4" />
                    </svg>
                    <div class="mt-3 font-bold text-base leading-tight">Movimientos</div>
                    <div class="text-xs opacity-90">Próximamente</div>
                </div>

                <div class="bg-gray-400 rounded-2xl shadow-md text-white p-6 flex flex-col items-center justify-center text-center opacity-80">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M11 3a1 1 0 012 0v4a1 1 0 11-2 0V3zM6.343 6.343a1 1 0 011.414 1.414L5.93 9.586a1 1 0 01-1.414-1.414l1.827-1.829zM3 11a1 1 0 100 2h4a1 1 0 100-2H3zm13.414-4.657a1 1 0 011.414-1.414l1.829 1.827a1 1 0 01-1.414 1.414l-1.829-1.827zM17 11a1 1 0 100 2h4a1 1 0 100-2h-4zM6.343 17.657a1 1 0 011.414-1.414l1.829 1.827a1 1 0 01-1.414 1.414l-1.829-1.827z" />
                    </svg>
                    <div class="mt-3 font-bold text-base leading-tight">Mantenimiento</div>
                    <div class="text-xs opacity-90">Próximamente</div>
                </div>

                <div class="bg-gray-400 rounded-2xl shadow-md text-white p-6 flex flex-col items-center justify-center text-center opacity-80">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M9 17v-6a2 2 0 012-2h2a2 2 0 012 2v6m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                    <div class="mt-3 font-bold text-base leading-tight">Reportes</div>
                    <div class="text-xs opacity-90">Próximamente</div>
                </div>

            </div>

        </div>
    </div>

</x-app-layout>
