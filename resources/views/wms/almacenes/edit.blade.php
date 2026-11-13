<x-app-layout>
    <x-slot name="header">
        <h2 class="text-2xl font-bold text-gray-800">WMS - Editar almacén</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-2xl mx-auto px-3">
            <div class="mb-4">
                <a href="{{ route('wms.almacenes.index') }}" class="text-sm text-gray-600 hover:text-gray-900">
                    &larr; Volver a almacenes
                </a>
            </div>

            @include('wms.almacenes._form', [
                'action' => route('wms.almacenes.update', $almacen),
                'method' => 'PUT',
                'almacen' => $almacen,
            ])
        </div>
    </div>
</x-app-layout>
