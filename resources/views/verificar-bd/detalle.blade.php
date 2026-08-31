<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Detalle {{ $titulo }} — {{ $base }} — {{ $nombreMes }} {{ $anio }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">

                {{-- Mensajes de éxito / error --}}
                @if (session('success'))
                    <div class="mb-4 p-3 bg-green-100 text-green-700 rounded">
                        {{ session('success') }}
                    </div>
                @endif
                @if (session('error'))
                    <div class="mb-4 p-3 bg-red-100 text-red-700 rounded">
                        {{ session('error') }}
                    </div>
                @endif
                @if ($error)
                    <div class="mb-4 p-3 bg-red-100 text-red-700 rounded">
                        Error: {{ $error }}
                    </div>
                @endif

                {{-- Cabecera con info y botón actualizar --}}
                {{-- Cabecera con info y botones --}}
                <div class="mb-4 flex justify-between items-start">
                    <p class="text-gray-600">
                        Registros pendientes en <strong>{{ $base }}</strong>
                        — <strong>{{ $nombreMes }} {{ $anio }}</strong>:
                        <strong>{{ count($registros) }}</strong>
                    </p>

                    @if (count($registros) > 0)
                        <form method="POST" action="{{ route('verificar-bd.actualizar-todos') }}"
                            onsubmit="return confirm('¿Confirma {{ $tipo === 'glosa' ? 'MAYORIZAR' : 'BLOQUEAR' }} TODOS los {{ count($registros) }} registros pendientes de {{ $nombreMes }} {{ $anio }}?')">
                            @csrf
                            <input type="hidden" name="servidor_ip" value="{{ $servidor_ip }}">
                            <input type="hidden" name="base" value="{{ $base }}">
                            <input type="hidden" name="tipo" value="{{ $tipo }}">
                            <input type="hidden" name="anio" value="{{ $anio }}">
                            <input type="hidden" name="mes" value="{{ $mes }}">
                            <button type="submit"
                                class="bg-green-600 hover:bg-green-700 text-black font-bold py-2 px-4 rounded">
                                ✓ {{ $tipo === 'glosa' ? 'MAYORIZAR TODOS' : 'BLOQUEAR TODOS' }}
                            </button>
                        </form>
                    @endif
                </div>

                @if (count($registros) > 0)
                    <table class="w-full border-collapse border border-gray-300 text-sm">
                        <thead>
                            <tr class="bg-gray-100">
                                @foreach ($columnas as $col)
                                    <th class="border border-gray-300 px-3 py-2">{{ $col }}</th>
                                @endforeach
                                <th class="border border-gray-300 px-3 py-2">ACCIÓN</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($registros as $registro)
                                <tr class="hover:bg-gray-50">
                                    @foreach ($columnas as $col)
                                        <td class="border border-gray-300 px-3 py-2">
                                            {{ $registro->$col }}
                                        </td>
                                    @endforeach
                                    <td class="border border-gray-300 px-3 py-2 text-center">
                                        <form method="POST" action="{{ route('verificar-bd.actualizar') }}"
                                            onsubmit="return confirm('¿Confirma {{ $tipo === 'glosa' ? 'MAYORIZAR' : 'BLOQUEAR' }} este registro?')">
                                            @csrf
                                            <input type="hidden" name="servidor_ip" value="{{ $servidor_ip }}">
                                            <input type="hidden" name="base" value="{{ $base }}">
                                            <input type="hidden" name="tipo" value="{{ $tipo }}">
                                            <input type="hidden" name="campo_id" value="{{ $campoId }}">
                                            <input type="hidden" name="valor_id" value="{{ $registro->$campoId }}">
                                            <input type="hidden" name="anio" value="{{ $anio }}">
                                            <input type="hidden" name="mes" value="{{ $mes }}">
                                            <button type="submit"
                                                class="bg-green-500 hover:bg-green-600 text-black text-xs font-bold py-1 px-2 rounded">
                                                {{ $tipo === 'glosa' ? 'MAYORIZAR' : 'BLOQUEAR' }}
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <p class="text-green-600 font-bold">
                        No hay registros pendientes para este periodo.
                    </p>
                @endif

            </div>
        </div>
    </div>
</x-app-layout>
