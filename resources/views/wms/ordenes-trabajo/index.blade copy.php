<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">WMS - Órdenes de Trabajo</h2>
    </x-slot>

    <script src="https://cdn.jsdelivr.net/npm/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>

    <div class="py-4 px-3 sm:py-6 sm:px-4">
        <div class="max-w-3xl mx-auto">
            <a href="{{ route('wms.index') }}" class="text-sm text-gray-600 mb-3 inline-flex items-center gap-1">&larr;
                Volver</a>

            <div id="alertBox" class="hidden mb-4 p-3 rounded-lg text-sm"></div>

            <div id="listaOts" class="space-y-4"></div>
            <div id="sinOts" class="hidden text-center text-gray-500 py-10">No hay Órdenes de Trabajo pendientes.
            </div>
            <div id="qrReader" class="hidden mt-4 rounded-xl overflow-hidden border border-gray-200"></div>
        </div>
    </div>

    <script>
        const routePendientes = "{{ route('wms.ordenes.trabajo.pendientes') }}";
        const routeMarcar = (otId, pallet) =>
            `{{ url('wms/ordenes-trabajo') }}/${otId}/pallet/${encodeURIComponent(pallet)}/chequear`;
        const csrfToken = "{{ csrf_token() }}";
        let html5QrCode = null;

        document.addEventListener('DOMContentLoaded', cargarOts);

        function cargarOts() {
            fetch(routePendientes)
                .then(res => res.json())
                .then(data => {
                    const cont = document.getElementById('listaOts');
                    cont.innerHTML = '';

                    if (data.ordenes.length === 0) {
                        document.getElementById('sinOts').classList.remove('hidden');
                        return;
                    }
                    document.getElementById('sinOts').classList.add('hidden');

                    data.ordenes.forEach(ot => {
                        const palletsHtml = ot.pallets.map(p => {
                            const itemsTxt = p.items.map(i => `
            <div class="py-0.5">
                <span class="font-mono">${i.codigo}</span> — ${i.descrip ?? ''} - ${i.descrip1 ?? ''}<br>
                <span class="text-gray-400">Lote ${i.clote ?? 'S/L'} · ${i.cantidad} cajas</span>
            </div>
        `).join('');

                            return `
            <div class="flex items-start justify-between gap-3 border-t border-gray-100 pt-3 mt-3 first:border-0 first:mt-0 first:pt-0">
                <div class="min-w-0">
                    <p class="font-mono font-semibold text-gray-800">Pallet ${p.pallet}</p>
                    <p class="text-xs font-medium text-blue-700 bg-blue-50 rounded px-2 py-0.5 inline-block mt-1">
                        📍 Almacén ${p.almacen} · Galpón ${p.galpon} · Ubic. ${p.ubicacion}
                    </p>
                    <div class="text-xs text-gray-600 mt-2">${itemsTxt}</div>
                </div>
                ${p.chequeado
                    ? `<span class="shrink-0 text-xs font-semibold text-green-700 bg-green-100 rounded-full px-3 py-1.5">✅ Verificado</span>`
                    : `<button data-ot="${ot.id}" data-pallet="${p.pallet}" class="btnChequear shrink-0 bg-blue-600 text-white text-xs font-semibold rounded-full px-3 py-2">Verificar</button>`
                }
            </div>
        `;
                        }).join('');

                        cont.insertAdjacentHTML('beforeend', `
                            <div class="bg-white border border-gray-200 rounded-xl p-4 shadow-sm">
                                <div class="flex justify-between items-start mb-1">
                                    <div>
                                        <p class="text-xs text-gray-400">OT #${ot.id} · ${ot.creado}</p>
                                        <p class="text-sm font-semibold text-gray-800">Nota Tipo ${ot.tipo_registro} #${ot.id_registro}</p>
                                        ${ot.glosa ? `<p class="text-xs text-gray-500">${ot.glosa}</p>` : ''}
                                    </div>
                                    <span class="text-xs font-semibold text-gray-600 bg-gray-100 rounded-full px-3 py-1">
                                        ${ot.pallets_chequeados}/${ot.total_pallets} pallets
                                    </span>
                                </div>
                                ${palletsHtml}
                            </div>
                        `);
                    });

                    document.querySelectorAll('.btnChequear').forEach(btn => {
                        btn.addEventListener('click', () => chequearPallet(btn.dataset.ot, btn.dataset.pallet));
                    });
                });
        }

        function chequearPallet(otId, pallet) {
            fetch(routeMarcar(otId, pallet), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
            }).then(async res => {
                const data = await res.json();
                if (!res.ok) {
                    mostrarAlerta(Object.values(data.errors ?? {}).flat().join(' '), 'error');
                    return;
                }
                mostrarAlerta(data.message, 'success');
                cargarOts();
            });
        }

        function mostrarAlerta(msg, tipo) {
            const box = document.getElementById('alertBox');
            box.className =
                `mb-4 p-3 rounded-lg text-sm ${tipo === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}`;
            box.textContent = msg;
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        }
    </script>
</x-app-layout>
