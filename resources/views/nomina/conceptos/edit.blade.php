<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Editar concepto de Nómina</h2>
    </x-slot>

    <div class="py-6"><div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white shadow-sm sm:rounded-lg p-6">
            <form method="POST" action="{{ route('nomina.conceptos.update', $concepto) }}" class="space-y-5">
                @csrf @method('PUT')
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div><label class="block text-sm font-medium text-gray-700">Código</label><input name="codigo" value="{{ old('codigo', $concepto->codigo) }}" maxlength="50" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">@error('codigo')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror</div>
                    <div><label class="block text-sm font-medium text-gray-700">Nombre</label><input name="nombre" value="{{ old('nombre', $concepto->nombre) }}" maxlength="150" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">@error('nombre')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror</div>
                    <div><label class="block text-sm font-medium text-gray-700">Tipo</label><select name="tipo" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">@foreach(['INGRESO','DESCUENTO','FISCAL'] as $tipo)<option value="{{ $tipo }}" @selected(old('tipo', $concepto->tipo) === $tipo)>{{ $tipo }}</option>@endforeach</select></div>
                    <div><label class="block text-sm font-medium text-gray-700">Origen</label><select name="origen" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">@foreach(['CALCULADO','MANUAL','IMPORTADO'] as $origen)<option value="{{ $origen }}" @selected(old('origen', $concepto->origen) === $origen)>{{ $origen }}</option>@endforeach</select></div>
                    <div class="md:col-span-2"><label class="block text-sm font-medium text-gray-700">Descripción</label><textarea name="descripcion" rows="4" maxlength="5000" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">{{ old('descripcion', $concepto->descripcion) }}</textarea></div>
                    <div class="flex items-center gap-2"><input type="checkbox" name="activo" value="1" @checked(old('activo', $concepto->activo)) class="rounded border-gray-300"><label class="text-sm text-gray-700">Concepto activo</label></div>
                </div>
                <div class="flex justify-end gap-2"><a href="{{ route('nomina.conceptos.index') }}" class="px-4 py-2 border border-gray-300 rounded-md">Cancelar</a><button class="px-5 py-2 bg-green-600 text-white rounded-md">Actualizar</button></div>
            </form>
        </div>
    </div></div>
</x-app-layout>
