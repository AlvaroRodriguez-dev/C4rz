<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Impresiones SAS — Resultado</h2>
    </x-slot>

    <div class="py-6 px-4">
        <div class="max-w-7xl mx-auto space-y-5">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-sm text-gray-500">Documento consultado</p>
                    <p class="text-xl font-bold text-gray-800">{{ $numero_documento }}</p>
                    <p class="text-xs text-amber-600 mt-1">Los cambios realizados aquí son temporales y no se guardan en la base real.</p>
                </div>
                <a href="{{ route('impresiones-sas.index') }}" class="inline-flex items-center justify-center rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                    Nueva búsqueda
                </a>
            </div>

            @if ($registro)
                <div class="bg-white shadow-sm sm:rounded-lg p-5">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-semibold text-gray-800">log_registro</h3>
                        <span class="text-xs rounded-full bg-blue-100 text-blue-700 px-3 py-1">Edición temporal</span>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach ($registro as $campo => $valor)
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 mb-1">{{ $campo }}</label>
                                <input type="text" class="impresion-registro block w-full rounded-lg border-gray-300 shadow-sm text-sm"
                                    data-campo="{{ $campo }}"
                                    value="{{ is_scalar($valor) || $valor === null ? $valor : json_encode($valor) }}">
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg p-5">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="font-semibold text-gray-800">log_registro_detalle</h3>
                        <p class="text-xs text-gray-500 mt-1">Puedes modificar cualquier valor mostrado.</p>
                    </div>
                    <span class="text-xs rounded-full bg-blue-100 text-blue-700 px-3 py-1">Edición temporal</span>
                </div>

                @if (count($detalles))
                    <div class="overflow-x-auto border border-gray-200 rounded-lg">
                        <table class="min-w-full text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    @foreach (array_keys($detalles[0]) as $campo)
                                        <th class="px-3 py-3 text-left font-semibold text-gray-600 whitespace-nowrap">{{ $campo }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($detalles as $filaIndex => $fila)
                                    <tr class="border-t border-gray-200">
                                        @foreach ($fila as $campo => $valor)
                                            <td class="p-2 align-top">
                                                <input type="text" class="impresion-detalle w-full min-w-[140px] rounded-md border-gray-300 text-sm"
                                                    data-fila="{{ $filaIndex }}" data-campo="{{ $campo }}"
                                                    value="{{ is_scalar($valor) || $valor === null ? $valor : json_encode($valor) }}">
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="rounded-lg bg-gray-50 border border-gray-200 p-5 text-sm text-gray-500">No se encontraron detalles para este documento.</div>
                @endif
            </div>

            <div class="bg-amber-50 border border-amber-200 rounded-lg p-4 text-sm text-amber-800">
                <strong>Importante:</strong> esta pantalla solo crea una copia temporal de los datos consultados. No existe ninguna operación de actualización sobre <code>faboce2026.log_registro</code> ni <code>faboce2026.log_registro_detalle</code>.
            </div>
        </div>
    </div>
</x-app-layout>
