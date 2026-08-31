<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Verificar estado BD
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">

                @if ($errors->any())
                    <div class="mb-4 p-4 bg-red-100 text-red-700 rounded">
                        @foreach ($errors->all() as $error)
                            <p>{{ $error }}</p>
                        @endforeach
                    </div>
                @endif

                <form method="POST" action="{{ route('verificar-bd.listar') }}">
                    @csrf

                    <div class="mb-4">
                        <label for="servidor_ip" class="block font-medium text-sm text-gray-700">
                            IP del Servidor
                        </label>
                        <input type="text" name="servidor_ip" id="servidor_ip"
                            value="{{ old('servidor_ip') }}"
                            placeholder="Ej: 192.168.1.10"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                            required>
                    </div>

                    <div class="mb-4">
                        <label for="filtro" class="block font-medium text-sm text-gray-700">
                            Filtro nombre de base de datos
                        </label>
                        <input type="text" name="filtro" id="filtro"
                            value="{{ old('filtro') }}"
                            placeholder="Ej: emp01"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                            required>
                        <p class="text-sm text-gray-500 mt-1">
                            Se buscarán bases que contengan este texto (LIKE '%{{ old('filtro') }}%')
                        </p>
                    </div>

                    <button type="submit"
                        class="bg-indigo-600 hover:bg-indigo-700 text-black font-bold py-2 px-4 rounded">
                        Buscar Bases de Datos
                    </button>
                </form>

            </div>
        </div>
    </div>
</x-app-layout>