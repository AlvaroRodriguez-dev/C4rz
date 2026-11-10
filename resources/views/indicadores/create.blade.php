<x-app-layout>
<x-slot name="header"><div><h2 class="text-xl font-semibold text-gray-800">Importar inventario OCS</h2><p class="text-sm text-gray-500">Cargar el inventario mensual y calcular los indicadores de gestión</p></div></x-slot>
<div class="py-8"><div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
@if($errors->any())<div class="mb-4 rounded-lg bg-red-50 border border-red-200 p-4 text-sm text-red-700"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<div class="bg-white shadow-sm rounded-xl p-6">
<form method="POST" action="{{ route('indicadores.store') }}" enctype="multipart/form-data" class="space-y-5">@csrf
<div><label class="block text-sm font-medium text-gray-700 mb-1">Archivo de OCS Inventory (CSV)</label><input type="file" name="archivo" accept=".csv,.txt,text/csv" required class="block w-full text-sm border border-gray-300 rounded-lg p-2"><p class="text-xs text-gray-500 mt-1">Se admite CSV separado por coma o punto y coma. Tamaño máximo: 20 MB.</p></div>
<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
<div><label class="block text-sm font-medium text-gray-700 mb-1">Mes del inventario</label><select name="mes" required class="w-full rounded-lg border-gray-300">@foreach(range(1,12) as $m)<option value="{{ $m }}" @selected(old('mes',now()->month)==$m)>{{ \Carbon\Carbon::create()->month($m)->locale('es')->monthName }}</option>@endforeach</select></div>
<div><label class="block text-sm font-medium text-gray-700 mb-1">Año</label><input type="number" name="anio" min="2020" max="2100" value="{{ old('anio',now()->year) }}" required class="w-full rounded-lg border-gray-300"></div>
</div>
<div class="rounded-lg bg-blue-50 border border-blue-100 p-4 text-sm text-blue-900"><strong>Reglas aplicadas</strong><ul class="list-disc ml-5 mt-2 space-y-1"><li>Todos los registros del CSV se consideran activos.</li><li>Para los indicadores se consideran computadoras Windows de escritorio/portátiles; Android y Windows Server quedan separados.</li><li>La antigüedad utiliza la columna opcional “Fecha aproximada de compra”. Si falta, se informa como dato pendiente.</li><li>Los sistemas operativos cuya versión exacta no permite confirmar el soporte quedan como “Pendiente de verificación”, no se cuentan automáticamente como sin soporte.</li></ul></div>
<div class="flex justify-end gap-3"><a href="{{ route('indicadores.index') }}" class="px-4 py-2 text-sm text-gray-600">Cancelar</a><button class="px-5 py-2 bg-blue-700 hover:bg-blue-800 text-white rounded-lg text-sm font-semibold">Procesar inventario</button></div>
</form></div></div></div>
</x-app-layout>