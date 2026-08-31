<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-6 sm:py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- Accesos directos --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <a href="{{ route('comercial.contactos.index') }}"
                   class="flex items-center gap-4 bg-white shadow rounded-lg p-5 hover:shadow-md transition">
                    <div class="shrink-0 w-12 h-12 rounded-full bg-red-100 text-red-700 flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 12c2.761 0 5-2.239 5-5s-2.239-5-5-5-5 2.239-5 5 2.239 5 5 5zm0 2c-3.866 0-11 1.940-11 5.8v2.2h22v-2.2c0-3.86-7.134-5.8-11-5.8z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="font-semibold text-gray-800">Contactos</p>
                        <p class="text-sm text-gray-500">Gestionar ejecutivos de venta</p>
                    </div>
                    <span class="ml-auto text-2xl font-bold text-gray-300">{{ $totalContactos }}</span>
                </a>

                <a href="{{ route('comercial.agencias.index') }}"
                   class="flex items-center gap-4 bg-white shadow rounded-lg p-5 hover:shadow-md transition">
                    <div class="shrink-0 w-12 h-12 rounded-full bg-red-100 text-red-700 flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 0c-4.198 0-8 3.403-8 7.602 0 4.198 3.469 9.21 8 16.398 4.531-7.188 8-12.2 8-16.398 0-4.199-3.801-7.602-8-7.602zm0 11c-1.657 0-3-1.343-3-3s1.343-3 3-3 3 1.343 3 3-1.343 3-3 3z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="font-semibold text-gray-800">Agencias</p>
                        <p class="text-sm text-gray-500">Gestionar puntos de venta</p>
                    </div>
                    <span class="ml-auto text-2xl font-bold text-gray-300">{{ $totalAgencias }}</span>
                </a>
            </div>

            {{-- Gráficos --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                <div class="bg-white shadow rounded-lg p-4 sm:p-6">
                    <h3 class="font-semibold text-gray-700 mb-4">Contactos por Agencia</h3>
                    @if($porAgencia->isEmpty())
                        <p class="text-sm text-gray-400 text-center py-8">Aún no hay contactos registrados.</p>
                    @else
                        <div class="relative h-40 sm:h-48 max-w-sm mx-auto">
                            <canvas id="chartAgencia"></canvas>
                        </div>
                    @endif
                </div>

                <div class="bg-white shadow rounded-lg p-4 sm:p-6">
                    <h3 class="font-semibold text-gray-700 mb-4">Contactos por Ciudad</h3>
                    @if($porCiudad->isEmpty())
                        <p class="text-sm text-gray-400 text-center py-8">Aún no hay contactos registrados.</p>
                    @else
                        <div class="relative h-40 sm:h-48 max-w-sm mx-auto">
                            <canvas id="chartCiudad"></canvas>
                        </div>
                    @endif
                </div>
            </div>

        </div>
    </div>

    @if($porAgencia->isNotEmpty() || $porCiudad->isNotEmpty())
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
    <script>
        const paletaRoja = ['#e40037', '#c03e4a', '#a53347', '#8a2b40', '#6f2338', '#54182c', '#7a3b52', '#9c5060'];

        @if($porAgencia->isNotEmpty())
        new Chart(document.getElementById('chartAgencia'), {
            type: 'bar',
            data: {
                labels: {!! json_encode($porAgencia->pluck('label')) !!},
                datasets: [{
                    label: 'Contactos',
                    data: {!! json_encode($porAgencia->pluck('total')) !!},
                    backgroundColor: paletaRoja,
                    borderRadius: 4,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, ticks: { precision: 0 } },
                    x: { ticks: { autoSkip: false, maxRotation: 45, minRotation: 0 } }
                }
            }
        });
        @endif

        @if($porCiudad->isNotEmpty())
        new Chart(document.getElementById('chartCiudad'), {
            type: 'doughnut',
            data: {
                labels: {!! json_encode($porCiudad->pluck('ciudad')) !!},
                datasets: [{
                    data: {!! json_encode($porCiudad->pluck('total')) !!},
                    backgroundColor: paletaRoja,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } }
            }
        });
        @endif
    </script>
    @endif
</x-app-layout>
