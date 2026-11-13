@php
    $esEdicion = $galpon !== null;
@endphp

<div class="bg-white shadow rounded-xl p-5">
    @if ($errors->any())
        <div class="mb-4 rounded-xl bg-red-100 border border-red-300 text-red-800 p-3">
            <ul class="list-disc list-inside text-sm">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ $action }}" method="POST" class="space-y-5">
        @csrf
        @if ($method !== 'POST')
            @method($method)
        @endif

        <div>
            <label for="almacen_id" class="block text-sm font-semibold text-gray-700 mb-1">Almacén principal</label>
            <select id="almacen_id" name="almacen_id" class="w-full rounded-lg border-gray-300" required>
                <option value="">Seleccione...</option>
                @foreach ($almacenes as $almacen)
                    <option value="{{ $almacen->id }}"
                        @selected((string) old('almacen_id', $galpon?->almacen_id) === (string) $almacen->id)>
                        {{ $almacen->codigo }} - {{ $almacen->nombre }}
                    </option>
                @endforeach
            </select>
            <p class="mt-1 text-xs text-gray-500">Los galpones representan áreas físicas del almacén principal.</p>
        </div>

        <div>
            <label for="codigo" class="block text-sm font-semibold text-gray-700 mb-1">Código</label>
            <input id="codigo" name="codigo" type="text" maxlength="20"
                   value="{{ old('codigo', $galpon?->codigo) }}"
                   class="w-full rounded-lg border-gray-300 uppercase"
                   required>
            <p class="mt-1 text-xs text-gray-500">Ejemplo: G1, G2, G3.</p>
        </div>

        <div>
            <label for="nombre" class="block text-sm font-semibold text-gray-700 mb-1">Nombre</label>
            <input id="nombre" name="nombre" type="text" maxlength="100"
                   value="{{ old('nombre', $galpon?->nombre) }}"
                   class="w-full rounded-lg border-gray-300"
                   required>
        </div>

        <label class="flex items-center gap-2">
            <input type="checkbox" name="activo" value="1"
                   @checked(old('activo', $galpon?->activo ?? true))>
            <span class="text-sm font-semibold text-gray-700">Galpón activo</span>
        </label>

        <div class="rounded-lg bg-blue-50 border border-blue-200 p-3 text-sm text-blue-800">
            Las posiciones no se registran manualmente en esta pantalla.
            Se configurarán posteriormente mediante tramos de numeración.
        </div>

        <div class="flex justify-end gap-2 pt-2">
            <a href="{{ route('wms.galpones.index', ['almacen' => old('almacen_id', $galpon?->almacen_id)]) }}"
               class="px-4 py-2 rounded-lg border border-gray-300 text-gray-700">
                Cancelar
            </a>
            <button type="submit"
                    class="px-5 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-semibold">
                {{ $esEdicion ? 'Guardar cambios' : 'Registrar galpón' }}
            </button>
        </div>
    </form>
</div>
