<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            📋 Mis Marcajes
        </h2>
    </x-slot>

    <div class="py-8 max-w-4xl mx-auto px-4">
        {{-- Filtro mes/año --}}
        <form method="GET" class="bg-white rounded-xl shadow p-4 mb-4 flex gap-4 items-end">
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Mes</label>
                <select name="mes"
                        class="border-gray-300 rounded-lg text-sm focus:ring-indigo-500">
                    @foreach([1=>'Enero',2=>'Febrero',3=>'Marzo',4=>'Abril',5=>'Mayo',
                              6=>'Junio',7=>'Julio',8=>'Agosto',9=>'Septiembre',
                              10=>'Octubre',11=>'Noviembre',12=>'Diciembre'] as $n => $nombre)
                        <option value="{{ $n }}" {{ request('mes', date('m')) == $n ? 'selected' : '' }}>
                            {{ $nombre }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Año</label>
                <select name="anio"
                        class="border-gray-300 rounded-lg text-sm focus:ring-indigo-500">
                    @foreach(range(date('Y'), 2024) as $y)
                        <option value="{{ $y }}" {{ request('anio', date('Y')) == $y ? 'selected' : '' }}>
                            {{ $y }}
                        </option>
                    @endforeach
                </select>
            </div>
            <button type="submit"
                    class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm
                           font-semibold py-2 px-4 rounded-lg">
                Filtrar
            </button>
        </form>

        {{-- Tabla --}}
        <div class="bg-white rounded-xl shadow overflow-hidden">
            <table class="min-w-full text-sm">
                <thead class="bg-indigo-600 text-white">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold">Fecha y Hora</th>
                        <th class="px-4 py-3 text-center font-semibold">Tipo</th>
                        <th class="px-4 py-3 text-left font-semibold">Ubicación</th>
                        <th class="px-4 py-3 text-center font-semibold">Foto</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($marcajes as $m)
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-4 py-2 whitespace-nowrap">
                                <p class="font-medium text-gray-800">
                                    {{ \Carbon\Carbon::parse($m->fecha_servidor)->format('d/m/Y') }}
                                </p>
                                <p class="text-xs text-gray-400 font-mono">
                                    {{ \Carbon\Carbon::parse($m->fecha_servidor)->format('H:i:s') }}
                                </p>
                            </td>
                            <td class="px-4 py-2 text-center">
                                <span class="px-3 py-1 rounded-full text-xs font-bold
                                    {{ $m->tipo === 'INGRESO'
                                       ? 'bg-green-100 text-green-700'
                                       : 'bg-red-100 text-red-700' }}">
                                    {{ $m->tipo }}
                                </span>
                            </td>
                            <td class="px-4 py-2">
                                @if($m->direccion)
                                    <p class="text-xs text-gray-600 max-w-xs truncate"
                                       title="{{ $m->direccion }}">
                                        📍 {{ $m->direccion }}
                                    </p>
                                @elseif($m->latitud)
                                    <p class="text-xs text-gray-400 font-mono">
                                        {{ $m->latitud }}, {{ $m->longitud }}
                                    </p>
                                @else
                                    <span class="text-gray-300 text-xs">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-2 text-center">
                                <img src="{{ asset('storage/' . $m->foto) }}"
                                    onerror="this.src='https://ui-avatars.com/api/?name=NA&size=40'"
                                     class="w-10 h-10 rounded-full object-cover
                                            border-2 border-gray-200 mx-auto cursor-pointer
                                            hover:scale-150 transition-transform"
                                     title="Ver foto">
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-10 text-center text-gray-400">
                                Sin registros en este período.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>