<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div><h2 class="text-2xl font-bold text-gray-800">{{ $activo->codigo_ti }}</h2><p class="text-sm text-gray-500">Ficha individual del activo</p></div>
            <a href="{{ route('itam.activos.index') }}" class="rounded-lg border px-4 py-2 text-sm">Volver al inventario</a>
        </div>
    </x-slot>
    <div class="py-6">
        <div class="mx-auto max-w-5xl space-y-5 px-4 sm:px-6 lg:px-8">
            @if (session('success'))<div class="rounded-lg bg-green-50 p-3 text-sm text-green-800">{{ session('success') }}</div>@endif
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <section class="rounded-xl bg-white p-5 shadow">
                    <h3 class="mb-4 font-bold text-gray-800">Identificación</h3>
                    <dl class="space-y-3 text-sm">
                        <div><dt class="text-gray-500">Tipo</dt><dd class="font-medium">{{ $activo->tipoActivo?->descripcion }}</dd></div>
                        <div><dt class="text-gray-500">Marca / Modelo</dt><dd class="font-medium">{{ $activo->marca?->nombre ?? '—' }} / {{ $activo->modelo?->nombre ?? '—' }}</dd></div>
                        <div><dt class="text-gray-500">Número de serie</dt><dd class="font-medium">{{ $activo->numero_serie ?: '—' }}</dd></div>
                        <div><dt class="text-gray-500">Número de parte</dt><dd class="font-medium">{{ $activo->numero_parte ?: '—' }}</dd></div>
                    </dl>
                </section>
                <section class="rounded-xl bg-white p-5 shadow">
                    <h3 class="mb-4 font-bold text-gray-800">Situación actual</h3>
                    <dl class="space-y-3 text-sm">
                        <div><dt class="text-gray-500">Estado</dt><dd class="font-medium">{{ $activo->estado?->descripcion }}</dd></div>
                        <div><dt class="text-gray-500">Ubicación</dt><dd class="font-medium">{{ $activo->ubicacion?->codigo }} — {{ $activo->ubicacion?->descripcion }}</dd></div>
                        <div><dt class="text-gray-500">Custodio</dt><dd class="font-medium">{{ $activo->custodio_id ?: 'Sin asignar' }}</dd></div>
                        <div><dt class="text-gray-500">Usuario asignado</dt><dd class="font-medium">{{ $activo->usuario_asignado_id ?: 'Sin asignar' }}</dd></div>
                    </dl>
                </section>
                <section class="rounded-xl bg-white p-5 shadow">
                    <h3 class="mb-4 font-bold text-gray-800">Adquisición y garantía</h3>
                    <dl class="space-y-3 text-sm">
                        <div><dt class="text-gray-500">Origen</dt><dd class="font-medium">{{ $activo->origen_documento === 'ADQUISICION' ? 'Adquisición' : 'Inventario inicial' }}</dd></div>
                        <div><dt class="text-gray-500">Fecha adquisición</dt><dd class="font-medium">{{ $activo->fecha_adquisicion?->format('d/m/Y') ?? '—' }}</dd></div>
                        <div><dt class="text-gray-500">Costo</dt><dd class="font-medium">{{ $activo->costo !== null ? number_format((float) $activo->costo, 2) : '—' }}</dd></div>
                        <div><dt class="text-gray-500">Garantía hasta</dt><dd class="font-medium">{{ $activo->garantia_fecha?->format('d/m/Y') ?? '—' }}</dd></div>
                        <div><dt class="text-gray-500">Fecha de alta ITAM</dt><dd class="font-medium">{{ $activo->fecha_alta?->format('d/m/Y') ?? '—' }}</dd></div>
                    </dl>
                </section>
                <section class="rounded-xl bg-white p-5 shadow">
                    <h3 class="mb-4 font-bold text-gray-800">Observaciones</h3>
                    <p class="whitespace-pre-line text-sm text-gray-700">{{ $activo->observaciones ?: 'Sin observaciones.' }}</p>
                </section>
            </div>
        </div>
    </div>
</x-app-layout>