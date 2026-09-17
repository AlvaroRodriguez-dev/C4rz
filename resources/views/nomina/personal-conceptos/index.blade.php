<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Conceptos por Personal</h2>
            <a href="{{ route('nomina.personal-conceptos.create') }}" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">Nueva asignación</a>
        </div>
    </x-slot>

    <div class="py-6"><div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
        @if (session('success'))<div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">{{ session('success') }}</div>@endif

        <div class="bg-white shadow-sm sm:rounded-lg p-6">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
                <div><label class="block text-sm font-medium text-gray-700">LICENSE</label><input name="license" value="{{ request('license') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" placeholder="Ej. 5287807"></div>
                <div><label class="block text-sm font-medium text-gray-700">Concepto</label><select name="concepto_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"><option value="">Todos</option>@foreach($conceptos as $concepto)<option value="{{ $concepto->id }}" @selected((string)request('concepto_id') === (string)$concepto->id)>{{ $concepto->codigo }} - {{ $concepto->nombre }}</option>@endforeach</select></div>
                <div class="flex gap-2"><button class="px-4 py-2 bg-gray-800 text-white rounded-md">Buscar</button><a href="{{ route('nomina.personal-conceptos.index') }}" class="px-4 py-2 border border-gray-300 rounded-md">Limpiar</a></div>
            </form>
        </div>

        <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden"><div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200"><thead class="bg-gray-50"><tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Personal</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Concepto</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Vigencia</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipo / Valor</th><th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Acción</th>
            </tr></thead><tbody class="divide-y divide-gray-200">
                @forelse($asignaciones as $item)<tr>
                    <td class="px-6 py-4"><div class="font-medium">{{ $item->personal->nombre_completo }}</div><div class="text-sm text-gray-500">{{ $item->personal->license }}</div></td>
                    <td class="px-6 py-4"><div class="font-medium">{{ $item->concepto->nombre }}</div><div class="text-sm text-gray-500">{{ $item->concepto->codigo }}</div></td>
                    <td class="px-6 py-4 text-sm">{{ $item->fecha_inicio?->format('d/m/Y') }} — {{ $item->fecha_fin?->format('d/m/Y') ?? 'VIGENTE' }}</td>
                    <td class="px-6 py-4">{{ $item->tipo_valor }}: {{ $item->valor }}</td>
                    <td class="px-6 py-4 text-right"><a href="{{ route('nomina.personal-conceptos.edit', $item) }}" class="px-3 py-2 bg-gray-800 text-white rounded-md text-sm">Editar</a></td>
                </tr>@empty<tr><td colspan="5" class="px-6 py-8 text-center text-gray-500">No existen asignaciones registradas.</td></tr>@endforelse
            </tbody></table>
        </div>@if($asignaciones->hasPages())<div class="p-4">{{ $asignaciones->links() }}</div>@endif</div>
    </div></div>
</x-app-layout>
