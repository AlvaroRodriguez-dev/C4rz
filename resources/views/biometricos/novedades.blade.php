<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            📋 Registro de Novedades de Asistencia
        </h2>
    </x-slot>

    <div class="py-8 max-w-4xl mx-auto px-4">
        <div class="bg-white rounded-xl shadow p-6">
            <form method="POST" action="{{ route('biometricos.novedades.generar') }}">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

                    {{-- Usuario --}}
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Usuario <span class="text-red-500">*</span>
                        </label>
                        <select name="usuario_id" id="usuario_id" required
                                class="w-full border-gray-300 rounded-lg shadow-sm select2-usuarios">
                            <option value="">— Seleccione un usuario —</option>
                            @foreach($usuarios as $u)
                                <option value="{{ $u->id }}"
                                        data-bio="{{ $u->biometrico->agencia ?? '' }}"
                                        {{ old('usuario_id') == $u->id ? 'selected' : '' }}>
                                    [{{ $u->user_id }}] {{ $u->name }}
                                    — {{ $u->biometrico->agencia ?? 'Sin biométrico' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Biométrico (opcional) --}}
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Biométrico <span class="text-gray-400 font-normal">(opcional)</span>
                        </label>
                        <select name="biometrico_id" id="biometrico_id"
                                class="w-full border-gray-300 rounded-lg shadow-sm select2-bio">
                            <option value="">— Todos los biométricos —</option>
                            @foreach($biometricos as $b)
                                <option value="{{ $b->id }}"
                                        {{ old('biometrico_id') == $b->id ? 'selected' : '' }}>
                                    {{ $b->agencia }} — {{ $b->descripcion }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Mes --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Mes <span class="text-red-500">*</span>
                        </label>
                        <select name="mes" required
                                class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                            @foreach([1=>'Enero',2=>'Febrero',3=>'Marzo',4=>'Abril',5=>'Mayo',6=>'Junio',
                                      7=>'Julio',8=>'Agosto',9=>'Septiembre',10=>'Octubre',11=>'Noviembre',12=>'Diciembre']
                                     as $num => $nombre)
                                <option value="{{ $num }}"
                                        {{ old('mes', date('n')) == $num ? 'selected' : '' }}>
                                    {{ $nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Año --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Año <span class="text-red-500">*</span>
                        </label>
                        <select name="anio" required
                                class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                            @foreach(range(date('Y'), 2020) as $y)
                                <option value="{{ $y }}"
                                        {{ old('anio', date('Y')) == $y ? 'selected' : '' }}>
                                    {{ $y }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="mt-6">
                    <button type="submit"
                            class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2.5 px-6 rounded-lg transition">
                        Generar Reporte de Novedades
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Select2 --}}
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet"/>
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
    $(document).ready(function () {
        $('.select2-usuarios').select2({
            placeholder: '— Seleccione un usuario —',
            allowClear: true,
            width: '100%',
        });
        $('.select2-bio').select2({
            placeholder: '— Todos los biométricos —',
            allowClear: true,
            width: '100%',
        });
    });
    </script>
</x-app-layout>