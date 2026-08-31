<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            📊 Reporte de Asistencia APP
        </h2>
    </x-slot>

    <div class="py-8 max-w-6xl mx-auto px-4">

        {{-- Formulario de filtros --}}
        <form method="POST" action="{{ route('asistencia.reporte') }}"
              class="bg-white rounded-xl shadow p-5 mb-5">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">

                {{-- Usuario --}}
                <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-gray-500 mb-1">
                        Empleado
                    </label>
                    <select name="user_id"
                            class="w-full border-gray-300 rounded-lg text-sm
                                   focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">— Todos —</option>
                        @foreach($usuarios as $u)
                            <option value="{{ $u->id }}"
                                    {{ ($filtros['user_id'] ?? '') == $u->id ? 'selected' : '' }}>
                                {{ $u->name }} — CI: {{ $u->license }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Fecha inicio --}}
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">
                        Fecha inicio
                    </label>
                    <input type="date" name="fecha_ini"
                           value="{{ $filtros['fecha_ini'] ?? date('Y-m-01') }}"
                           class="w-full border-gray-300 rounded-lg text-sm
                                  focus:ring-indigo-500 focus:border-indigo-500">
                </div>

                {{-- Fecha fin --}}
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">
                        Fecha fin
                    </label>
                    <input type="date" name="fecha_fin"
                           value="{{ $filtros['fecha_fin'] ?? date('Y-m-d') }}"
                           class="w-full border-gray-300 rounded-lg text-sm
                                  focus:ring-indigo-500 focus:border-indigo-500">
                </div>
            </div>

            <div class="mt-4 flex gap-3">
                <button type="submit"
                        class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm
                               font-semibold py-2 px-5 rounded-lg">
                    Generar Reporte
                </button>
                <a href="{{ route('asistencia.reporte') }}"
                   class="bg-gray-200 hover:bg-gray-300 text-gray-700 text-sm
                          font-semibold py-2 px-5 rounded-lg">
                    Limpiar
                </a>
            </div>
        </form>

        {{-- Resultados --}}
        @if($marcajes->count() > 0)
        <div class="bg-white rounded-xl shadow overflow-hidden">

            {{-- Resumen --}}
            <div class="px-5 py-3 bg-gray-50 border-b text-sm text-gray-600 flex gap-6">
                <span>📋 Total registros: <strong>{{ $marcajes->count() }}</strong></span>
                <span>✅ Ingresos:
                    <strong>{{ $marcajes->where('tipo', 'INGRESO')->count() }}</strong>
                </span>
                <span>🚪 Salidas:
                    <strong>{{ $marcajes->where('tipo', 'SALIDA')->count() }}</strong>
                </span>
            </div>

            <table class="min-w-full text-sm">
                <thead class="bg-indigo-600 text-white">
                    <tr>
                        <th class="px-3 py-3 text-left font-semibold">Empleado</th>
                        <th class="px-3 py-3 text-left font-semibold">Fecha Servidor</th>
                        <th class="px-3 py-3 text-left font-semibold">Fecha Cliente</th>
                        <th class="px-3 py-3 text-center font-semibold">Tipo</th>
                        <th class="px-3 py-3 text-left font-semibold">Ubicación</th>
                        <th class="px-3 py-3 text-center font-semibold">Foto</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($marcajes as $i => $m)
                        <tr class="{{ $i % 2 === 0 ? 'bg-white' : 'bg-gray-50' }}
                                   hover:bg-indigo-50 transition">

                            {{-- Empleado --}}
                            <td class="px-3 py-2">
                                <p class="font-medium text-gray-800">{{ $m['nombre'] }}</p>
                                <p class="text-xs text-gray-400">CI: {{ $m['license'] }}</p>
                            </td>

                            {{-- Fecha servidor --}}
                            <td class="px-3 py-2 whitespace-nowrap">
                                <p class="font-medium text-gray-800">
                                    {{ \Carbon\Carbon::parse($m['fecha_servidor'])->format('d/m/Y') }}
                                </p>
                                <p class="text-xs font-mono text-gray-500">
                                    {{ \Carbon\Carbon::parse($m['fecha_servidor'])->format('H:i:s') }}
                                </p>
                            </td>

                            {{-- Fecha cliente --}}
                            <td class="px-3 py-2 whitespace-nowrap">
                                @if($m['fecha_cliente'])
                                    <p class="font-medium text-gray-800">
                                        {{ \Carbon\Carbon::parse($m['fecha_cliente'])->format('d/m/Y') }}
                                    </p>
                                    <p class="text-xs font-mono text-gray-500">
                                        {{ \Carbon\Carbon::parse($m['fecha_cliente'])->format('H:i:s') }}
                                    </p>
                                @else
                                    <span class="text-gray-300 text-xs">—</span>
                                @endif
                            </td>

                            {{-- Tipo --}}
                            <td class="px-3 py-2 text-center">
                                <span class="px-3 py-1 rounded-full text-xs font-bold
                                    {{ $m['tipo'] === 'INGRESO'
                                       ? 'bg-green-100 text-green-700'
                                       : 'bg-red-100 text-red-700' }}">
                                    {{ $m['tipo'] }}
                                </span>
                            </td>

                            {{-- Ubicación con enlace Google Maps --}}
                            <td class="px-3 py-2">
                                @if($m['latitud'] && $m['longitud'])
                                    <a href="https://www.google.com/maps?q={{ $m['latitud'] }},{{ $m['longitud'] }}"
                                       target="_blank"
                                       title="Ver en Google Maps"
                                       class="inline-flex items-center gap-1 text-xs text-indigo-600
                                              hover:text-indigo-800 hover:underline">
                                        📍
                                        @if($m['direccion'])
                                            <span class="max-w-[180px] truncate block"
                                                  title="{{ $m['direccion'] }}">
                                                {{ Str::limit($m['direccion'], 40) }}
                                            </span>
                                        @else
                                            <span class="font-mono">
                                                {{ number_format($m['latitud'], 5) }},
                                                {{ number_format($m['longitud'], 5) }}
                                            </span>
                                        @endif
                                        <svg class="w-3 h-3 flex-shrink-0" fill="none"
                                             stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                  stroke-width="2"
                                                  d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10
                                                     a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                        </svg>
                                    </a>
                                @else
                                    <span class="text-gray-300 text-xs">Sin ubicación</span>
                                @endif
                            </td>

                            {{-- Foto --}}
                            <td class="px-3 py-2 text-center">
                                <img src="{{ $m['foto_url'] }}"
                                     class="w-10 h-10 rounded-full object-cover border-2
                                            border-gray-200 mx-auto cursor-pointer
                                            hover:scale-150 transition-transform duration-200"
                                     onerror="this.src='https://ui-avatars.com/api/?name={{ urlencode($m['tipo']) }}&size=40'"
                                     title="{{ $m['nombre'] }} — {{ $m['tipo'] }}"
                                     onclick="abrirFoto('{{ $m['foto_url'] }}', '{{ $m['nombre'] }}', '{{ $m['tipo'] }}')">
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @elseif(request()->isMethod('post'))
            <div class="bg-white rounded-xl shadow p-10 text-center text-gray-400">
                <p class="font-medium">No se encontraron registros con los filtros seleccionados.</p>
            </div>
        @endif
    </div>

    {{-- Modal foto ampliada --}}
    <div id="modalFoto"
         class="fixed inset-0 z-50 hidden flex items-center justify-center
                bg-black bg-opacity-75 p-4"
         onclick="cerrarFoto()">
        <div class="relative max-w-sm w-full" onclick="event.stopPropagation()">
            <button onclick="cerrarFoto()"
                    class="absolute -top-3 -right-3 bg-white text-gray-700 rounded-full
                           w-8 h-8 flex items-center justify-center shadow font-bold text-lg">
                ×
            </button>
            <img id="modalFotoImg"
                 class="w-full rounded-xl shadow-2xl object-cover">
            <p id="modalFotoNombre"
               class="text-white text-center text-sm mt-3 font-medium"></p>
        </div>
    </div>

    <script>
    function abrirFoto(url, nombre, tipo) {
        document.getElementById('modalFotoImg').src = url;
        document.getElementById('modalFotoNombre').textContent =
            nombre + ' — ' + tipo;
        document.getElementById('modalFoto').classList.remove('hidden');
    }

    function cerrarFoto() {
        document.getElementById('modalFoto').classList.add('hidden');
    }

    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') cerrarFoto();
    });
    </script>
</x-app-layout>