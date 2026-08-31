<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Resultado de Migración
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">

                {{-- Info del proceso --}}
                <div class="mb-6 p-4 bg-indigo-50 border border-indigo-200 rounded-md text-sm text-indigo-800">
                    <div class="grid grid-cols-2 gap-2">
                        <div><span class="font-semibold">Origen:</span> {{ $ipOrigen }} / {{ $bdOrigen }}</div>
                        <div><span class="font-semibold">Destino:</span> {{ $ipDestino }} / {{ $bdDestino }}</div>
                        <div><span class="font-semibold">Período:</span>
                            {{ \Carbon\Carbon::parse($fechaInicial)->format('d/m/Y') }}
                            al
                            {{ \Carbon\Carbon::parse($fechaFinal)->format('d/m/Y') }}
                        </div>
                        <div>
                            <span class="font-semibold">CMP IN:</span>
                            @if($incluirCmpIn)
                                <span class="text-green-700 font-semibold">Incluidos</span>
                            @else
                                <span class="text-red-700 font-semibold">Excluidos</span>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Tabla resumen --}}
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Tabla
                                </th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-red-500 uppercase tracking-wider">
                                    Eliminados (Destino)
                                </th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-green-600 uppercase tracking-wider">
                                    Insertados
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @php $totalEliminados = 0; $totalInsertados = 0; @endphp
                            @foreach ($resumen as $fila)
                                @php
                                    $totalEliminados += $fila['eliminados'];
                                    $totalInsertados += $fila['insertados'];
                                @endphp
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 font-medium text-gray-900 uppercase">
                                        {{ $fila['tabla'] }}
                                    </td>
                                    <td class="px-6 py-4 text-center text-red-600 font-semibold">
                                        {{ number_format($fila['eliminados']) }}
                                    </td>
                                    <td class="px-6 py-4 text-center text-green-600 font-semibold">
                                        {{ number_format($fila['insertados']) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-gray-50">
                            <tr>
                                <td class="px-6 py-3 font-bold text-gray-700 uppercase text-sm">Total</td>
                                <td class="px-6 py-3 text-center font-bold text-red-600">
                                    {{ number_format($totalEliminados) }}
                                </td>
                                <td class="px-6 py-3 text-center font-bold text-green-600">
                                    {{ number_format($totalInsertados) }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="mt-6 flex justify-end">
                    <a href="{{ route('migrar.contables.index') }}"
                       class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-6 rounded-md transition">
                        Nueva Migración
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>