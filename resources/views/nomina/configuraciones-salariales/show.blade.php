<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Configuración salarial</h2>
            <a href="{{ route('nomina.configuraciones-salariales.index') }}" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">Volver</a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @if (session('success'))
                <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">{{ session('error') }}</div>
            @endif

            <div class="text-gray-700">
                <span class="font-medium">{{ $personal->nombre_completo }}</span>
                · LICENSE {{ $personal->license }}
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h3 class="font-semibold text-gray-800 mb-5">Trabajador</h3>
                    <dl class="space-y-4">
                        <div><dt class="text-sm text-gray-500">LICENSE</dt><dd class="font-medium">{{ $personal->license }}</dd></div>
                        <div><dt class="text-sm text-gray-500">Nombre</dt><dd>{{ $personal->nombre }}</dd></div>
                        <div><dt class="text-sm text-gray-500">Apellido</dt><dd>{{ $personal->apellido }}</dd></div>
                        <div><dt class="text-sm text-gray-500">Estado</dt><dd>{{ $personal->estado }}</dd></div>
                    </dl>
                </div>

                <div class="lg:col-span-2 bg-white shadow-sm sm:rounded-lg p-6">
                    <h3 class="font-semibold text-gray-800 mb-5">Nueva configuración / actualización</h3>
                    <form method="POST" action="{{ route('nomina.configuraciones-salariales.store', $personal) }}" class="space-y-5">
                        @csrf
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Fecha de inicio</label>
                                <input type="date" name="fecha_inicio" value="{{ old('fecha_inicio', optional($personal->configuracionesSalariales->first())->fecha_inicio?->format('Y-m-d') ?? now()->format('Y-m-d')) }}" required
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @error('fecha_inicio')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Haber básico</label>
                                <input type="number" name="haber_basico" min="0" step="0.01" value="{{ old('haber_basico', optional($personal->configuracionesSalariales->first())->haber_basico) }}" required
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @error('haber_basico')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Categoría</label>
                                <select name="categoria_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">Sin categoría</option>
                                    @foreach ($categorias as $categoria)
                                        <option value="{{ $categoria->id }}" @selected(old('categoria_id', optional($personal->configuracionesSalariales->first())->categoria_id) == $categoria->id)>
                                            {{ $categoria->codigo }} — {{ $categoria->nombre }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Modalidad de remuneración</label>
                                <input type="text" name="modalidad_remuneracion" maxlength="80" value="{{ old('modalidad_remuneracion', optional($personal->configuracionesSalariales->first())->modalidad_remuneracion) }}"
                                    placeholder="Mensual, jornal, etc."
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Salario cotizable</label>
                                <input type="number" name="salario_cotizable" min="0" step="0.01" value="{{ old('salario_cotizable', optional($personal->configuracionesSalariales->first())->salario_cotizable) }}"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700">Observaciones</label>
                                <textarea name="observaciones" rows="3" maxlength="5000"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('observaciones', optional($personal->configuracionesSalariales->first())->observaciones) }}</textarea>
                            </div>
                        </div>
                        <div class="flex justify-end">
                            <button type="submit" class="px-5 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">Guardar configuración salarial</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <div class="p-6 border-b border-gray-200">
                    <h3 class="font-semibold text-gray-800">Historial salarial</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Vigencia</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Haber básico</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Categoría</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Modalidad</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Salario cotizable</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse ($personal->configuracionesSalariales as $config)
                                <tr>
                                    <td class="px-6 py-4">{{ $config->fecha_inicio?->format('d/m/Y') }} — {{ $config->fecha_fin?->format('d/m/Y') ?? 'Actual' }}</td>
                                    <td class="px-6 py-4 font-medium">{{ number_format((float) $config->haber_basico, 2, '.', ',') }}</td>
                                    <td class="px-6 py-4">{{ $config->categoria?->nombre ?? '—' }}</td>
                                    <td class="px-6 py-4">{{ $config->modalidad_remuneracion ?? '—' }}</td>
                                    <td class="px-6 py-4">{{ $config->salario_cotizable !== null ? number_format((float) $config->salario_cotizable, 2, '.', ',') : '—' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-6 py-8 text-center text-gray-500">Todavía no existe una configuración salarial.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
