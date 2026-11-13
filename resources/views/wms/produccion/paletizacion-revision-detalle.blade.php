<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">WMS - Pallets de Liberación</h2>
    </x-slot>

    <div class="py-4 px-3 sm:py-6 sm:px-4">
        <div class="max-w-7xl mx-auto">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4 print:hidden">
                <div>
                    <a href="{{ route('wms.produccion.liberacion.create') }}" class="text-sm text-gray-600">&larr; Volver a liberaciones</a>
                    <h1 class="text-2xl font-bold text-gray-900 mt-2">Pallets generados</h1>
                    <p class="text-sm text-gray-500">Los QR se consultan en pantalla y las etiquetas se generan en PDF de 80 × 80 mm.</p>
                </div>
                <a href="{{ route('wms.produccion.liberacion.pallets', ['entrega' => $entrega->id, 'pdf' => 1]) }}"
                   target="_blank"
                   class="px-4 py-2 rounded-lg bg-gray-900 text-white font-semibold text-center">
                    IMPRIMIR ETIQUETAS 8 × 8 CM
                </a>
            </div>

            <div class="bg-white shadow rounded-xl p-4 sm:p-5 mb-5 print:hidden">
                <div class="grid grid-cols-2 md:grid-cols-6 gap-4">
                    <div><div class="text-xs text-gray-500">Documento</div><div class="font-mono font-semibold">{{ $entrega->documento?->id_documento ?? '—' }}</div></div>
                    <div><div class="text-xs text-gray-500">Folio</div><div class="font-semibold">{{ $entrega->folio_fisico ?? '—' }}</div></div>
                    <div><div class="text-xs text-gray-500">Origen</div><div>{{ $entrega->origen ?? '—' }}</div></div>
                    <div><div class="text-xs text-gray-500">Fecha</div><div>{{ optional($entrega->fecha_entrega)->format('d/m/Y') }}</div></div>
                    <div><div class="text-xs text-gray-500">Estado</div><div class="font-semibold">{{ $entrega->estado }}</div></div>
                    <div><div class="text-xs text-gray-500">Total pallets</div><div class="font-bold text-lg">{{ $entrega->hu->count() }}</div></div>
                </div>
            </div>

            @if(session('success'))
                <div class="bg-green-100 text-green-800 rounded-lg p-3 mb-4 print:hidden">{{ session('success') }}</div>
            @endif

            @if($entrega->hu->isEmpty())
                <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-5 text-sm text-yellow-800">La liberación no tiene pallets registrados.</div>
            @else
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                    @foreach($entrega->hu as $hu)
                        <article class="bg-white shadow rounded-xl border border-gray-200 overflow-hidden pallet-card">
                            <div class="p-4 border-b border-gray-100 flex items-start justify-between gap-3">
                                <div>
                                    <div class="text-xs text-gray-500">HU / PALLET</div>
                                    <div class="font-mono text-xl font-bold text-gray-900">{{ $hu->numero }}</div>
                                </div>
                                <span class="px-2 py-1 rounded-full text-xs {{ $hu->estado === 'UBICADO' ? 'bg-green-100 text-green-700' : 'bg-blue-100 text-blue-700' }}">{{ $hu->estado }}</span>
                            </div>

                            <div class="p-4">
                                <div class="flex justify-center mb-4">
                                    <div class="qr-code bg-white p-2 border rounded-lg" data-value="{{ $hu->numero }}"></div>
                                </div>

                                <div class="grid grid-cols-2 gap-3 text-sm mb-4">
                                    <div><div class="text-xs text-gray-500">Formato</div><div class="font-semibold">{{ $hu->formato ?? '—' }}</div></div>
                                    <div><div class="text-xs text-gray-500">Tipo</div><div>{{ $hu->tipo ?? '—' }}</div></div>
                                    <div><div class="text-xs text-gray-500">Capacidad</div><div>{{ $hu->capacidad_estandar }}</div></div>
                                    <div><div class="text-xs text-gray-500">Cantidad</div><div class="font-semibold">{{ $hu->cantidad_total }}</div></div>
                                    <div class="col-span-2"><div class="text-xs text-gray-500">Ubicación</div><div>{{ $hu->ubicacion?->codigo ?? 'PENDIENTE' }}</div></div>
                                </div>

                                <div class="border-t pt-3">
                                    <div class="text-xs font-semibold text-gray-500 uppercase mb-2">Contenido</div>
                                    @foreach($hu->detalles as $detalle)
                                        <div class="flex justify-between gap-3 text-xs py-1 border-t first:border-0">
                                            <div>
                                                <div class="font-mono">{{ $detalle->codigo }}</div>
                                                <div class="text-gray-500">Lote: {{ $detalle->lote ?? 'S/L' }}</div>
                                            </div>
                                            <div class="font-semibold">{{ $detalle->cantidad }}</div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.qr-code').forEach(function (element) {
                new QRCode(element, {
                    text: element.dataset.value,
                    width: 180,
                    height: 180,
                    correctLevel: QRCode.CorrectLevel.M
                });
            });
        });
    </script>
</x-app-layout>
