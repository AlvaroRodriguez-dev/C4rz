<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            📊 Reporte de Asistencia
        </h2>
    </x-slot>

    <div class="py-8 max-w-4xl mx-auto px-4">
        <div class="bg-white rounded-xl shadow p-6">
            <form method="POST" action="{{ route('biometricos.reporte.generar') }}">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <!-- Biométrico -->
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Biométrico</label>
                        <select name="biometrico_id" id="biometrico_id" required
                            class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="">— Seleccione —</option>
                            @foreach ($biometricos as $b)
                                <option value="{{ $b->id }}"
                                    {{ old('biometrico_id') == $b->id ? 'selected' : '' }}>
                                    {{ $b->agencia }} — {{ $b->descripcion }} ({{ $b->ip }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Fechas -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Fecha Inicial</label>
                        <input type="date" name="fecha_ini" required value="{{ old('fecha_ini', date('Y-m-01')) }}"
                            class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Fecha Final</label>
                        <input type="date" name="fecha_fin" required value="{{ old('fecha_fin', date('Y-m-d')) }}"
                            class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                    </div>

                    <!-- Usuario (Select2) -->
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Usuario <span class="text-gray-400 font-normal">(opcional — si no selecciona, muestra
                                todos)</span>
                        </label>
                        <select name="usuario_id" id="usuario_id"
                            class="w-full border-gray-300 rounded-lg shadow-sm select2-usuarios">
                            <option value="">— Todos los usuarios —</option>
                        </select>
                    </div>
                </div>

                <div class="mt-6 flex gap-3">
                    <button type="submit"
                        class="bg-indigo-600 hover:bg-indigo-700 text-black font-semibold py-2 px-6 rounded-lg transition">
                        Generar Reporte
                    </button>
                    <a href="{{ route('biometricos.reporte') }}"
                        class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold py-2 px-6 rounded-lg transition">
                        Limpiar
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Select2 -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            $('.select2-usuarios').select2({
                placeholder: '— Todos los usuarios —',
                allowClear: true,
                width: '100%'
            });

            $('#biometrico_id').on('change', function() {
                const bioId = $(this).val();
                const $sel = $('#usuario_id');
                $sel.empty().append('<option value="">— Todos los usuarios —</option>');
                if (!bioId) return;

                $.post('{{ route('biometricos.reporte.usuarios') }}', {
                        _token: '{{ csrf_token() }}',
                        biometrico_id: bioId
                    },
                    function(data) {
                        data.forEach(u => {
                            $sel.append(
                                `<option value="${u.id}">[${u.user_id}] ${u.name ?? 'Sin nombre'}</option>`
                                );
                        });
                    }
                );
            });

            // Si viene con valor previo (después de validación)
            @if (old('biometrico_id'))
                $('#biometrico_id').trigger('change');
            @endif
        });
    </script>
</x-app-layout>
