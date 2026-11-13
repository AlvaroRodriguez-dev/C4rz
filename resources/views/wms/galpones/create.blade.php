<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Nuevo Galpón</h2>
            <p class="text-sm text-gray-500">Registrar un área física dentro de un almacén principal</p>
        </div>
    </x-slot>
    <div class="py-6">
        <div class="max-w-3xl mx-auto px-3">
            @include('wms.galpones._form', [
                'action' => route('wms.galpones.store'),
                'method' => 'POST',
                'galpon' => null,
            ])
        </div>
    </div>
</x-app-layout>
