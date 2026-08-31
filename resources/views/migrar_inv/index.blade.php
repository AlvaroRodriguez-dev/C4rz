<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Migrar Datos Inventario
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">

                @if ($errors->any())
                    <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
                        <ul class="list-disc pl-5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('migrar.inv.ejecutar') }}">
                    @csrf

                    {{-- CONEXIÓN --}}
                    <h3 class="text-lg font-semibold text-gray-700 mb-4 border-b pb-2">Conexión</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">IP Servidor Origen</label>
                            <input type="text" name="ip_origen" value="{{ old('ip_origen') }}"
                                   placeholder="192.168.1.10"
                                   class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Base de Datos Origen</label>
                            <input type="text" name="bd_origen" value="{{ old('bd_origen') }}"
                                   placeholder="SISINV_EMPRESA_A"
                                   class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">IP Servidor Destino</label>
                            <input type="text" name="ip_destino" value="{{ old('ip_destino') }}"
                                   placeholder="192.168.1.20"
                                   class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Base de Datos Destino</label>
                            <input type="text" name="bd_destino" value="{{ old('bd_destino') }}"
                                   placeholder="SISINV_EMPRESA_B"
                                   class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                    </div>

                    {{-- CONSULTA --}}
                    <h3 class="text-lg font-semibold text-gray-700 mb-4 border-b pb-2">Parámetros de Consulta</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Fecha Inicial</label>
                            <input type="date" name="fecha_inicial" value="{{ old('fecha_inicial') }}"
                                   class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Fecha Final</label>
                            <input type="date" name="fecha_final" value="{{ old('fecha_final') }}"
                                   class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de Base</label>
                        <select name="tipo_base" id="tipo_base"
                                class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="">-- Seleccione --</option>
                            <option value="SUMINISTROS"        {{ old('tipo_base') === 'SUMINISTROS'        ? 'selected' : '' }}>Suministros</option>
                            <option value="PRODUCTO_TERMINADO" {{ old('tipo_base') === 'PRODUCTO_TERMINADO' ? 'selected' : '' }}>Producto Terminado</option>
                        </select>
                    </div>

                    {{-- Campos condicionales: SUMINISTROS --}}
                    <div id="bloque_suministros" class="hidden mb-4">
                        <div class="flex items-center">
                            <input type="checkbox" name="excluir_in" id="excluir_in" value="1"
                                   {{ old('excluir_in') ? 'checked' : '' }}
                                   class="h-4 w-4 text-indigo-600 border-gray-300 rounded">
                            <label for="excluir_in" class="ml-2 text-sm text-gray-700">
                                Excluir documentos con prefijo <code class="bg-gray-100 px-1 rounded">IN</code>
                                (RDOCUM / EDOCUM NOT LIKE 'IN%')
                            </label>
                        </div>
                    </div>

                    {{-- Campos condicionales: PRODUCTO TERMINADO --}}
                    <div id="bloque_pt" class="hidden mb-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Prefijo Proveedor
                                <span class="text-gray-400 font-normal">(ej: IP → PROCODIGO LIKE 'IP%')</span>
                            </label>
                            <input type="text" name="proveedor" value="{{ old('proveedor') }}"
                                   placeholder="IP"
                                   class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Carácter Caja
                                <span class="text-gray-400 font-normal">(0-9 → VCAJA NOT LIKE '%X')</span>
                            </label>
                            <input type="text" name="caja" value="{{ old('caja') }}"
                                   maxlength="1" placeholder="0"
                                   class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                    </div>

                    <div class="flex justify-end mt-6">
                        <button type="submit"
                                class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-6 rounded-md transition">
                            Iniciar Migración
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- JS para mostrar/ocultar campos condicionales --}}
    <script>
        const selectTipo      = document.getElementById('tipo_base');
        const bloqueSumin     = document.getElementById('bloque_suministros');
        const bloquePT        = document.getElementById('bloque_pt');

        function actualizarBloques() {
            const val = selectTipo.value;
            bloqueSumin.classList.toggle('hidden', val !== 'SUMINISTROS');
            bloquePT.classList.toggle('hidden',    val !== 'PRODUCTO_TERMINADO');
        }

        selectTipo.addEventListener('change', actualizarBloques);
        // Ejecutar al cargar por si hay old() values
        actualizarBloques();
    </script>
</x-app-layout>