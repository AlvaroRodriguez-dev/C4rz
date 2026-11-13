<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Editar Galpón</h2>
            <p class="text-sm text-gray-500">{{ $galpon->codigo }} - {{ $galpon->nombre }}</p>
        </div>
    </x-slot>
    <div class="py-6">
        <div class="max-w-3xl mx-auto px-3">
            @include('wms.galpones._form', [
                'action' => route('wms.galpones.update', $galpon),
                'method' => 'PUT',
                'galpon' => $galpon,
            ])
        </div>
    </div>
</x-app-layout>
