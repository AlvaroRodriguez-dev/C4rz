<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Impresiones SAS</h2>
    </x-slot>

    <div class="py-8 px-4">
        <div class="max-w-2xl mx-auto">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                @if (session('error'))
                    <div class="mb-5 rounded-lg bg-red-50 border border-red-200 p-4 text-sm text-red-700">{{ session('error') }}</div>
                @endif
                @if ($errors->any())
                    <div class="mb-5 rounded-lg bg-red-50 border border-red-200 p-4 text-sm text-red-700">
                        @foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach
                    </div>
                @endif

                <h3 class="text-lg font-semibold text-gray-800 mb-1">Buscar documento</h3>
                <p class="text-sm text-gray-500 mb-6">
                    Introduce el número del documento para consultar <code>log_registro</code> y <code>log_registro_detalle</code>.
                </p>

                <form method="POST" action="{{ route('impresiones-sas.buscar') }}" class="space-y-5">
                    @csrf
                    <div>
                        <label for="numero_documento" class="block text-sm font-medium text-gray-700">Número de documento</label>
                        <input type="text" name="numero_documento" id="numero_documento" value="{{ old('numero_documento') }}"
                            class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="Ej.: 123456" required autofocus>
                    </div>
                    <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-5 py-3 text-sm font-semibold text-white hover:bg-indigo-700">
                        Buscar documento
                    </button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
