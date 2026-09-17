<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Configuración laboral</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="text-sm text-gray-600">{{ $personal->nombre_completo }} · LICENSE {{ $personal->license }}</p>
                </div>
                <a href="{{ route('nomina.configuraciones-laborales.index') }}"
                    class="rounded-md bg-white px-4 py-2 text-sm font-semibold text-gray-700 ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                    Volver
                </a>
            </div>

            @if(session('success'))
                <div class="mb-4 rounded-md bg-green-50 p-4 text-sm text-green-700">
                    {{ session('success') }}
                </div>
            @endif

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="border-b border-gray-200 px-6 py-4 font-semibold text-gray-800">Trabajador</div>
                    <div class="p-6">
                        <dl class="space-y-3 text-sm">
                            <div><dt class="font-medium text-gray-500">LICENSE</dt><dd class="text-gray-900">{{ $personal->license }}</dd></div>
                            <div><dt class="font-medium text-gray-500">Nombre</dt><dd class="text-gray-900">{{ $personal->nombre }}</dd></div>
                            <div><dt class="font-medium text-gray-500">Apellido</dt><dd class="text-gray-900">{{ $personal->apellido }}</dd></div>
                            <div><dt class="font-medium text-gray-500">Estado</dt><dd class="text-gray-900">{{ $personal->estado }}</dd></div>
                        </dl>
                    </div>
                </div>

                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg lg:col-span-2">
                    <div class="border-b border-gray-200 px-6 py-4 font-semibold text-gray-800">Historial laboral local</div>
                    <div>
                        @forelse($personal->configuracionesLaborales as $config)
                            <div class="border-b border-gray-200 p-6 last:border-b-0">
                                <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
                                    <strong class="text-gray-800">Vigencia</strong>
                                    <span class="rounded-full bg-blue-100 px-2.5 py-1 text-xs font-medium text-blue-800">
                                        {{ optional($config->fecha_inicio)->format('d/m/Y') }}
                                        —
                                        {{ optional($config->fecha_fin)->format('d/m/Y') ?? 'Actual' }}
                                    </span>
                                </div>
                                <div class="grid grid-cols-1 gap-4 text-sm md:grid-cols-2">
                                    <div><span class="block text-gray-500">Área</span>{{ $config->area_nombre ?? '—' }} @if($config->area_codigo) ({{ $config->area_codigo }}) @endif</div>
                                    <div><span class="block text-gray-500">Sección</span>{{ $config->seccion_nombre ?? '—' }}</div>
                                    <div><span class="block text-gray-500">Cargo</span>{{ $config->cargo_nombre ?? '—' }}</div>
                                    <div><span class="block text-gray-500">Jerarquía</span>{{ $config->jerarquia_nombre ?? '—' }}</div>
                                    <div><span class="block text-gray-500">Agencia</span>{{ $config->agencia_nombre ?? '—' }} @if($config->agencia_codigo) ({{ $config->agencia_codigo }}) @endif</div>
                                    <div><span class="block text-gray-500">Ciudad</span>{{ $config->ciudad ?? '—' }}</div>
                                    <div><span class="block text-gray-500">Fecha ingreso</span>{{ optional($config->fecha_ingreso)->format('d/m/Y') ?? '—' }}</div>
                                    <div><span class="block text-gray-500">Fecha retiro</span>{{ optional($config->fecha_retiro)->format('d/m/Y') ?? '—' }}</div>
                                </div>
                            </div>
                        @empty
                            <div class="p-8 text-center text-sm text-gray-500">No existe una configuración laboral local.</div>
                        @endforelse
                    </div>
                    <div class="border-t border-gray-200 p-6">
                        <form action="{{ route('nomina.configuraciones-laborales.sincronizar', $personal->license) }}" method="POST">
                            @csrf
                            <button type="submit" class="rounded-md bg-green-600 px-4 py-2 text-sm font-semibold text-white hover:bg-green-500">
                                Sincronizar desde RRHH
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
