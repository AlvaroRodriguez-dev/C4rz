<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            WMS - Nueva Configuración
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-md mx-auto px-4">

            <a href="{{ route('wms.configurar.index') }}" class="text-sm text-gray-600 mb-4 inline-block">&larr; Volver</a>

            @if ($errors->any())
                <div class="mb-4 p-3 bg-red-100 text-red-800 rounded-lg text-sm">
                    <ul class="list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('wms.configurar.store') }}" method="POST" class="bg-white shadow rounded-xl p-6 space-y-5">
                @csrf

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">CÓDIGO (4 caracteres)</label>
                    <input type="text" name="codigo" maxlength="4" value="{{ old('codigo') }}"
                           class="w-full uppercase border-gray-300 rounded-lg shadow-sm text-lg p-3 tracking-widest font-mono"
                           style="text-transform:uppercase" required>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">DESCRIPCIÓN (máx. 20)</label>
                    <input type="text" name="descripcion" maxlength="20" value="{{ old('descripcion') }}"
                           class="w-full border-gray-300 rounded-lg shadow-sm text-lg p-3" required>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">CAJAS x PALLET</label>
                    <input type="number" name="cajas_x_pallet" min="1" value="{{ old('cajas_x_pallet') }}"
                           class="w-full border-gray-300 rounded-lg shadow-sm text-lg p-3" required>
                </div>

                <button type="submit"
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-4 rounded-xl shadow text-lg">
                    GUARDAR
                </button>
            </form>

        </div>
    </div>
</x-app-layout>