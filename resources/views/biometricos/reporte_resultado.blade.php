<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                📊 Resultado — {{ $bio->agencia }}: {{ $bio->descripcion }}
            </h2>
            <a href="{{ route('biometricos.reporte') }}"
               class="text-sm bg-gray-200 hover:bg-gray-300 text-gray-700 py-1 px-4 rounded-lg">
                ← Nueva consulta
            </a>
        </div>
    </x-slot>

    <div class="py-8 max-w-6xl mx-auto px-4">
        <div class="bg-white rounded-xl shadow overflow-hidden">

            {{-- Encabezado del período --}}
            <div class="px-6 py-3 bg-gray-50 border-b text-sm text-gray-600 flex flex-wrap gap-6">
                <span>📅 Período:
                    <strong>{{ $request->fecha_ini }}</strong> al
                    <strong>{{ $request->fecha_fin }}</strong>
                </span>
                <span>📋 Fechas con registros: <strong>{{ count($agrupado) }}</strong></span>
                @if($request->fuente ?? 'todos' !== 'todos')
                    <span>🔍 Fuente:
                        <strong>{{ $request->fuente === 'usb' ? '💾 Solo USB' : '🌐 Solo Online' }}</strong>
                    </span>
                @else
                    <span>🔍 Fuente: <strong>🌐 Online + 💾 USB</strong></span>
                @endif
            </div>

            @if(empty($agrupado))
                <div class="p-10 text-center text-gray-400">
                    <svg class="w-12 h-12 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                              d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="font-medium">No se encontraron registros en el período seleccionado.</p>
                </div>
            @else
                <table class="min-w-full text-sm">
                    <thead class="bg-indigo-600 text-white">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold">Fecha</th>
                            <th class="px-4 py-3 text-left font-semibold">ID Usuario</th>
                            <th class="px-4 py-3 text-left font-semibold">Nombre</th>
                            <th class="px-4 py-3 text-left font-semibold">Marcajes</th>
                            <th class="px-4 py-3 text-center font-semibold">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @php $fila = 0; @endphp
                        @foreach($agrupado as $fecha => $porUsuario)
                            @foreach($porUsuario as $userId => $marcajes)
                                <tr class="{{ $fila % 2 === 0 ? 'bg-white' : 'bg-gray-50' }} hover:bg-indigo-50 transition">

                                    <td class="px-4 py-2 font-medium text-gray-800 whitespace-nowrap">
                                        {{ \Carbon\Carbon::parse($fecha)->translatedFormat('D d/m/Y') }}
                                    </td>

                                    <td class="px-4 py-2 text-gray-600">{{ $userId }}</td>

                                    <td class="px-4 py-2 text-gray-800 font-medium">
                                        {{ $usuarios[$userId] ?? '—' }}
                                    </td>

                                    {{-- Marcajes con badge de color + fuente --}}
                                    <td class="px-4 py-2">
                                        <div class="flex flex-wrap gap-1">
                                            @foreach($marcajes as $marcaje)
                                                @php
                                                    
                                                    $colores = [
                                                        'blue'   => 'bg-blue-100 text-blue-700 ring-blue-200',
                                                        'green'  => 'bg-green-100 text-green-700 ring-green-200',
                                                        'purple' => 'bg-purple-100 text-purple-700 ring-purple-200',
                                                        'yellow' => 'bg-yellow-100 text-yellow-700 ring-yellow-200',
                                                        'red'    => 'bg-red-100 text-red-700 ring-red-200',
                                                        'gray'   => 'bg-gray-100 text-gray-700 ring-gray-200',
                                                    ];
                                                    $clase  = $colores[$marcaje['color']] ?? $colores['gray'];
                                                    $fuente = ($marcaje['fuente'] ?? 'online') === 'usb'
                                                              ? '💾' : '🌐';
                                                @endphp
                                                <span class="{{ $clase }} ring-1 text-xs font-mono px-2 py-1 rounded-md inline-flex items-center gap-1 whitespace-nowrap">
                                                    {{ $fuente }}
                                                    🕐 {{ $marcaje['hora'] }}
                                                    <span class="opacity-75 font-sans">[{{ $marcaje['tipo'] }}]</span>
                                                    <span class="opacity-60 font-sans">· {{ $marcaje['verificacion'] }}</span>
                                                </span>
                                            @endforeach
                                        </div>
                                    </td>

                                    <td class="px-4 py-2 text-center">
                                        <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-gray-200 text-gray-700 text-xs font-bold">
                                            {{ count($marcajes) }}
                                        </span>
                                    </td>
                                </tr>
                                @php $fila++; @endphp
                            @endforeach
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        <div class="mt-4 flex gap-3">
            <a href="{{ route('biometricos.reporte') }}"
               class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold py-2 px-5 rounded-lg">
                Nueva consulta
            </a>
            <button onclick="window.print()"
                    class="bg-gray-200 hover:bg-gray-300 text-gray-700 text-sm font-semibold py-2 px-5 rounded-lg">
                🖨 Imprimir
            </button>
        </div>
    </div>
</x-app-layout>