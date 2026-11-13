<x-app-layout>
    <x-slot name="header">
        <h2 class="text-2xl font-bold text-gray-800">Almacenes WMS: {{ $user->name }}</h2>
    </x-slot>

    <div class="py-6 max-w-3xl mx-auto px-4">
        <a href="{{ route('wms.usuario-almacenes.index') }}" class="text-sm text-gray-600 hover:text-gray-800">&larr; Volver</a>

        @if ($errors->any())
            <div class="mt-4 mb-4 rounded-xl bg-red-100 border border-red-300 text-red-800 p-3">
                <ul class="list-disc list-inside text-sm">
                    @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
        @endif

        <div class="bg-white rounded-xl shadow p-6 mt-4">
            <p class="text-sm text-gray-500 mb-6">
                <strong>{{ $user->name }}</strong><br>{{ $user->email }}
            </p>

            <form method="POST" action="{{ route('wms.usuario-almacenes.update', $user) }}">
                @csrf
                @method('PUT')

                <h3 class="font-bold text-gray-700 mb-2 uppercase text-sm tracking-wide">Almacenes operativos</h3>
                <p class="text-xs text-gray-500 mb-4">
                    Marca los almacenes donde el usuario puede operar. No se asignan subalmacenes 111/112 en esta etapa.
                </p>

                <div class="space-y-3">
                    @foreach ($almacenes as $almacen)
                        @php
                            $asignacion = $asignaciones->get($almacen->id);
                            $activo = $asignacion && $asignacion->activo;
                            $esPrincipal = $asignacion && $asignacion->es_principal;
                        @endphp
                        <label class="flex items-center gap-3 border border-gray-200 rounded-xl p-4 hover:bg-gray-50">
                            <input type="checkbox"
                                name="almacenes[]"
                                value="{{ $almacen->id }}"
                                data-almacen="{{ $almacen->id }}"
                                class="almacen-checkbox rounded border-gray-300 text-indigo-600"
                                {{ $activo ? 'checked' : '' }}>
                            <div class="flex-1">
                                <div class="font-semibold text-gray-800">{{ $almacen->codigo }} · {{ $almacen->nombre }}</div>
                                <div class="text-xs text-gray-500">Prefijo documentos: {{ $almacen->prefijo_documento ?: '—' }}</div>
                            </div>
                            <label class="flex items-center gap-2 text-sm text-gray-600">
                                <input type="radio"
                                    name="principal"
                                    value="{{ $almacen->id }}"
                                    data-principal="{{ $almacen->id }}"
                                    class="principal-radio"
                                    {{ $esPrincipal ? 'checked' : '' }}>
                                Principal
                            </label>
                        </label>
                    @endforeach
                </div>

                <div class="mt-5 bg-blue-50 border border-blue-200 rounded-xl p-3 text-sm text-blue-800">
                    El almacén <strong>principal</strong> será el contexto operativo utilizado por WMS.
                    Si el usuario no tiene almacén activo, no podrá operar procesos WMS que requieran contexto.
                </div>

                <div class="mt-6 flex gap-3">
                    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-3 rounded-lg font-semibold">
                        Guardar asignación
                    </button>
                    <a href="{{ route('wms.usuario-almacenes.index') }}" class="text-gray-500 px-4 py-3">Cancelar</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const checks = document.querySelectorAll('.almacen-checkbox');
            const radios = document.querySelectorAll('.principal-radio');

            function sincronizar() {
                radios.forEach(function (radio) {
                    const check = document.querySelector('.almacen-checkbox[data-almacen="' + radio.dataset.principal + '"]');
                    radio.disabled = !check || !check.checked;
                    if (radio.disabled) radio.checked = false;
                });

                const activos = Array.from(radios).filter(r => !r.disabled);
                if (activos.length === 1) {
                    activos[0].checked = true;
                }
            }

            checks.forEach(function (check) {
                check.addEventListener('change', sincronizar);
            });

            sincronizar();
        });
    </script>
</x-app-layout>
