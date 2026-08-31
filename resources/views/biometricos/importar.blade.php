<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            💾 Importar Registros desde USB
        </h2>
    </x-slot>

    <div class="py-8 max-w-3xl mx-auto px-4">
        <div class="bg-white rounded-xl shadow p-6">

            {{-- Info --}}
            <div class="mb-6 bg-blue-50 border border-blue-200 rounded-lg p-4 text-sm text-blue-800">
                <p class="font-semibold mb-1">📋 Instrucciones</p>
                <ul class="list-disc list-inside space-y-1 text-blue-700">
                    <li>Conecta el USB al biométrico y descarga el archivo de registros.</li>
                    <li>El archivo debe ser <strong>.txt</strong> o <strong>.dat</strong>.</li>
                    <li>Los registros con fecha <strong>1970-01-01</strong> serán descartados automáticamente.</li>
                    <li>Los registros duplicados serán omitidos.</li>
                </ul>
            </div>

            {{-- Biométrico --}}
            <div class="mb-5">
                <label class="block text-sm font-medium text-gray-700 mb-1">Biométrico destino</label>
                <select id="biometrico_id" name="biometrico_id"
                    class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">— Seleccione —</option>
                    @foreach ($biometricos as $b)
                        <option value="{{ $b->id }}">
                            {{ $b->agencia }} — {{ $b->descripcion }} ({{ $b->ip }})
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Archivo --}}
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-1">Archivo de registros</label>
                <div id="dropZone"
                    class="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center cursor-pointer hover:border-indigo-400 transition">
                    <svg class="w-10 h-10 mx-auto text-gray-400 mb-2" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                    </svg>
                    <p class="text-gray-500 text-sm">Arrastra el archivo aquí o <span
                            class="text-indigo-600 font-medium">haz clic para seleccionar</span></p>
                    <p id="nombreArchivo" class="mt-2 text-indigo-700 font-medium text-sm hidden"></p>
                    <input type="file" id="archivo" accept=".txt,.dat,.log" class="hidden">
                </div>
            </div>

            {{-- Botón --}}
            <button id="btnImportar"
                class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2.5 px-6 rounded-lg transition">
                💾 Procesar e Importar
            </button>

            {{-- Spinner --}}
            <div id="spinner" class="hidden mt-5 flex items-center gap-2 text-gray-500">
                <svg class="animate-spin h-5 w-5 text-indigo-600" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                        stroke-width="4" />
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z" />
                </svg>
                <span>Procesando archivo, por favor espere...</span>
            </div>

            {{-- Resultado --}}
            <div id="resultado" class="hidden mt-5"></div>
        </div>
    </div>

    <script>
        const token = '{{ csrf_token() }}';

        // ── Drag & drop + click ──────────────────────────────────────────
        const dropZone = document.getElementById('dropZone');
        const inputFile = document.getElementById('archivo');

        dropZone.addEventListener('click', () => inputFile.click());

        dropZone.addEventListener('dragover', e => {
            e.preventDefault();
            dropZone.classList.add('border-indigo-500', 'bg-indigo-50');
        });

        dropZone.addEventListener('dragleave', () => {
            dropZone.classList.remove('border-indigo-500', 'bg-indigo-50');
        });

        dropZone.addEventListener('drop', e => {
            e.preventDefault();
            dropZone.classList.remove('border-indigo-500', 'bg-indigo-50');
            if (e.dataTransfer.files.length) {
                inputFile.files = e.dataTransfer.files;
                mostrarNombre(e.dataTransfer.files[0].name);
            }
        });

        function renderDetalle(titulo, items, color) {
            if (!items || items.length === 0) return '';

            const colores = {
                yellow: 'bg-yellow-50 border-yellow-200 text-yellow-800',
                orange: 'bg-orange-50 border-orange-200 text-orange-800',
                gray: 'bg-gray-50 border-gray-200 text-gray-600',
                red: 'bg-red-50 border-red-200 text-red-800',
            };

            const clase = colores[color] ?? colores.gray;

            // Mostrar máx 50 registros para no saturar la vista
            const muestra = items.slice(0, 50);
            const hayMas = items.length > 50;

            const filas = muestra.map(item => `
        <tr class="border-t border-opacity-30">
            <td class="py-0.5 pr-3 font-mono text-xs opacity-70">
                Línea ${item.linea ?? '—'}
            </td>
            <td class="py-0.5 pr-3 font-mono text-xs">
                ${item.user_id ?? '—'}
            </td>
            <td class="py-0.5 pr-3 font-mono text-xs">
                ${item.timestamp ?? item.contenido ?? '—'}
            </td>
            <td class="py-0.5 text-xs opacity-80">
                ${item.motivo ?? '—'}
            </td>
        </tr>`).join('');

            return `
        <details class="mt-3 border rounded-lg ${clase} overflow-hidden">
            <summary class="px-3 py-2 cursor-pointer font-semibold text-sm
                            flex items-center justify-between select-none">
                <span>${titulo}</span>
                <span class="font-bold">${items.length} registros</span>
            </summary>
            <div class="px-3 pb-3 overflow-x-auto">
                <table class="w-full text-xs mt-2">
                    <thead>
                        <tr class="opacity-60">
                            <th class="text-left pr-3 pb-1">Línea</th>
                            <th class="text-left pr-3 pb-1">Usuario</th>
                            <th class="text-left pr-3 pb-1">Fecha/Hora</th>
                            <th class="text-left pb-1">Motivo</th>
                        </tr>
                    </thead>
                    <tbody>${filas}</tbody>
                </table>
                ${hayMas ? `<p class="text-xs opacity-60 mt-2 italic">
                        ... y ${items.length - 50} registros más no mostrados.</p>` : ''}
            </div>
        </details>`;
        }

        inputFile.addEventListener('change', () => {
            if (inputFile.files.length) mostrarNombre(inputFile.files[0].name);
        });

        function mostrarNombre(nombre) {
            const p = document.getElementById('nombreArchivo');
            p.textContent = '📄 ' + nombre;
            p.classList.remove('hidden');
        }

        // ── Importar ─────────────────────────────────────────────────────
        document.getElementById('btnImportar').addEventListener('click', async () => {
            const bioId = document.getElementById('biometrico_id').value;
            const archivo = inputFile.files[0];

            if (!bioId) {
                alert('Seleccione un biométrico destino.');
                return;
            }
            if (!archivo) {
                alert('Seleccione un archivo para importar.');
                return;
            }

            const formData = new FormData();
            formData.append('_token', token);
            formData.append('biometrico_id', bioId);
            formData.append('archivo', archivo);

            document.getElementById('spinner').classList.remove('hidden');
            document.getElementById('resultado').classList.add('hidden');
            document.getElementById('btnImportar').disabled = true;

            try {
                const resp = await fetch('{{ route('biometricos.importar.procesar') }}', {
                    method: 'POST',
                    body: formData,
                });
                const data = await resp.json();
                const div = document.getElementById('resultado');
                div.classList.remove('hidden');

                if (data.success) {
                    div.innerHTML = `
        <div class="bg-green-50 border border-green-200 rounded-lg p-4 text-sm">
            <p class="font-semibold text-green-800 mb-3">✅ ${data.message}</p>

            {{-- Resumen --}}
            <div class="grid grid-cols-2 md:grid-cols-3 gap-2 mb-4">
                <div class="bg-white rounded p-2 text-center">
                    <p class="text-xs opacity-70">Archivo</p>
                    <p class="font-bold text-xs truncate">${data.archivo}</p>
                </div>
                <div class="bg-white rounded p-2 text-center">
                    <p class="text-xs opacity-70">Líneas procesadas</p>
                    <p class="font-bold">${data.total_lineas}</p>
                </div>
                <div class="bg-white rounded p-2 text-center">
                    <p class="text-xs opacity-70">✅ Insertados</p>
                    <p class="font-bold text-green-600">${data.insertados}</p>
                </div>
                <div class="bg-white rounded p-2 text-center">
                    <p class="text-xs opacity-70">⚠️ Dupl. en archivo</p>
                    <p class="font-bold text-yellow-600">${data.duplicados_archivo}</p>
                </div>
                <div class="bg-white rounded p-2 text-center">
                    <p class="text-xs opacity-70">🔁 Dupl. en BD</p>
                    <p class="font-bold text-orange-500">${data.duplicados_bd}</p>
                </div>
                <div class="bg-white rounded p-2 text-center">
                    <p class="text-xs opacity-70">🗑️ Descartados</p>
                    <p class="font-bold text-gray-400">${data.descartados}</p>
                </div>
                <div class="bg-white rounded p-2 text-center">
                    <p class="text-xs opacity-70">❌ Errores formato</p>
                    <p class="font-bold text-red-500">${data.errores}</p>
                </div>
            </div>

            ${renderDetalle('⚠️ Duplicados dentro del archivo', data.detalle.duplicados_archivo, 'yellow')}
            ${renderDetalle('🔁 Duplicados ya en BD', data.detalle.duplicados_bd, 'orange')}
            ${renderDetalle('🗑️ Registros descartados (1970)', data.detalle.descartados, 'gray')}
            ${renderDetalle('❌ Errores de formato', data.detalle.errores, 'red')}
        </div>`;
                } else {
                    div.innerHTML = `
        <div class="bg-red-50 border border-red-200 rounded-lg p-3 text-sm text-red-800">
            ❌ ${data.message}
        </div>`;
                }
            } catch (e) {
                document.getElementById('resultado').innerHTML = `
                <div class="bg-red-50 border border-red-200 rounded-lg p-4 text-sm text-red-800">
                    ❌ Error de conexión: ${e.message}
                </div>`;
                document.getElementById('resultado').classList.remove('hidden');
            } finally {
                document.getElementById('spinner').classList.add('hidden');
                document.getElementById('btnImportar').disabled = false;
            }
        });
    </script>
</x-app-layout>
