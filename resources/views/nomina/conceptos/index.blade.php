<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Conceptos de Nómina</h2>
            <a href="{{ route('nomina.conceptos.create') }}" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">Nuevo concepto</a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @if (session('success'))
                <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">{{ session('success') }}</div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Código</label>
                        <input type="text" name="codigo" value="{{ request('codigo') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Tipo</label>
                        <select name="tipo" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            <option value="">Todos</option>
                            @foreach (['INGRESO', 'DESCUENTO', 'FISCAL'] as $tipo)
                                <option value="{{ $tipo }}" @selected(request('tipo') === $tipo)>{{ $tipo }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Estado</label>
                        <select name="activo" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            <option value="">Todos</option>
                            <option value="1" @selected(request('activo') === '1')>ACTIVO</option>
                            <option value="0" @selected(request('activo') === '0')>INACTIVO</option>
                        </select>
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" class="px-4 py-2 bg-gray-800 text-white rounded-md hover:bg-gray-700">Buscar</button>
                        <a href="{{ route('nomina.conceptos.index') }}" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700">Limpiar</a>
                    </div>
                </form>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Código</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Concepto</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipo</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Origen</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Acción</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse ($conceptos as $concepto)
                                <tr>
                                    <td class="px-6 py-4 font-mono text-sm">{{ $concepto->codigo }}</td>
                                    <td class="px-6 py-4"><div class="font-medium text-gray-900">{{ $concepto->nombre }}</div><div class="text-sm text-gray-500">{{ $concepto->descripcion }}</div></td>
                                    <td class="px-6 py-4">{{ $concepto->tipo }}</td>
                                    <td class="px-6 py-4">{{ $concepto->origen }}</td>
                                    <td class="px-6 py-4">{{ $concepto->activo ? 'ACTIVO' : 'INACTIVO' }}</td>
                                    <td class="px-6 py-4 text-right"><a href="{{ route('nomina.conceptos.edit', $concepto) }}" class="px-3 py-2 bg-gray-800 text-white rounded-md text-sm">Editar</a></td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="px-6 py-8 text-center text-gray-500">No existen conceptos registrados.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($conceptos->hasPages())<div class="p-4">{{ $conceptos->links() }}</div>@endif
            </div>
        </div>
    </div>
</x-app-layout>
