<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            WMS - Gestión de Almacén
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-2xl mx-auto px-4">

            @if (session('success'))
                <div class="mb-4 p-3 bg-green-100 text-green-800 rounded-lg text-sm">
                    {{ session('success') }}
                </div>
            @endif

            <div class="grid grid-cols-2 gap-4">

                <a href="{{ route('wms.configurar.index') }}"
                    class="flex flex-col items-center justify-center gap-2 bg-gray-700 hover:bg-gray-800 text-white rounded-2xl shadow p-6 h-32 text-center transition">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                    </svg>
                    <span class="font-semibold">CONFIGURAR</span>
                </a>

                <a href="{{ route('wms.ingresos.create') }}"
                    class="flex flex-col items-center justify-center gap-2 bg-green-600 hover:bg-green-700 text-white rounded-2xl shadow p-6 h-32 text-center transition">
                    <span class="text-2xl font-bold">+</span>
                    <span class="font-semibold">INGRESOS +</span>
                </a>

                <a href="{{ route('wms.ingresos.ver.index') }}"
                    class="flex flex-col items-center justify-center gap-2 bg-green-500 hover:bg-green-600 text-white rounded-2xl shadow p-6 h-32 text-center transition">
                    <span class="font-semibold">INGRESOS VER</span>
                </a>

                <a href="{{ route('wms.salidas.create') }}"
                    class="flex flex-col items-center justify-center gap-2 bg-red-600 hover:bg-red-700 text-white rounded-2xl shadow p-6 h-32 text-center transition">
                    <span class="text-2xl font-bold">-</span>
                    <span class="font-semibold">SALIDAS -</span>
                </a>

                <a href="{{ route('wms.salidas.ver.index') }}"
                    class="flex flex-col items-center justify-center gap-2 bg-red-500 hover:bg-red-600 text-white rounded-2xl shadow p-6 h-32 text-center transition">
                    <span class="font-semibold">SALIDAS VER</span>
                </a>

                <a href="{{ route('wms.inventario.index') }}"
                    class="flex flex-col items-center justify-center gap-2 bg-blue-600 hover:bg-blue-700 text-white rounded-2xl shadow p-6 h-32 text-center transition">
                    <span class="font-semibold">INVENTARIO</span>
                </a>

                <a href="{{ route('wms.pallet.ver.index') }}"
                    class="flex flex-col items-center justify-center gap-2 bg-purple-600 hover:bg-purple-700 text-white rounded-2xl shadow p-6 h-32 text-center transition">
                    <span class="font-semibold">VER PALLET</span>
                </a>

                <a href="{{ route('wms.reubicacion.index') }}"
                    class="flex flex-col items-center justify-center gap-2 bg-yellow-600 hover:bg-yellow-700 text-white rounded-2xl shadow p-6 h-32 text-center transition">
                    <span class="font-semibold">RE-UBICACIÓN</span>
                </a>

                <a href="{{ route('wms.ubicacion.ver.index') }}"
                    class="flex flex-col items-center justify-center gap-2 bg-purple-500 hover:bg-purple-600 text-white rounded-2xl shadow p-6 h-32 text-center transition">
                    <span class="font-semibold">VER UBICACIÓN</span>
                </a>

                <a href="{{ route('wms.kardex.index') }}"
                    class="flex flex-col items-center justify-center gap-2 bg-gray-700 hover:bg-gray-800 text-white rounded-2xl shadow p-6 h-32 text-center transition">
                    <span class="font-semibold">KARDEX</span>
                </a>


            </div>
        </div>
    </div>
</x-app-layout>
