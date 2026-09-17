<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800 leading-tight">Editar Asignación de Concepto</h2></x-slot>
    <div class="py-6"><div class="max-w-4xl mx-auto sm:px-6 lg:px-8"><div class="bg-white shadow-sm sm:rounded-lg p-6">
        <form method="POST" action="{{ route('nomina.personal-conceptos.update', $personalConcepto) }}" class="space-y-5">@csrf @method('PUT')
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2"><label class="block text-sm font-medium text-gray-700">Personal</label><select name="rh_personal_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">@foreach($personal as $persona)<option value="{{ $persona->id }}" @selected($personalConcepto->rh_personal_id == $persona->id)>{{ $persona->license }} — {{ $persona->nombre_completo }}</option>@endforeach</select></div>
                <div class="md:col-span-2"><label class="block text-sm font-medium text-gray-700">Concepto</label><select name="concepto_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">@foreach($conceptos as $concepto)<option value="{{ $concepto->id }}" @selected($personalConcepto->concepto_id == $concepto->id)>{{ $concepto->codigo }} — {{ $concepto->nombre }} ({{ $concepto->tipo }})</option>@endforeach</select></div>
                <div><label class="block text-sm font-medium text-gray-700">Fecha inicio</label><input type="date" name="fecha_inicio" value="{{ old('fecha_inicio', $personalConcepto->fecha_inicio?->format('Y-m-d')) }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"></div>
                <div><label class="block text-sm font-medium text-gray-700">Fecha fin</label><input type="date" name="fecha_fin" value="{{ old('fecha_fin', $personalConcepto->fecha_fin?->format('Y-m-d')) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"></div>
                <div><label class="block text-sm font-medium text-gray-700">Tipo de valor</label><select name="tipo_valor" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">@foreach(['IMPORTE','PORCENTAJE','CANTIDAD'] as $tipo)<option value="{{ $tipo }}" @selected($personalConcepto->tipo_valor === $tipo)>{{ $tipo }}</option>@endforeach</select></div>
                <div><label class="block text-sm font-medium text-gray-700">Valor</label><input type="number" step="0.000001" min="0" name="valor" value="{{ old('valor', $personalConcepto->valor) }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"></div>
                <div class="md:col-span-2"><label class="block text-sm font-medium text-gray-700">Observaciones</label><textarea name="observaciones" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">{{ old('observaciones', $personalConcepto->observaciones) }}</textarea></div>
            </div>
            <div class="flex justify-end gap-2"><a href="{{ route('nomina.personal-conceptos.index') }}" class="px-4 py-2 border border-gray-300 rounded-md">Cancelar</a><button class="px-5 py-2 bg-green-600 text-white rounded-md">Actualizar</button></div>
        </form>
    </div></div></div>
</x-app-layout>
