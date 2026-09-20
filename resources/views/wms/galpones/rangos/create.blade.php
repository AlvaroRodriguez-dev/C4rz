<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Agregar tramo</h2>
            <p class="text-sm text-gray-500">
                {{ $galpon->almacen->codigo }} · {{ $galpon->codigo }} - {{ $galpon->nombre }}
            </p>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-2xl mx-auto px-3">
            @if ($errors->any())
                <div class="mb-4 rounded-xl bg-red-100 border border-red-300 text-red-800 p-3">
                    <ul class="list-disc list-inside text-sm">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="bg-white shadow rounded-xl p-5">
                <div class="mb-5 rounded-xl bg-blue-50 border border-blue-200 p-4 text-sm text-blue-800">
                    <strong>¿Qué es un tramo?</strong>
                    Es un intervalo de numeración que se agrega al galpón.
                    Si G2 ya tiene 21–30, puedes agregar posteriormente 41–42 sin modificar el tramo anterior.
                </div>

                <form action="{{ route('wms.galpones.rangos.store', $galpon) }}"
                      method="POST"
                      id="rango-form"
                      class="space-y-5">
                    @csrf

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="desde" class="block text-sm font-semibold text-gray-700 mb-1">
                                Posición inicial
                            </label>
                            <input id="desde" name="desde" type="number" min="1" max="4294967295"
                                   value="{{ old('desde') }}"
                                   class="w-full rounded-lg border-gray-300 text-lg"
                                   required>
                        </div>
                        <div>
                            <label for="hasta" class="block text-sm font-semibold text-gray-700 mb-1">
                                Posición final
                            </label>
                            <input id="hasta" name="hasta" type="number" min="1" max="4294967295"
                                   value="{{ old('hasta') }}"
                                   class="w-full rounded-lg border-gray-300 text-lg"
                                   required>
                        </div>
                    </div>

                    <div id="resumen" class="rounded-xl bg-gray-50 border border-gray-200 p-4">
                        <div class="text-xs uppercase tracking-wide text-gray-500">Resumen</div>
                        <div id="cantidad" class="mt-1 text-lg font-bold text-gray-800">
                            Ingrese el inicio y el final del tramo.
                        </div>
                    </div>

                    <div class="rounded-xl bg-yellow-50 border border-yellow-200 p-4 text-sm text-yellow-800">
                        El sistema rechazará tramos que se superpongan con otros tramos o que utilicen
                        posiciones que ya pertenecen a otro galpón del mismo almacén.
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <a href="{{ route('wms.galpones.rangos.index', $galpon) }}"
                           class="px-4 py-2 rounded-lg border border-gray-300 text-gray-700">
                            Cancelar
                        </a>
                        <button type="submit"
                                class="px-5 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-semibold">
                            Generar posiciones
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        (() => {
            const desde = document.getElementById('desde');
            const hasta = document.getElementById('hasta');
            const cantidad = document.getElementById('cantidad');
            const form = document.getElementById('rango-form');

            function actualizar() {
                const inicio = Number(desde.value);
                const fin = Number(hasta.value);

                if (!inicio || !fin) {
                    cantidad.textContent = 'Ingrese el inicio y el final del tramo.';
                    return;
                }

                if (inicio > fin) {
                    cantidad.textContent = 'El inicio no puede ser mayor que el final.';
                    return;
                }

                const total = fin - inicio + 1;
                cantidad.textContent =
                    total.toLocaleString('es-BO') + ' posición(es): ' + inicio + ' → ' + fin;
            }

            desde.addEventListener('input', actualizar);
            hasta.addEventListener('input', actualizar);

            form.addEventListener('submit', (event) => {
                const inicio = Number(desde.value);
                const fin = Number(hasta.value);

                if (!inicio || !fin || inicio > fin) {
                    event.preventDefault();
                    return;
                }

                const total = fin - inicio + 1;

                if (total > 10000) {
                    event.preventDefault();
                    alert('Un solo tramo no puede generar más de 10.000 posiciones.');
                    return;
                }

                const mensaje =
                    'Se generarán ' + total.toLocaleString('es-BO') +
                    ' posición(es), desde ' + inicio + ' hasta ' + fin + '.\n\n' +
                    'Las posiciones existentes no se modificarán. ¿Desea continuar?';

                if (!confirm(mensaje)) {
                    event.preventDefault();
                }
            });

            actualizar();
        })();
    </script>
</x-app-layout>
