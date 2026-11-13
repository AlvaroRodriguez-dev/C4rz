@php
    $esEdicion = $almacen !== null;
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
            <label for="codigo" class="block text-sm font-semibold text-gray-700 mb-1">Código</label>
            <input id="codigo" name="codigo" type="text" maxlength="10"
                   value="{{ old('codigo', $almacen?->codigo) }}"
                   class="w-full rounded-lg border-gray-300 uppercase"
                   required>
            <p class="mt-1 text-xs text-gray-500">Identificador del almacén. Ejemplo: 110.</p>
        </div>

        <div>
            <label for="nombre" class="block text-sm font-semibold text-gray-700 mb-1">Nombre</label>
            <input id="nombre" name="nombre" type="text" maxlength="100"
                   value="{{ old('nombre', $almacen?->nombre) }}"
                   class="w-full rounded-lg border-gray-300"
                   required>
        </div>

        <div>
            <label for="tipo" class="block text-sm font-semibold text-gray-700 mb-1">Tipo</label>
            <select id="tipo" name="tipo" class="w-full rounded-lg border-gray-300" required>
                <option value="PRINCIPAL" @selected(old('tipo', $almacen?->tipo) === 'PRINCIPAL')>PRINCIPAL</option>
                <option value="SUB" @selected(old('tipo', $almacen?->tipo) === 'SUB')>SUB</option>
            </select>
        </div>

        <div id="padre-wrapper">
            <label for="almacen_padre_id" class="block text-sm font-semibold text-gray-700 mb-1">Almacén principal</label>
            <select id="almacen_padre_id" name="almacen_padre_id" class="w-full rounded-lg border-gray-300">
                <option value="">Seleccione...</option>
                @foreach ($padres as $padre)
                    <option value="{{ $padre->id }}"
                        @selected((string) old('almacen_padre_id', $almacen?->almacen_padre_id) === (string) $padre->id)>
                        {{ $padre->codigo }} - {{ $padre->nombre }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="prefijo_documento" class="block text-sm font-semibold text-gray-700 mb-1">Prefijo documental</label>
            <input id="prefijo_documento" name="prefijo_documento" type="text" maxlength="5"
                   value="{{ old('prefijo_documento', $almacen?->prefijo_documento) }}"
                   class="w-full rounded-lg border-gray-300 uppercase">
            <p class="mt-1 text-xs text-gray-500">Prefijo utilizado por el almacén/agencia en SAS-ERP.</p>
        </div>

        <label class="flex items-center gap-2">
            <input type="checkbox" name="activo" value="1"
                   @checked(old('activo', $almacen?->activo ?? true))>
            <span class="text-sm font-semibold text-gray-700">Almacén activo</span>
        </label>

        <div class="flex justify-end gap-2 pt-2">
            <a href="{{ route('wms.almacenes.index') }}"
               class="px-4 py-2 rounded-lg border border-gray-300 text-gray-700">
                Cancelar
            </a>
            <button type="submit"
                    class="px-5 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-semibold">
                {{ $esEdicion ? 'Guardar cambios' : 'Registrar almacén' }}
            </button>
        </div>
    </form>
</div>

<script>
    (() => {
        const tipo = document.getElementById('tipo');
        const wrapper = document.getElementById('padre-wrapper');
        const padre = document.getElementById('almacen_padre_id');

        function actualizarPadre() {
            const esSub = tipo.value === 'SUB';
            wrapper.classList.toggle('hidden', !esSub);
            padre.disabled = !esSub;
            padre.required = esSub;

            if (!esSub) {
                padre.value = '';
            }
        }

        tipo.addEventListener('change', actualizarPadre);
        actualizarPadre();
    })();
</script>
