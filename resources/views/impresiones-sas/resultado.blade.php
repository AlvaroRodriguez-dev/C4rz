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

                <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
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

                        <a href="{{ route('impresiones-sas.pdf') }}"
                           target="_blank"
                           class="rounded bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700">
                            Generar PDF
                        </a>
                    </div>
                </div>

                <div class="bg-white shadow-lg border border-gray-300 mx-auto p-5 md:p-7" style="max-width: 1400px;">

                    {{-- ENCABEZADO --}}
                    @php
                        $cab = $edicion['cabecera'] ?? $cabecera ?? [];
                    @endphp

                    <div class="grid grid-cols-[180px_1fr_190px] gap-4 items-start">
                        <div class="pt-1">
                            <div class="text-3xl font-bold tracking-tight text-gray-800">
                                fab<span class="font-normal">oce</span>
                            </div>
                        </div>

                        <div class="text-center">
                            <input name="cabecera[titulo]"
                                   value="{{ data_get($cab, 'titulo', 'DESPACHO DE PRODUCTO TERMINADO') }}"
                                   class="w-full border-0 text-center text-xl font-bold uppercase focus:ring-1 focus:ring-indigo-400">

                            <input name="cabecera[subtitulo]"
                                   value="{{ data_get($cab, 'subtitulo', '') }}"
                                   class="w-full border-0 text-center text-sm font-semibold uppercase focus:ring-1 focus:ring-indigo-400">
                        </div>

                        <input name="cabecera[id]"
                               value="{{ data_get($cab, 'id', $numero_documento) }}"
                               class="w-full border-0 text-right text-xl font-bold focus:ring-1 focus:ring-indigo-400">
                    </div>

                    <div class="mt-3 border border-gray-400 text-xs">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-1 p-2">

                            <div class="grid grid-cols-[105px_1fr] items-center">
                                <label class="font-semibold">Lugar y fecha:</label>
                                <input name="cabecera[fechad]"
                                       value="{{ data_get($cab, 'fechad', '') }}"
                                       class="border-0 border-b border-gray-300 py-0.5 focus:ring-0">
                            </div>

                            <div class="grid grid-cols-[80px_1fr] items-center">
                                <label class="font-semibold">Ag. Origen:</label>
                                <input name="cabecera[origen]"
                                       value="{{ data_get($cab, 'origen', '') }}"
                                       class="border-0 border-b border-gray-300 py-0.5 focus:ring-0">
                            </div>

                            <div class="grid grid-cols-[105px_1fr] items-center">
                                <label class="font-semibold">Empresa:</label>
                                <input name="cabecera[empresa]"
                                       value="{{ data_get($cab, 'empresa', '') }}"
                                       class="border-0 border-b border-gray-300 py-0.5 focus:ring-0">
                            </div>

                            <div class="grid grid-cols-[80px_1fr] items-center">
                                <label class="font-semibold">Ag. Destino:</label>
                                <input name="cabecera[destino]"
                                       value="{{ data_get($cab, 'destino', '') }}"
                                       class="border-0 border-b border-gray-300 py-0.5 focus:ring-0">
                            </div>

                            <div class="grid grid-cols-[105px_1fr] items-center">
                                <label class="font-semibold">Transportista:</label>
                                <input name="cabecera[transportista]"
                                       value="{{ data_get($cab, 'transportista', data_get($cab, 'nombre', '')) }}"
                                       class="border-0 border-b border-gray-300 py-0.5 focus:ring-0">
                            </div>

                            <div class="grid grid-cols-[80px_1fr] items-center">
                                <label class="font-semibold">Cel.:</label>
                                <input name="cabecera[telefono]"
                                       value="{{ data_get($cab, 'telefono', '') }}"
                                       class="border-0 border-b border-gray-300 py-0.5 focus:ring-0">
                            </div>

                            <div class="grid grid-cols-[105px_1fr] items-center md:col-span-2">
                                <label class="font-semibold">Detalle Camion:</label>
                                <input name="cabecera[camion]"
                                       value="{{ data_get($cab, 'camion', '') }}"
                                       class="border-0 border-b border-gray-300 py-0.5 focus:ring-0">
                            </div>
                        </div>
                    </div>

                    {{-- TABLA PRINCIPAL --}}
                    <div class="mt-3 overflow-x-auto">
                        <table class="w-full border-collapse text-[11px]">
                            <thead>
                                <tr class="bg-gray-100">
                                    @foreach([
                                        'factnota'=>'NOTA',
                                        'viaje'=>'VJE',
                                        'producto'=>'PRODUCTO',
                                        'facturada'=>'CJAS.',
                                        'acumulada'=>'ACUM.',
                                        'entregada'=>'ENTR.',
                                        'saldo'=>'SALDO',
                                        'metros'=>'M2',
                                        'impbs'=>'COSTO $US'
                                    ] as $key => $label)
                                        <th class="border border-gray-700 px-1 py-1 text-center font-bold whitespace-nowrap">
                                            {{ $label }}
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>

                            <tbody>
                                @foreach($detalles as $i => $fila)
                                    @php
                                        $edit = $edicion['detalles'][$i] ?? [];

                                        $factnota = $edit['factnota'] ?? ($fila['factnota'] ?? '');
                                        $viaje = $edit['viaje'] ?? ($fila['viaje'] ?? '');
                                        $producto = $edit['producto'] ?? ($fila['producto'] ?? '');
                                        $descrip1 = $edit['descrip1'] ?? ($fila['descrip1'] ?? '');
                                        $lote = $edit['lote'] ?? ($fila['lote'] ?? '');

                                        $facturada = $edit['facturada'] ?? ($fila['facturada'] ?? '');
                                        $acumulada = $edit['acumulada'] ?? ($fila['acumulada'] ?? '');
                                        $entregada = $edit['entregada'] ?? ($fila['entregada'] ?? '');
                                        $saldo = $edit['saldo'] ?? ($fila['saldo'] ?? '');
                                        $metros = $edit['metros'] ?? ($fila['metros'] ?? '');
                                        $impbs = $edit['impbs'] ?? ($fila['impbs'] ?? '');
                                    @endphp

                                    <tr>
                                        <td class="border border-gray-700 p-0">
                                            <input name="detalles[{{ $i }}][factnota]"
                                                   value="{{ $factnota }}"
                                                   class="w-full min-w-[110px] border-0 bg-transparent px-1 py-1 text-[11px] text-center focus:bg-indigo-50 focus:ring-1 focus:ring-indigo-400">
                                        </td>

                                        <td class="border border-gray-700 p-0">
                                            <input name="detalles[{{ $i }}][viaje]"
                                                   value="{{ $viaje }}"
                                                   class="w-full min-w-[55px] border-0 bg-transparent px-1 py-1 text-[11px] text-center focus:bg-indigo-50 focus:ring-1 focus:ring-indigo-400">
                                        </td>

                                        <td class="border border-gray-700 p-0">
                                            <div class="min-w-[390px]">
                                                <input name="detalles[{{ $i }}][producto]"
                                                       value="{{ $producto }}"
                                                       class="w-full border-0 bg-transparent px-1 py-1 text-[11px] text-left font-medium focus:bg-indigo-50 focus:ring-1 focus:ring-indigo-400"
                                                       title="Producto">

                                                <div class="flex border-t border-gray-100">
                                                    <input name="detalles[{{ $i }}][descrip1]"
                                                           value="{{ $descrip1 }}"
                                                           placeholder="Descripción adicional"
                                                           class="w-2/3 border-0 bg-transparent px-1 py-0.5 text-[10px] text-left text-gray-600 focus:bg-indigo-50 focus:ring-1 focus:ring-indigo-400">

                                                    <input name="detalles[{{ $i }}][lote]"
                                                           value="{{ $lote }}"
                                                           placeholder="Lote"
                                                           class="w-1/3 border-0 border-l border-gray-100 bg-transparent px-1 py-0.5 text-[10px] text-left text-gray-600 focus:bg-indigo-50 focus:ring-1 focus:ring-indigo-400">
                                                </div>
                                            </div>
                                        </td>

                                        @foreach([
                                            'facturada' => $facturada,
                                            'acumulada' => $acumulada,
                                            'entregada' => $entregada,
                                            'saldo' => $saldo,
                                            'metros' => $metros,
                                            'impbs' => $impbs,
                                        ] as $campo => $valor)
                                            <td class="border border-gray-700 p-0">
                                                <input name="detalles[{{ $i }}][{{ $campo }}]"
                                                       value="{{ $valor }}"
                                                       data-suma-campo="{{ $campo }}"
                                                       class="numeric-field w-full min-w-[65px] border-0 bg-transparent px-1 py-1 text-[11px] text-right focus:bg-indigo-50 focus:ring-1 focus:ring-indigo-400">
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach

                                {{-- TOTALES: derivados de las filas. No se toman de un valor fijo. --}}
                                <tr class="font-bold">
                                    <td colspan="3" class="border border-gray-700 px-2 py-1 text-right">TOTALES</td>

                                    @foreach(['facturada','acumulada','entregada','saldo','metros','impbs'] as $campo)
                                        <td class="border border-gray-700 p-0">
                                            <input name="cabecera[total_{{ $campo }}]"
                                                   value="{{ data_get($cab, 'total_'.$campo, '') }}"
                                                   data-total-campo="{{ $campo }}"
                                                   readonly
                                                   class="total-field w-full border-0 bg-gray-50 px-1 py-1 text-right font-bold">
                                        </td>
                                    @endforeach
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    {{-- FIRMAS / TRANSPORTISTA --}}
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mt-12 text-[11px]">
                        <div class="text-center">
                            <div class="border-t border-gray-700 pt-1">
                                CHOFER:
                                <input name="cabecera[nombre]"
                                       value="{{ data_get($cab, 'nombre', '') }}"
                                       class="inline w-40 border-0 border-b border-gray-300 p-0 text-center text-[11px]">
                            </div>

                            <div class="mt-1">
                                PLACA:
                                <input name="cabecera[placa]"
                                       value="{{ data_get($cab, 'placa', '') }}"
                                       class="inline w-32 border-0 border-b border-gray-300 p-0 text-center text-[11px]">
                            </div>

                            <div class="mt-1">
                                EMPRESA TRANSPORTE:
                                <input name="cabecera[empresa_transporte]"
                                       value="{{ data_get($cab, 'empresa_transporte', data_get($cab, 'razon_social', '')) }}"
                                       class="inline w-28 border-0 border-b border-gray-300 p-0 text-center text-[11px]">
                            </div>

                            <div class="mt-1">FABOCE S.R.L</div>
                        </div>

                        <div class="text-center">
                            <div class="border-t border-gray-700 pt-1">ENCARGADO DE ALMACEN</div>
                            <div class="mt-1">
                                <input name="cabecera[usuario]"
                                       value="{{ data_get($cab, 'usuario', auth()->user()->name ?? '') }}"
                                       class="w-full border-0 border-b border-gray-300 p-0 text-center text-[11px]">
                            </div>
                        </div>

                        <div class="text-center">
                            <div class="border-t border-gray-700 pt-1">SELLO Y FIRMA DE LA EMPRESA</div>

                            <div class="mt-1">
                                NOMBRE(S) Y APELLIDO(S):
                                <input name="cabecera[nombre_receptor]"
                                       value="{{ data_get($cab, 'nombre_receptor', '') }}"
                                       class="w-32 border-0 border-b border-gray-300 p-0 text-[11px]">
                            </div>

                            <div class="mt-1">
                                EMPRESA TRANSPORTE:
                                <input name="cabecera[nit]"
                                       value="{{ data_get($cab, 'nit', '') }}"
                                       class="w-32 border-0 border-b border-gray-300 p-0 text-[11px]">
                            </div>

                            <div class="mt-1">
                                C.I.:
                                <input name="cabecera[carnet_identidad]"
                                       value="{{ data_get($cab, 'carnet_identidad', '') }}"
                                       class="w-24 border-0 border-b border-gray-300 p-0 text-[11px]">

                                TELEFONO:
                                <input name="cabecera[telefono_firma]"
                                       value="{{ data_get($cab, 'telefono_firma', data_get($cab, 'telefono', '')) }}"
                                       class="w-24 border-0 border-b border-gray-300 p-0 text-[11px]">
                            </div>
                        </div>
                    </div>

                    {{-- TEXTO DE CONFORMIDAD --}}
                    <div class="mt-7 text-[12px] leading-5">
                        <div>
                            RECIBI LA MERCADERIA DESCRITA DE
                            <input name="cabecera[quintales]"
                                   value="{{ data_get($cab, 'quintales', '') }}"
                                   class="w-20 border-0 border-b border-gray-400 p-0 text-center font-bold focus:ring-0">
                            QQ. COMPROMETIENDOME A ENTREGARLA EN PERFECTO ESTADO AL DESTINO
                        </div>

                        <div>
                            EN
                            <input name="cabecera[dias_entrega]"
                                   value="{{ data_get($cab, 'dias_entrega', '') }}"
                                   class="w-12 border-0 border-b border-gray-400 p-0 text-center focus:ring-0">
                            DIAS, ASUMIENDO LA RESPONSABILIDAD POR PERDIDA O ROTURA QUE SERAN DEDUCIBLES DEL FLETE.
                        </div>
                    </div>

                    <div class="mt-2 text-[11px]">
                        <strong>OBSERVACION:</strong>
                        <span class="ml-2">Una vez firmada la conformidad no se aceptan reclamos</span>

                        <textarea name="cabecera[observacion]" rows="4"
                                  class="mt-1 w-full resize-none border border-gray-700 text-[11px] focus:border-indigo-500 focus:ring-indigo-500">{{ data_get($cab, 'observacion', data_get($cabecera ?? [], 'glosa', '')) }}</textarea>
                    </div>

                    <div class="mt-8 flex justify-between text-[10px] text-gray-500">
                        <div>
                            Usuario: {{ data_get($cab, 'usuario', auth()->user()->name ?? '') }}<br>
                            Fecha y Hora de Impresión: {{ now()->format('d-m-Y H:i:s') }}
                        </div>
                        <div class="self-end">Página 1 de 1</div>
                    </div>
                </div>

                <div class="mt-4 rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-800">
                    <strong>Modo seguro:</strong>
                    esta pantalla utiliza una copia temporal de los datos para preparar la impresión.
                    Las modificaciones se guardan exclusivamente en la sesión.
                    <strong>No se ejecuta ningún UPDATE, INSERT ni DELETE sobre faboce2026.</strong>
                </div>
            </form>
        </div>
    </div>

    <script>
        (() => {
            const form = document.getElementById('form-impresion');
            if (!form) return;

            const parseNumber = (value) => {
                if (value === null || value === undefined || value === '') return null;

                const normalized = String(value).replace(/,/g, '').trim();
                const number = Number(normalized);

                return Number.isFinite(number) ? number : null;
            };

            const formatNumber = (number) => {
                return number.toLocaleString('en-US', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            };

            const recalcularTotales = () => {
                const campos = ['facturada', 'acumulada', 'entregada', 'saldo', 'metros', 'impbs'];

                campos.forEach((campo) => {
                    let total = 0;
                    let tieneValor = false;

                    form.querySelectorAll('[data-suma-campo="' + campo + '"]').forEach((input) => {
                        const value = parseNumber(input.value);
                        if (value !== null) {
                            total += value;
                            tieneValor = true;
                        }
                    });

                    const output = form.querySelector('[data-total-campo="' + campo + '"]');
                    if (output) {
                        output.value = tieneValor ? formatNumber(total) : '';
                    }
                });

                const totalBs = parseNumber(
                    form.querySelector('[data-total-campo="impbs"]')?.value
                );

                const tipoCambio = parseNumber(
                    document.getElementById('tipo-cambio')?.value
                );

                const totalUsd = document.getElementById('total-usd');

                if (totalUsd) {
                    totalUsd.value = (
                        totalBs !== null &&
                        tipoCambio !== null &&
                        tipoCambio > 0
                    ) ? formatNumber(totalBs / tipoCambio) : '';
                }
            };

            form.querySelectorAll('[data-suma-campo], #tipo-cambio').forEach((input) => {
                input.addEventListener('input', recalcularTotales);
            });

            recalcularTotales();
        })();
    </script>
</x-app-layout>
