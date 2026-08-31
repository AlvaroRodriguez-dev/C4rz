<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Verificar estado BD - Resultados
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">

                <div class="mb-4 flex justify-between items-center">
                    <div>
                        <p><strong>Servidor:</strong> {{ $servidor_ip }}</p>
                        <p><strong>Filtro:</strong> {{ $filtro }}</p>
                    </div>
                    <a href="{{ route('verificar-bd.index') }}"
                        class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded">
                        Nueva búsqueda
                    </a>
                </div>

                @if (count($bases) > 0)
                    <table class="w-full border-collapse border border-gray-300">
                        <thead>
                            <tr class="bg-gray-100">
                                <th class="border border-gray-300 px-3 py-2">NRO</th>
                                <th class="border border-gray-300 px-3 py-2">BASE DE DATOS</th>
                                <th class="border border-gray-300 px-3 py-2">GLOSA</th>
                                <th class="border border-gray-300 px-3 py-2">INGRESOS</th>
                                <th class="border border-gray-300 px-3 py-2">ENTREGAS</th>
                                <th class="border border-gray-300 px-3 py-2">TRASPASOS</th>
                                <th class="border border-gray-300 px-3 py-2">VENTAS</th>
                                <th class="border border-gray-300 px-3 py-2">COBRANZAS</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($bases as $index => $base)
                                <tr class="hover:bg-gray-50">
                                    <td class="border border-gray-300 px-3 py-2 text-center">{{ $index + 1 }}</td>
                                    <td class="border border-gray-300 px-3 py-2 font-medium">{{ $base }}</td>

                                    {{-- Columna GLOSA --}}
                                    <td class="border border-gray-300 px-3 py-2 text-center"
                                        id="glosa-cell-{{ $index }}">
                                        @if (str_starts_with(strtoupper($base), 'SISCON'))
                                            <button type="button"
                                                class="btn-verificar-glosa bg-blue-500 hover:bg-blue-600 text-black text-sm font-bold py-1 px-3 rounded"
                                                data-base="{{ $base }}" data-index="{{ $index }}">
                                                VERIFICAR
                                            </button>
                                        @else
                                            &nbsp; N/A
                                        @endif
                                    </td>

                                    {{-- Columna INGRESOS --}}
                                    <td class="border border-gray-300 px-3 py-2 text-center"
                                        id="ingresos-cell-{{ $index }}">
                                        @if (str_starts_with(strtoupper($base), 'SISINV'))
                                            <button type="button"
                                                class="btn-verificar-movimiento bg-blue-500 hover:bg-blue-600 text-black text-sm font-bold py-1 px-3 rounded"
                                                data-base="{{ $base }}" data-index="{{ $index }}"
                                                data-tabla="recep" data-campo-fecha="RFECHA"
                                                data-campo-anulada="RANULADA"
                                                data-cell="ingresos-cell-{{ $index }}">
                                                VERIFICAR
                                            </button>
                                        @else
                                            &nbsp;
                                        @endif
                                    </td>

                                    {{-- Columna ENTREGAS --}}
                                    <td class="border border-gray-300 px-3 py-2 text-center"
                                        id="entregas-cell-{{ $index }}">
                                        @if (str_starts_with(strtoupper($base), 'SISINV'))
                                            <button type="button"
                                                class="btn-verificar-movimiento bg-blue-500 hover:bg-blue-600 text-black text-sm font-bold py-1 px-3 rounded"
                                                data-base="{{ $base }}" data-index="{{ $index }}"
                                                data-tabla="entregas" data-campo-fecha="EFECHA"
                                                data-campo-anulada="EANULADA"
                                                data-cell="entregas-cell-{{ $index }}">
                                                VERIFICAR
                                            </button>
                                        @else
                                            &nbsp;
                                        @endif
                                    </td>

                                    {{-- Columna TRASPASOS --}}
                                    <td class="border border-gray-300 px-3 py-2 text-center"
                                        id="traspasos-cell-{{ $index }}">
                                        @if (str_starts_with(strtoupper($base), 'SISINV'))
                                            <button type="button"
                                                class="btn-verificar-movimiento bg-blue-500 hover:bg-blue-600 text-black text-sm font-bold py-1 px-3 rounded"
                                                data-base="{{ $base }}" data-index="{{ $index }}"
                                                data-tabla="trasp" data-campo-fecha="TFECHA"
                                                data-campo-anulada="TANULADA"
                                                data-cell="traspasos-cell-{{ $index }}">
                                                VERIFICAR
                                            </button>
                                        @else
                                            &nbsp;
                                        @endif
                                    </td>

                                    {{-- Columna VENTAS --}}
                                    <td class="border border-gray-300 px-3 py-2 text-center"
                                        id="ventas-cell-{{ $index }}">
                                        @if (str_starts_with(strtoupper($base), 'SISINV'))
                                            <button type="button"
                                                class="btn-verificar-movimiento bg-blue-500 hover:bg-blue-600 text-black text-sm font-bold py-1 px-3 rounded"
                                                data-base="{{ $base }}" data-index="{{ $index }}"
                                                data-tabla="ventas" data-campo-fecha="VFECHA"
                                                data-campo-anulada="VANULADA"
                                                data-cell="ventas-cell-{{ $index }}">
                                                VERIFICAR
                                            </button>
                                        @else
                                            &nbsp;
                                        @endif
                                    </td>

                                    {{-- Columna COBRANZAS --}}
                                    <td class="border border-gray-300 px-3 py-2 text-center"
                                        id="cobranzas-cell-{{ $index }}">
                                        @if (str_starts_with(strtoupper($base), 'SISINV'))
                                            <button type="button"
                                                class="btn-verificar-movimiento bg-blue-500 hover:bg-blue-600 text-black text-sm font-bold py-1 px-3 rounded"
                                                data-base="{{ $base }}" data-index="{{ $index }}"
                                                data-tabla="cobranza" data-campo-fecha="FECHA"
                                                data-campo-anulada="ANULADA"
                                                data-cell="cobranzas-cell-{{ $index }}">
                                                VERIFICAR
                                            </button>
                                        @else
                                            &nbsp;
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <p class="text-gray-500">No se encontraron bases de datos con ese filtro.</p>
                @endif

            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            function renderResultado(cell, data, base, servidorIp) {
                if (data.ok) {
                    cell.innerHTML = '<span class="text-green-600 font-bold">' + data.mensaje + '</span>' +
                        '<br><button onclick="this.closest(\'td\').querySelector(\'.btn-refrescar\').click()" ' +
                        'class="btn-refrescar mt-1 text-xs text-indigo-500 underline hover:text-indigo-700">↻ actualizar</button>';
                    return;
                }

                if (data.mensaje) {
                    cell.innerHTML = '<span class="text-red-600 font-bold">' + data.mensaje + '</span>' +
                        '<br><button class="mt-1 text-xs text-indigo-500 underline hover:text-indigo-700" ' +
                        'onclick="this.parentElement._refreshBtn && this.parentElement._refreshBtn.click()">↻ actualizar</button>';
                    return;
                }

                let html = '';
                data.items.forEach(function(item, idx) {
                    const url = '{{ route('verificar-bd.detalle') }}' +
                        '?servidor_ip=' + encodeURIComponent(servidorIp) +
                        '&base=' + encodeURIComponent(base) +
                        '&tipo=' + encodeURIComponent(data.tipo) +
                        '&anio=' + item.anio +
                        '&mes=' + item.mes;

                    if (idx > 0) html += '<br>';
                    html += '<a href="' + url + '" target="_blank" ' +
                        'class="text-red-600 font-bold underline hover:text-red-800">' +
                        item.texto + '</a>';
                });

                // Botón de actualizar verificación al final
                html +=
                    '<br><span class="btn-refrescar-trigger text-xs text-indigo-500 underline cursor-pointer hover:text-indigo-700" ' +
                    'data-base="' + base + '" data-tipo="' + data.tipo + '">↻ actualizar verificación</span>';

                cell.innerHTML = html;

                // Conectar el enlace "actualizar verificación" al botón original que disparó la verificación
                cell.querySelector('.btn-refrescar-trigger').addEventListener('click', function() {
                    // Buscar el botón original en la celda (ya fue reemplazado, así que re-disparamos la consulta)
                    cell._refreshCallback && cell._refreshCallback();
                });
            }



            document.addEventListener('DOMContentLoaded', function() {
                document.querySelectorAll('.btn-verificar-glosa').forEach(function(btn) {
                    btn.addEventListener('click', function() {
                        const base = this.dataset.base;
                        const index = this.dataset.index;
                        const cell = document.getElementById('glosa-cell-' + index);

                        function ejecutarVerificacion() {
                            cell.innerHTML =
                                '<span class="text-gray-400 text-sm">Verificando...</span>';
                            fetch('{{ route('verificar-bd.glosa') }}', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                        'Accept': 'application/json',
                                    },
                                    body: JSON.stringify({
                                        servidor_ip: '{{ $servidor_ip }}',
                                        base: base
                                    }),
                                })
                                .then(r => r.json())
                                .then(data => renderResultado(cell, data, base, '{{ $servidor_ip }}',
                                    ejecutarVerificacion))
                                .catch(() => {
                                    cell.innerHTML =
                                        '<span class="text-red-600 font-bold">Error de conexión</span>';
                                });
                        }

                        ejecutarVerificacion();
                    });
                });
            });

            document.querySelectorAll('.btn-verificar-movimiento').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    const base = this.dataset.base;
                    const tabla = this.dataset.tabla;
                    const campoFecha = this.dataset.campoFecha;
                    const campoAnulada = this.dataset.campoAnulada;
                    const cellId = this.dataset.cell;
                    const cell = document.getElementById(cellId);

                    function ejecutarVerificacion() {
                        cell.innerHTML = '<span class="text-gray-400 text-sm">Verificando...</span>';
                        fetch('{{ route('verificar-bd.movimiento') }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                    'Accept': 'application/json',
                                },
                                body: JSON.stringify({
                                    servidor_ip: '{{ $servidor_ip }}',
                                    base: base,
                                    tabla: tabla,
                                    campo_fecha: campoFecha,
                                    campo_anulada: campoAnulada,
                                }),
                            })
                            .then(r => r.json())
                            .then(data => renderResultado(cell, data, base, '{{ $servidor_ip }}',
                                ejecutarVerificacion))
                            .catch(() => {
                                cell.innerHTML =
                                    '<span class="text-red-600 font-bold">Error de conexión</span>';
                            });
                    }

                    ejecutarVerificacion();
                });
            });

            function renderResultado(cell, data, base, servidorIp, ejecutarVerificacion) {
                if (data.ok) {
                    cell.innerHTML =
                        '<span class="text-green-600 font-bold">' + data.mensaje + '</span>' +
                        '<br><button class="mt-1 text-xs text-indigo-500 underline hover:text-indigo-700">↻ actualizar</button>';
                    cell.querySelector('button').addEventListener('click', ejecutarVerificacion);
                    return;
                }

                if (data.mensaje) {
                    // Tabla vacía o error sin items
                    cell.innerHTML =
                        '<span class="text-red-600 font-bold">' + data.mensaje + '</span>' +
                        '<br><button class="mt-1 text-xs text-indigo-500 underline hover:text-indigo-700">↻ actualizar</button>';
                    cell.querySelector('button').addEventListener('click', ejecutarVerificacion);
                    return;
                }

                // Items con enlaces por mes
                let html = '';
                data.items.forEach(function(item, idx) {
                    const url = '{{ route('verificar-bd.detalle') }}' +
                        '?servidor_ip=' + encodeURIComponent(servidorIp) +
                        '&base=' + encodeURIComponent(base) +
                        '&tipo=' + encodeURIComponent(data.tipo) +
                        '&anio=' + item.anio +
                        '&mes=' + item.mes;

                    if (idx > 0) html += '<br>';
                    html += '<a href="' + url + '" target="_blank" ' +
                        'class="text-red-600 font-bold underline hover:text-red-800">' +
                        item.texto + '</a>';
                });

                html +=
                    '<br><button class="mt-1 text-xs text-indigo-500 underline hover:text-indigo-700">↻ actualizar</button>';
                cell.innerHTML = html;
                cell.querySelector('button').addEventListener('click', ejecutarVerificacion);
            }
        </script>
    @endpush

</x-app-layout>
