<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Faboce - Nuestras Agencias</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50">

    <header class="bg-gradient-to-r from-red-700 to-red-800 text-white">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10 sm:py-14 text-center relative">
            <a href="{{ route('login') }}"
               class="absolute top-4 right-4 text-xs sm:text-sm text-white/80 hover:text-white underline">
                Acceso administrador
            </a>
            <h1 class="text-2xl sm:text-3xl font-bold">Faboce S.R.L.</h1>
            <p class="mt-2 text-white/90 text-sm sm:text-base">
                Encuentra el punto de venta más cercano a nivel nacional
            </p>
        </div>
    </header>

    <main class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12 space-y-10">
        @forelse($agenciasPorCiudad as $ciudad => $agencias)
            <section>
                <h2 class="text-lg sm:text-xl font-bold text-gray-800 border-b-2 border-red-700 inline-block pb-1 mb-4">
                    {{ $ciudad }}
                </h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    @foreach($agencias as $agencia)
                        <div class="bg-white shadow rounded-lg p-4 flex gap-3 items-start">
                            <div class="shrink-0 mt-1 text-red-700">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M12 0c-4.198 0-8 3.403-8 7.602 0 4.198 3.469 9.21 8 16.398 4.531-7.188 8-12.2 8-16.398 0-4.199-3.801-7.602-8-7.602zm0 11c-1.657 0-3-1.343-3-3s1.343-3 3-3 3 1.343 3 3-1.343 3-3 3z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="font-semibold text-gray-800">{{ $agencia->descripcion }}</p>
                                <p class="text-sm text-gray-500">{{ $agencia->direccion }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        @empty
            <p class="text-center text-gray-400 py-12">
                Aún no hay agencias registradas.
            </p>
        @endforelse
    </main>

    <footer class="bg-white border-t py-6 text-center text-sm text-gray-500">
        © {{ date('Y') }} Faboce S.R.L. — Todos los derechos reservados
    </footer>

</body>
</html>
