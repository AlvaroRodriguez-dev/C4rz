<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Impresiones SAS — Edición del documento</h2>
    </x-slot>

    <div class="py-6 px-4 bg-gray-100">
        <div class="max-w-[1500px] mx-auto">

            @if (session('success'))
                <div class="mb-4 rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-700">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="mb-4 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                    {{ session('error') }}
                </div>
            @endif

            <form method="POST" action="{{ route('impresiones-sas.guardar-edicion') }}" id="form-impresion">
                @csrf

                <div class="flex items-center justify-between mb-4">
                    <div>
                        <div class="text-sm text-gray-500">Documento consultado</div>
                        <div class="text-xl font-bold">{{ $numero_documento }}</div>
                    </div>
                    <div class="flex gap-2">
                        <a href="{{ route('impresiones-sas.index') }}"
                           class="rounded border border-gray-300 bg-white px-4 py-2 text-sm hover:bg-gray-50">
                            Nueva búsqueda
                        </a>
                        <button type="submit"
                                class="rounded bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                            Guardar cambios temporales
                        </button>
                    </div>
                </div>

                {{-- Hoja de edición con la misma organización visual del PDF --}}
                <div class="bg-white shadow-lg border border-gray-300 mx-auto p-5 md:p-7" style="max-width: 1400px;">

                    {{-- ENCABEZADO --}}
                    <div class="grid grid-cols-[180px_1fr_190px] gap-4 items-start border-b-0">
                        <div class="pt-1">
                            <div class="text-3xl font-bold tracking-tight text-gray-800">fab<span class="font-normal">oce</span></div>
                        </div>

                        <div class="text-center">
                            <input name="cabecera[titulo]" value="{{ data_get($edicion ?? [], 'cabecera.titulo', 'DESPACHO DE PRODUCTO TERMINADO') }}"
                                   class="w-full border-0 text-center text-xl font-bold uppercase focus:ring-1 focus:ring-indigo-400">
                            <input name="cabecera[subtitulo]" value="{{ data_get($edicion ?? [], 'cabecera.subtitulo', '120 - AGENCIA PETROLERA - COCHABAMBA') }}"
                                   class="w-full border-0 text-center text-sm font-semibold uppercase focus:ring-1 focus:ring-indigo-400">
                        </div>

                        <input name="cabecera[id]" value="{{ data_get($edicion ?? [], 'cabecera.id', $numero_documento) }}"
                               class="w-full border-0 text-right text-xl font-bold focus:ring-1 focus:ring-indigo-400">
                    </div>

                    <div class="mt-3 border border-gray-400 text-xs">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-1 p-2">
                            <div class="grid grid-cols-[105px_1fr] items-center">
                                <label class="font-semibold">Lugar y fecha:</label>
                                <input name="cabecera[fechad]" value="{{ data_get($edicion ?? [], 'cabecera.fechad', data_get($registro ?? [], 'fecha')) }}" class="border-0 border-b border-gray-300 py-0.5 focus:ring-0">
                            </div>
                            <div class="grid grid-cols-[80px_1fr] items-center">
                                <label class="font-semibold">Ag. Origen:</label>
                                <input name="cabecera[origen]" value="{{ data_get($edicion ?? [], 'cabecera.origen', data_get($registro ?? [], 'origen')) }}" class="border-0 border-b border-gray-300 py-0.5 focus:ring-0">
                            </div>

                            <div class="grid grid-cols-[105px_1fr] items-center">
                                <label class="font-semibold">Empresa:</label>
                                <input name="cabecera[empresa]" value="{{ data_get($edicion ?? [], 'cabecera.empresa', trim((string) data_get($registro ?? [], 'nit', '') . ' - ' . (string) data_get($registro ?? [], 'razon_social', ''))) }}" class="border-0 border-b border-gray-300 py-0.5 focus:ring-0">
                            </div>
                            <div class="grid grid-cols-[80px_1fr] items-center">
                                <label class="font-semibold">Ag. Destino:</label>
                                <input name="cabecera[destino]" value="{{ data_get($edicion ?? [], 'cabecera.destino', data_get($registro ?? [], 'destino')) }}" class="border-0 border-b border-gray-300 py-0.5 focus:ring-0">
                            </div>

                            <div class="grid grid-cols-[105px_1fr] items-center">
                                <label class="font-semibold">Transportista:</label>
                                <input name="cabecera[transportista]" value="{{ data_get($edicion ?? [], 'cabecera.transportista', data_get($registro ?? [], 'nombre')) }}" class="border-0 border-b border-gray-300 py-0.5 focus:ring-0">
                            </div>
                            <div class="grid grid-cols-[80px_1fr] items-center">
                                <label class="font-semibold">Cel.:</label>
                                <input name="cabecera[telefono]" value="{{ data_get($edicion ?? [], 'cabecera.telefono', data_get($registro ?? [], 'telefono')) }}" class="border-0 border-b border-gray-300 py-0.5 focus:ring-0">
                            </div>

                            <div class="grid grid-cols-[105px_1fr] items-center md:col-span-2">
                                <label class="font-semibold">Detalle Camion:</label>
                                <input name="cabecera[camion]" value="{{ data_get($edicion ?? [], 'cabecera.camion', trim((string) data_get($registro ?? [], 'placa', '') . ' - ' . (string) data_get($registro ?? [], 'descripcionc', ''))) }}" class="border-0 border-b border-gray-300 py-0.5 focus:ring-0">
                            </div>
                        </div>
                    </div>

                    {{-- TABLA PRINCIPAL --}}
                    <div class="mt-3 overflow-x-auto">
                        <table class="w-full border-collapse text-[11px]">
                            <thead>
                                <tr class="bg-gray-100">
                                    @foreach([
                                        'nota'=>'NOTA','vje'=>'VJE','producto'=>'PRODUCTO','cajas'=>'CJAS.',
                                        'acum'=>'ACUM.','entr'=>'ENTR.','saldo'=>'SALDO','m2'=>'M2','imp_bs'=>'IMP. Bs'
                                    ] as $key => $label)
                                        <th class="border border-gray-700 px-1 py-1 text-center font-bold whitespace-nowrap">{{ $label }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($detalles as $i => $fila)
                                    @php
                                        $edit = data_get($edicion ?? [], "detalles.$i", []);
                                        $nota = $edit['nota'] ?? ($fila['nota'] ?? ($fila['factura'] ?? ($fila['tdocum'] ?? '')));
                                        $producto = $edit['producto'] ?? ($fila['producto'] ?? trim(($fila['descrip'] ?? '') . ' ' . ($fila['descrip1'] ?? '') . ' - ' . ($fila['lote'] ?? '')));
                                        $cajas = $edit['cajas'] ?? ($fila['cantidad_despacho'] ?? '');
                                    @endphp
                                    <tr>
                                        @foreach([
                                            'nota'=>$nota,
                                            'vje'=>$edit['vje'] ?? ($fila['vje'] ?? ''),
                                            'producto'=>$producto,
                                            'cajas'=>$cajas,
                                            'acum'=>$edit['acum'] ?? ($fila['acum'] ?? '0.00'),
                                            'entr'=>$edit['entr'] ?? ($fila['entr'] ?? $cajas),
                                            'saldo'=>$edit['saldo'] ?? ($fila['saldo'] ?? '0.00'),
                                            'm2'=>$edit['m2'] ?? ($fila['m2'] ?? ''),
                                            'imp_bs'=>$edit['imp_bs'] ?? ($fila['imp_bs'] ?? ''),
                                        ] as $campo => $valor)
                                            <td class="border border-gray-700 p-0">
                                                <input name="detalles[{{ $i }}][{{ $campo }}]" value="{{ $valor }}"
                                                       class="w-full border-0 bg-transparent px-1 py-1 text-[11px] text-center focus:bg-indigo-50 focus:ring-1 focus:ring-indigo-400 {{ $campo === 'producto' ? 'text-left min-w-[360px]' : 'min-w-[65px]' }}">
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach

                                <tr class="font-bold">
                                    <td colspan="3" class="border border-gray-700 px-2 py-1 text-right">TOTALES</td>
                                    @foreach(['cajas','acum','entr','saldo','m2','imp_bs'] as $campo)
                                        <td class="border border-gray-700 p-0">
                                            <input name="cabecera[total_{{ $campo }}]"
                                                   value="{{ data_get($edicion ?? [], 'cabecera.total_'.$campo, '') }}"
                                                   class="total-field w-full border-0 bg-transparent px-1 py-1 text-center font-bold focus:bg-indigo-50 focus:ring-1 focus:ring-indigo-400"
                                                   data-total-field="{{ $campo }}">
                                        </td>
                                    @endforeach
                                </tr>

                                <tr class="font-bold">
                                    <td colspan="8" class="border border-gray-700 px-2 py-1 text-right">
                                        TOTAL $us &nbsp;&nbsp; Tipo de Cambio:
                                        <input name="cabecera[tipo_cambio]" value="{{ data_get($edicion ?? [], 'cabecera.tipo_cambio', '11.54') }}"
                                               class="inline-block w-20 border-0 border-b border-gray-400 bg-transparent text-center font-bold focus:ring-0">
                                    </td>
                                    <td class="border border-gray-700 p-0">
                                        <input name="cabecera[total_usd]" value="{{ data_get($edicion ?? [], 'cabecera.total_usd', '') }}"
                                               class="w-full border-0 bg-transparent px-1 py-1 text-center font-bold focus:bg-indigo-50 focus:ring-1 focus:ring-indigo-400">
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    {{-- FIRMAS / TRANSPORTISTA --}}
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mt-12 text-[11px]">
                        <div class="text-center">
                            <div class="border-t border-gray-700 pt-1">CHOFER: <input name="cabecera[nombre]" value="{{ data_get($edicion ?? [], 'cabecera.nombre', data_get($registro ?? [], 'nombre')) }}" class="inline w-40 border-0 border-b border-gray-300 p-0 text-center text-[11px]"></div>
                            <div class="mt-1">PLACA: <input name="cabecera[placa]" value="{{ data_get($edicion ?? [], 'cabecera.placa', data_get($registro ?? [], 'placa')) }}" class="inline w-32 border-0 border-b border-gray-300 p-0 text-center text-[11px]"></div>
                            <div class="mt-1">EMPRESA TRANSPORTE: <input name="cabecera[empresa_transporte]" value="{{ data_get($edicion ?? [], 'cabecera.empresa_transporte', data_get($registro ?? [], 'razon_social')) }}" class="inline w-28 border-0 border-b border-gray-300 p-0 text-center text-[11px]"></div>
                            <div class="mt-1">FABOCE S.R.L</div>
                        </div>

                        <div class="text-center">
                            <div class="border-t border-gray-700 pt-1">ENCARGADO DE ALMACEN</div>
                            <div class="mt-1"><input name="cabecera[usuario]" value="{{ data_get($edicion ?? [], 'cabecera.usuario', auth()->user()->name ?? '') }}" class="w-full border-0 border-b border-gray-300 p-0 text-center text-[11px]"></div>
                        </div>

                        <div class="text-center">
                            <div class="border-t border-gray-700 pt-1">SELLO Y FIRMA DE LA EMPRESA</div>
                            <div class="mt-1">NOMBRE(S) Y APELLIDO(S): <input name="cabecera[nombre_receptor]" value="{{ data_get($edicion ?? [], 'cabecera.nombre_receptor', '') }}" class="w-32 border-0 border-b border-gray-300 p-0 text-[11px]"></div>
                            <div class="mt-1">EMPRESA TRANSPORTE: <input name="cabecera[nit]" value="{{ data_get($edicion ?? [], 'cabecera.nit', data_get($registro ?? [], 'nit')) }}" class="w-32 border-0 border-b border-gray-300 p-0 text-[11px]"></div>
                            <div class="mt-1">C.I.: <input name="cabecera[carnet_identidad]" value="{{ data_get($edicion ?? [], 'cabecera.carnet_identidad', data_get($registro ?? [], 'carnet_identidad')) }}" class="w-24 border-0 border-b border-gray-300 p-0 text-[11px]"> TELEFONO: <input name="cabecera[telefono_firma]" value="{{ data_get($edicion ?? [], 'cabecera.telefono_firma', data_get($registro ?? [], 'telefono')) }}" class="w-24 border-0 border-b border-gray-300 p-0 text-[11px]"></div>
                        </div>
                    </div>

                    {{-- TEXTO DE CONFORMIDAD --}}
                    <div class="mt-7 text-[12px] leading-5">
                        <div>
                            RECIBI LA MERCADERIA DESCRITA DE
                            <input name="cabecera[quintales]" value="{{ data_get($edicion ?? [], 'cabecera.quintales', data_get($registro ?? [], 'quintales', '')) }}"
                                   class="w-20 border-0 border-b border-gray-400 p-0 text-center font-bold focus:ring-0">
                            QQ. COMPROMETIENDOME A ENTREGARLA EN PERFECTO ESTADO AL DESTINO
                        </div>
                        <div>
                            EN
                            <input name="cabecera[dias_entrega]" value="{{ data_get($edicion ?? [], 'cabecera.dias_entrega', '') }}"
                                   class="w-12 border-0 border-b border-gray-400 p-0 text-center focus:ring-0">
                            DIAS, ASUMIENDO LA RESPONSABILIDAD POR PERDIDA O ROTURA QUE SERAN DEDUCIBLES DEL FLETE.
                        </div>
                    </div>

                    <div class="mt-2 text-[11px]">
                        <strong>OBSERVACION:</strong>
                        <span class="ml-2">Una vez firmada la conformidad no se aceptan reclamos</span>
                        <textarea name="cabecera[observacion]" rows="4"
                                  class="mt-1 w-full resize-none border border-gray-700 text-[11px] focus:border-indigo-500 focus:ring-indigo-500">{{ data_get($edicion ?? [], 'cabecera.observacion', data_get($registro ?? [], 'observacion', '')) }}</textarea>
                    </div>

                    <div class="mt-8 flex justify-between text-[10px] text-gray-500">
                        <div>
                            Usuario: {{ data_get($edicion ?? [], 'cabecera.usuario', auth()->user()->name ?? '') }}<br>
                            Fecha y Hora de Impresión: {{ now()->format('d-m-Y H:i:s') }}
                        </div>
                        <div class="self-end">Página 1 de 1</div>
                    </div>
                </div>

                <div class="mt-4 rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-800">
                    <strong>Modo seguro:</strong> esta pantalla reproduce la estructura del documento de impresión.
                    Las modificaciones se guardan exclusivamente en la sesión de impresión y serán la fuente del PDF posterior.
                    <strong>No se ejecuta ningún UPDATE, INSERT ni DELETE sobre faboce2026.</strong>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
