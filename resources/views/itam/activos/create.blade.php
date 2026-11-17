<x-app-layout>
    <x-slot name="header">
        <h2 class="text-2xl font-bold text-gray-800">Registrar activo tecnológico</h2>
        <p class="text-sm text-gray-500">El código ITAM será generado automáticamente.</p>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
            @if ($errors->any())
                <div class="mb-4 rounded-lg bg-red-50 p-4 text-sm text-red-700"><ul class="list-inside list-disc">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
            @endif
            <form method="POST" action="{{ route('itam.activos.store') }}" class="space-y-6 rounded-xl bg-white p-6 shadow">
                @csrf
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <label class="text-sm font-medium">Origen del registro
                        <select name="origen_documento" required class="mt-1 w-full rounded-lg border-gray-300">
                            <option value="INVENTARIO_INICIAL" @selected(old('origen_documento') === 'INVENTARIO_INICIAL')>Inventario inicial</option>
                            <option value="ADQUISICION" @selected(old('origen_documento') === 'ADQUISICION')>Adquisición</option>
                        </select>
                    </label>
                    <label class="text-sm font-medium">Tipo de activo
                        <select name="tipo_activo_id" required class="mt-1 w-full rounded-lg border-gray-300">
                            <option value="">Seleccione...</option>
                            @foreach ($tiposActivo as $tipo)<option value="{{ $tipo->id }}" @selected(old('tipo_activo_id') == $tipo->id)>{{ $tipo->descripcion }}{{ $tipo->requiere_serial ? ' *' : '' }}</option>@endforeach
                        </select>
                    </label>
                    <label class="text-sm font-medium">Marca
                        <select name="marca_id" class="mt-1 w-full rounded-lg border-gray-300">
                            <option value="">Sin especificar</option>
                            @foreach ($marcas as $marca)<option value="{{ $marca->id }}" @selected(old('marca_id') == $marca->id)>{{ $marca->nombre }}</option>@endforeach
                        </select>
                    </label>
                    <label class="text-sm font-medium">Modelo
                        <select name="modelo_id" class="mt-1 w-full rounded-lg border-gray-300">
                            <option value="">Sin especificar</option>
                            @foreach ($modelos as $modelo)<option value="{{ $modelo->id }}" @selected(old('modelo_id') == $modelo->id)>{{ $modelo->marca?->nombre }} — {{ $modelo->nombre }}</option>@endforeach
                        </select>
                    </label>
                    <label class="text-sm font-medium">Número de serie
                        <input name="numero_serie" value="{{ old('numero_serie') }}" maxlength="150" class="mt-1 w-full rounded-lg border-gray-300" placeholder="Serie del fabricante">
                    </label>
                    <label class="text-sm font-medium">Número de parte
                        <input name="numero_parte" value="{{ old('numero_parte') }}" maxlength="150" class="mt-1 w-full rounded-lg border-gray-300">
                    </label>
                    <label class="text-sm font-medium">Ubicación inicial
                        <select name="ubicacion_id" required class="mt-1 w-full rounded-lg border-gray-300">
                            <option value="">Seleccione...</option>
                            @foreach ($ubicaciones as $ubicacion)<option value="{{ $ubicacion->id }}" @selected(old('ubicacion_id') == $ubicacion->id)>{{ $ubicacion->codigo }} — {{ $ubicacion->descripcion }}</option>@endforeach
                        </select>
                    </label>
                    <label class="text-sm font-medium">Fecha de adquisición
                        <input type="date" name="fecha_adquisicion" value="{{ old('fecha_adquisicion') }}" class="mt-1 w-full rounded-lg border-gray-300">
                    </label>
                    <label class="text-sm font-medium">Costo
                        <input type="number" min="0" step="0.01" name="costo" value="{{ old('costo') }}" class="mt-1 w-full rounded-lg border-gray-300">
                    </label>
                    <label class="text-sm font-medium">Vencimiento de garantía
                        <input type="date" name="garantia_fecha" value="{{ old('garantia_fecha') }}" class="mt-1 w-full rounded-lg border-gray-300">
                    </label>
                </div>
                <label class="block text-sm font-medium">Observaciones
                    <textarea name="observaciones" rows="3" class="mt-1 w-full rounded-lg border-gray-300">{{ old('observaciones') }}</textarea>
                </label>
                <p class="text-xs text-gray-500">* Los tipos marcados requieren número de serie. El registro se crea como DISPONIBLE y sin custodio; la asignación se realizará en el siguiente proceso.</p>
                <div class="flex justify-end gap-3">
                    <a href="{{ route('itam.activos.index') }}" class="rounded-lg border px-4 py-2 text-sm">Cancelar</a>
                    <button class="rounded-lg bg-blue-600 px-5 py-2 text-sm font-semibold text-white hover:bg-blue-700">Registrar activo</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>