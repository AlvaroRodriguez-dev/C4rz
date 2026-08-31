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

                <a href="{{ route('wms.ingresos.create') }}"
                    class="bg-green-600 hover:bg-green-700 rounded-2xl shadow text-white p-6 transition">
                    <div class="flex justify-center">

                        <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">

                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />

                        </svg>

                    </div>

                    <div class="mt-4 text-center font-bold">

                        Registrar

                    </div>

                    <div class="text-center text-sm">

                        Ingreso

                    </div>

                </a>
                <!-- Ver Ingresos -->

                <a href="{{ route('wms.ingresos.ver.index') }}"
                    class="bg-emerald-500 hover:bg-emerald-600 rounded-2xl shadow text-white p-6 transition">

                    <div class="flex justify-center">

                        <svg xmlns="http://www.w3.org/2000/svg"
                            class="w-12 h-12"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor">

                            <path stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 17v-6h13M9 7h13M3 7h.01M3 12h.01M3 17h.01" />

                        </svg>

                    </div>

                    <div class="mt-4 text-center font-bold">

                        Ver

                    </div>

                    <div class="text-center text-sm">

                        Ingresos

                    </div>

                </a>

                <!-- Registrar Salida -->

                <a href="{{ route('wms.salidas.create') }}"
                    class="bg-red-600 hover:bg-red-700 rounded-2xl shadow text-white p-6 transition">

                    <div class="flex justify-center">

                        <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">

                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4" />

                        </svg>

                    </div>

                    <div class="mt-4 text-center font-bold">

                        Registrar

                    </div>

                    <div class="text-center text-sm">

                        Salida

                    </div>

                </a>

                <!-- Ver Salidas -->

                <a href="{{ route('wms.salidas.ver.index') }}"
                    class="bg-rose-500 hover:bg-rose-600 rounded-2xl shadow text-white p-6 transition">

                    <div class="flex justify-center">

                        <svg xmlns="http://www.w3.org/2000/svg"
                            class="w-12 h-12"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor">

                            <path stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M3 5h18M3 12h18M3 19h18" />

                        </svg>

                    </div>

                    <div class="mt-4 text-center font-bold">

                        Ver

                    </div>

                    <div class="text-center text-sm">

                        Salidas

                    </div>

                </a>

                <!-- Reubicar -->

                <a href="{{ route('wms.reubicacion.index') }}"
                    class="bg-yellow-500 hover:bg-yellow-600 rounded-2xl shadow text-white p-6 transition">

                    <div class="flex justify-center">

                        <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">

                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 7h13m0 0l-3-3m3 3l-3 3M16 17H3m0 0l3-3m-3 3l3 3" />

                        </svg>

                    </div>

                    <div class="mt-4 text-center font-bold">

                        Reubicar

                    </div>

                    <div class="text-center text-sm">

                        Pallets

                    </div>

                </a>

            </div>

            <!-- CONSULTAS -->

            <h3 class="font-bold text-gray-700 mb-3 uppercase tracking-wide">
                Reportes (consultas)
            </h3>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">

                <a href="{{ route('wms.pallet.ver.index') }}"
                    class="bg-blue-600 hover:bg-blue-700 rounded-2xl shadow text-white p-6 text-center">

                    <div class="text-5xl">📦</div>

                    <div class="font-semibold mt-3">

                        xPallets

                    </div>

                </a>

                <a href="{{ route('wms.ubicacion.ver.index') }}"
                    class="bg-cyan-600 hover:bg-cyan-700 rounded-2xl shadow text-white p-6 text-center">

                    <div class="text-5xl">📍</div>

                    <div class="font-semibold mt-3">

                        xUbicaciones

                    </div>

                </a>

                <a href="{{ route('wms.inventario.index') }}"
                    class="bg-sky-700 hover:bg-sky-800 rounded-2xl shadow text-white p-6 text-center">

                    <div class="text-5xl">📋</div>

                    <div class="font-semibold mt-3">

                        Inventario

                    </div>

                </a>

                <a href="{{ route('wms.kardex.index') }}"
                    class="bg-slate-700 hover:bg-slate-800 rounded-2xl shadow text-white p-6 text-center">

                    <div class="text-5xl">📑</div>

                    <div class="font-semibold mt-3">

                        Kardex

                    </div>

                </a>

            </div>

            <!-- CONFIGURACIÓN -->
            <h3 class="font-bold text-gray-700 mb-3 uppercase tracking-wide">
                Codificador
            </h3>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">

                <a href="{{ route('wms.configurar.index') }}"
                    class="bg-gray-700 hover:bg-gray-800 rounded-2xl shadow text-white p-6 text-center">

                    <div class="text-5xl">

                        ⚙️

                    </div>

                    <div class="font-semibold mt-3">

                        Configuración

                    </div>

                </a>

            </div>

        </div>

    </div>

</x-app-layout>