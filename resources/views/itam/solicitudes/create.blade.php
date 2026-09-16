<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800">Nueva solicitud de TI</h2></x-slot>

    <div class="py-6 max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        @if($errors->any())
            <div class="mb-4 p-4 bg-red-100 text-red-700 rounded-lg"><ul class="list-disc ml-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        @endif

        <form method="POST" action="{{ route('itam.solicitudes.store') }}" x-data="solicitudForm()" class="space-y-6">
            @csrf
            <div class="bg-white shadow rounded-lg p-5">
                <h3 class="font-semibold text-gray-800 mb-4">Datos de la solicitud</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700">Solicitante</label>
                        <input type="hidden" name="solicitante_id" x-model="personal.id">
                        <input type="text" x-model="personal.label" @input.debounce.400ms="buscarPersonal" placeholder="Buscar por nombre, apellido o licencia" class="mt-1 w-full rounded-lg border-gray-300">
                        <div x-show="resultados.length" class="relative">
                            <div class="absolute z-20 w-full bg-white border rounded-lg shadow mt-1 max-h-60 overflow-auto">
                                <template x-for="persona in resultados" :key="persona.id">
                                    <button type="button" @click="seleccionar(persona)" class="block w-full text-left px-4 py-2 hover:bg-gray-50">
                                        <span x-text="persona.lastname + ', ' + persona.name"></span>
                                        <span class="text-xs text-gray-500" x-text="persona.licence_id ? ' · ' + persona.licence_id : ''"></span>
                                    </button>
                                </template>
                            </div>
                        </div>
                    </div>
                    <input type="hidden" name="area_id" x-model="personal.area_id">
                    <div><label class="block text-sm font-medium text-gray-700">Ubicación</label><select name="ubicacion_id" class="mt-1 w-full rounded-lg border-gray-300"><option value="">Seleccione...</option>@foreach($ubicaciones as $ubicacion)<option value="{{ $ubicacion->id }}">{{ $ubicacion->codigo }} - {{ $ubicacion->descripcion }}</option>@endforeach</select></div>
                    <div><label class="block text-sm font-medium text-gray-700">Fecha</label><input type="date" name="fecha_solicitud" value="{{ old('fecha_solicitud', now()->toDateString()) }}" class="mt-1 w-full rounded-lg border-gray-300"></div>
                    <div><label class="block text-sm font-medium text-gray-700">Prioridad</label><select name="prioridad" class="mt-1 w-full rounded-lg border-gray-300"><option>BAJA</option><option selected>NORMAL</option><option>ALTA</option><option>URGENTE</option></select></div>
                    <div><label class="block text-sm font-medium text-gray-700">Motivo</label><input name="motivo" value="{{ old('motivo') }}" maxlength="255" class="mt-1 w-full rounded-lg border-gray-300"></div>
                    <div class="md:col-span-2"><label class="block text-sm font-medium text-gray-700">Descripción general</label><textarea name="descripcion" rows="3" class="mt-1 w-full rounded-lg border-gray-300">{{ old('descripcion') }}</textarea></div>
                </div>
            </div>

            <div class="bg-white shadow rounded-lg p-5">
                <div class="flex justify-between items-center mb-4"><h3 class="font-semibold text-gray-800">Requerimientos</h3><button type="button" @click="agregar()" class="px-3 py-2 bg-gray-800 text-white rounded-lg text-sm">+ Agregar</button></div>
                <div class="space-y-4">
                    <template x-for="(detalle, index) in detalles" :key="index">
                        <div class="border rounded-lg p-4 grid grid-cols-1 md:grid-cols-12 gap-3">
                            <div class="md:col-span-2"><label class="text-xs font-semibold text-gray-500">TIPO</label><select :name="`detalles[${index}][tipo_item]`" x-model="detalle.tipo_item" class="mt-1 w-full rounded-lg border-gray-300"><option>ACTIVO</option><option>COMPONENTE</option><option>ACCESORIO</option><option>SERVICIO</option></select></div>
                            <div class="md:col-span-3"><label class="text-xs font-semibold text-gray-500">TIPO DE ACTIVO</label><select :name="`detalles[${index}][tipo_activo_id]`" x-model="detalle.tipo_activo_id" class="mt-1 w-full rounded-lg border-gray-300"><option value="">No aplica</option>@foreach($tiposActivo as $tipo)<option value="{{ $tipo->id }}">{{ $tipo->descripcion }}</option>@endforeach</select></div>
                            <div class="md:col-span-4"><label class="text-xs font-semibold text-gray-500">DESCRIPCIÓN</label><input :name="`detalles[${index}][descripcion_solicitada]`" x-model="detalle.descripcion_solicitada" required maxlength="255" class="mt-1 w-full rounded-lg border-gray-300"></div>
                            <div class="md:col-span-1"><label class="text-xs font-semibold text-gray-500">CANT.</label><input type="number" min="1" :name="`detalles[${index}][cantidad]`" x-model="detalle.cantidad" required class="mt-1 w-full rounded-lg border-gray-300"></div>
                            <div class="md:col-span-2 flex items-end"><button type="button" @click="quitar(index)" x-show="detalles.length > 1" class="text-red-600 text-sm">Eliminar</button></div>
                            <div class="md:col-span-12"><label class="text-xs font-semibold text-gray-500">ESPECIFICACIONES</label><textarea :name="`detalles[${index}][especificaciones]`" x-model="detalle.especificaciones" rows="2" class="mt-1 w-full rounded-lg border-gray-300"></textarea></div>
                        </div>
                    </template>
                </div>
            </div>

            <div class="flex justify-end gap-3"><a href="{{ route('itam.solicitudes.index') }}" class="px-4 py-2 rounded-lg border bg-white">Cancelar</a><button class="px-5 py-2 bg-red-700 hover:bg-red-800 text-white rounded-lg font-semibold">Registrar solicitud</button></div>
        </form>
    </div>

    @push('scripts')
    <script>
        function solicitudForm() {
            return {
                personal: {id:'', label:'', area_id:''}, resultados: [],
                detalles: [{tipo_item:'ACTIVO', tipo_activo_id:'', descripcion_solicitada:'', cantidad:1, especificaciones:''}],
                async buscarPersonal() {
                    if (this.personal.label.length < 2) { this.resultados=[]; return; }
                    const r = await fetch(`{{ route('itam.solicitudes.personal.buscar') }}?q=${encodeURIComponent(this.personal.label)}`, {headers:{'Accept':'application/json'}});
                    this.resultados = await r.json();
                },
                seleccionar(p) { this.personal={id:p.id,label:(p.lastname+', '+p.name),area_id:p.area_id || ''}; this.resultados=[]; },
                agregar() { this.detalles.push({tipo_item:'ACTIVO',tipo_activo_id:'',descripcion_solicitada:'',cantidad:1,especificaciones:''}); },
                quitar(i) { this.detalles.splice(i,1); }
            }
        }
    </script>
    @endpush
</x-app-layout>
