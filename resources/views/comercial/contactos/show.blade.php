<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">Contacto: {{ $contacto->nombre }}</h2>
    </x-slot>

    <div class="py-6 sm:py-8 max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        @if(session('success'))
            <div class="mb-4 p-4 bg-green-100 text-green-700 rounded-lg text-sm">{{ session('success') }}</div>
        @endif

        <div class="bg-white shadow rounded-lg p-4 sm:p-6">
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div><dt class="text-gray-500 text-sm">Nombre</dt><dd class="font-medium">{{ $contacto->nombre }}</dd></div>
                <div><dt class="text-gray-500 text-sm">Cargo</dt><dd class="font-medium">{{ $contacto->cargo }}</dd></div>
                <div><dt class="text-gray-500 text-sm">Teléfono</dt><dd class="font-medium">{{ $contacto->telefono }}</dd></div>
                <div><dt class="text-gray-500 text-sm">Email</dt><dd class="font-medium break-all">{{ $contacto->email }}</dd></div>
                <div><dt class="text-gray-500 text-sm">Agencia</dt><dd class="font-medium">{{ $contacto->agencia->descripcion }}</dd></div>
                <div><dt class="text-gray-500 text-sm">Estado</dt><dd class="font-medium">{{ $contacto->activo ? 'Activo' : 'Inactivo' }}</dd></div>
            </dl>
            <div class="mt-4 flex flex-col-reverse sm:flex-row gap-2">
                <a href="{{ route('comercial.contactos.index') }}" class="px-4 py-2 bg-gray-300 rounded text-center">Volver</a>
                <a href="{{ route('comercial.contactos.edit', $contacto) }}" class="px-4 py-2 bg-yellow-500 text-white rounded text-center">Editar</a>
            </div>
        </div>

        <div class="bg-white shadow rounded-lg p-4 sm:p-6 text-center">
            <h3 class="font-semibold text-gray-700 mb-4">Código QR - Tarjeta Pública</h3>
            <div id="qrcode" class="flex justify-center mb-4"></div>
            <p class="text-sm text-gray-500 break-all mb-4 px-2">{{ $contacto->url_publica }}</p>

            <div class="flex flex-col sm:flex-row justify-center gap-2">
                <button onclick="downloadQR()" class="px-4 py-2 bg-red-700 text-white rounded">Descargar QR (PNG)</button>
                <a href="{{ $contacto->url_publica }}" target="_blank" class="px-4 py-2 bg-gray-700 text-white rounded">Ver tarjeta pública</a>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script>
        const publicUrl = "{{ $contacto->url_publica }}";
        new QRCode(document.getElementById("qrcode"), {
            text: publicUrl,
            width: 220,
            height: 220,
        });

        function downloadQR() {
            const canvas = document.querySelector('#qrcode canvas');
            const link = document.createElement('a');
            link.download = "qr-{{ Str::slug($contacto->nombre) }}.png";
            link.href = canvas.toDataURL('image/png');
            link.click();
        }
    </script>
</x-app-layout>
