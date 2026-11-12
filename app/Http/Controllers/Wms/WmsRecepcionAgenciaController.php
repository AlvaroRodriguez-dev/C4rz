<?php

namespace App\Http\Controllers\Wms;

use App\Http\Controllers\Controller;
use App\Models\WmsIngreso;
use App\Models\WmsSalida;
use App\Models\WmsUbicacion;
use App\Services\WmsContextService;
use App\Services\WmsStockLockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WmsRecepcionAgenciaController extends Controller
{
    public function __construct(
        private WmsContextService $context,
        private WmsStockLockService $stockLock
    ) {}

    public function index()
    {
        $almacen = $this->context->almacenPrincipal();

        if (!$almacen) {
            return view('wms.recepcion-agencias.index', [
                'almacen' => null,
                'recepciones' => collect(),
                'advertenciaAlmacen' => 'No tienes un almacén principal WMS activo asignado. Solicita al administrador que configure tu almacén principal. Por seguridad, no podrás realizar registros hasta que se complete esta asignación.',
            ]);
        }

        // Resumen agregado por recepción para evitar seleccionar r.* dentro
        // de una consulta agrupada incompatible con ONLY_FULL_GROUP_BY.
        $resumenPallets = DB::table('wms_recepcion_agencia_pallets')
            ->select('recepcion_id')
            ->selectRaw('COUNT(id) as total_pallets')
            ->selectRaw("SUM(CASE WHEN estado = 'ingresado' THEN 1 ELSE 0 END) as pallets_ingresados")
            ->selectRaw("SUM(CASE WHEN estado = 'observado' THEN 1 ELSE 0 END) as pallets_observados")
            ->groupBy('recepcion_id');

        $recepciones = DB::table('wms_recepciones_agencia as r')
            ->leftJoinSub($resumenPallets, 'p', function ($join) {
                $join->on('p.recepcion_id', '=', 'r.id');
            })
            ->where('r.almacen_id', $almacen->id)
            ->select(
                'r.*',
                DB::raw('COALESCE(p.total_pallets, 0) as total_pallets'),
                DB::raw('COALESCE(p.pallets_ingresados, 0) as pallets_ingresados'),
                DB::raw('COALESCE(p.pallets_observados, 0) as pallets_observados')
            )
            ->orderByDesc('r.id')
            ->limit(50)
            ->get();

        return view('wms.recepcion-agencias.index', compact('almacen', 'recepciones'));
    }

    public function show(int $recepcion)
    {
        $cabecera = $this->recepcion($recepcion);
        $pallets = DB::table('wms_recepcion_agencia_pallets as p')
            ->join('wms_recepcion_agencia_documentos as d', 'd.id', '=', 'p.documento_id')
            ->leftJoin('wms_ubicaciones as ut', 'ut.id', '=', 'p.ubicacion_temporal_id')
            ->leftJoin('wms_ubicaciones as uf', 'uf.id', '=', 'p.ubicacion_final_id')
            ->where('p.recepcion_id', $cabecera->id)
            ->select('p.*', 'd.id_registro', 'd.tipo_registro', 'ut.codigo as ubicacion_temporal',
                'uf.codigo as ubicacion_final')
            ->orderBy('d.id_registro')->orderBy('p.pallet')->get();

        $documentos = DB::table('wms_recepcion_agencia_documentos')
            ->where('recepcion_id', $cabecera->id)->get();

        return view('wms.recepcion-agencias.show', compact('cabecera', 'pallets', 'documentos'));
    }

    public function buscarDocumentos(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));
        $almacen = $this->almacenPrincipalOrFail()->codigo;

        $notas = DB::connection('faboce2026')->table('log_registro')
            ->whereBetween('tipo_registro', [1, 3])
            ->where('destino', $almacen)
            ->when($q !== '', fn ($query) => $query->where('id', 'like', "%{$q}%"))
            ->select('id', 'tipo_registro', 'destino', 'glosa', 'fecha')
            ->orderByDesc('id')->limit(30)->get();

        $results = $notas->map(function ($nota) {
            $tipo = (string) $nota->tipo_registro;
            $id = (string) $nota->id;
            $despachados = WmsSalida::query()->where('tipo_registro', $tipo)
                ->where('id_registro', $id)->distinct()->pluck('pallet');
            $sinDespacho = $despachados->isEmpty();
            $yaRecibidos = DB::table('wms_recepcion_agencia_pallets')
                ->where('tipo_registro', $tipo)->where('id_registro', $id)->pluck('pallet');
            $pallets = $despachados->diff($yaRecibidos)->count();
            $estadoDespacho = $sinDespacho
                ? ' · SIN DESPACHO FISICO'
                : " · {$pallets} pallets pendientes";

            return [
                'id' => $id,
                'tipo_registro' => $tipo,
                'text' => "Tipo {$tipo} · #{$id} · " . ($nota->fecha ? date('d/m/Y', strtotime($nota->fecha)) : 'S/F') . " · {$nota->glosa}{$estadoDespacho}",
                'pallets' => $pallets,
                'sin_despacho' => $sinDespacho,
                'disabled' => $sinDespacho || $pallets === 0,
            ];
        })->filter(fn ($item) => $item['sin_despacho'] || $item['pallets'] > 0)->values();

        return response()->json(['results' => $results]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'camion' => ['nullable', 'string', 'max:80'],
            'observacion' => ['nullable', 'string', 'max:500'],
            'documentos' => ['required', 'array', 'min:1', 'max:10'],
            'documentos.*' => ['required', 'string', 'max:20'],
        ]);

        $almacen = $this->almacenPrincipalOrFail();

        return DB::transaction(function () use ($data, $almacen) {
            $notas = DB::connection('faboce2026')->table('log_registro')
                ->whereIn('id', $data['documentos'])
                ->whereBetween('tipo_registro', [1, 3])
                ->where('destino', $almacen->codigo)
                ->get()->keyBy(fn ($row) => (string) $row->id);

            if ($notas->count() !== count(array_unique($data['documentos']))) {
                throw ValidationException::withMessages(['documentos' => 'Una o más notas no pertenecen a la agencia o ya no están disponibles.']);
            }

            foreach ($notas as $nota) {
                $salidas = WmsSalida::query()->where('tipo_registro', (string) $nota->tipo_registro)
                    ->where('id_registro', (string) $nota->id)->orderBy('pallet')->orderBy('codigo')->get();

                $palletsYaRecibidos = DB::table('wms_recepcion_agencia_pallets')
                    ->where('tipo_registro', (string) $nota->tipo_registro)
                    ->where('id_registro', (string) $nota->id)->pluck('pallet');

                $palletsPendientes = $salidas->groupBy('pallet')
                    ->reject(fn ($lineas, $pallet) => $palletsYaRecibidos->contains((string) $pallet));

                if ($palletsPendientes->isEmpty()) {
                    throw ValidationException::withMessages(['documentos' => "La nota {$nota->id} no tiene pallets pendientes de recepción."]);
                }
            }

            $recepcionId = DB::table('wms_recepciones_agencia')->insertGetId([
                'almacen_id' => $almacen->id,
                'camion' => $data['camion'] ?? null,
                'fecha_recepcion' => now(),
                'estado' => 'abierta',
                'observacion' => $data['observacion'] ?? null,
                'created_id' => auth()->id(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($notas as $nota) {
                $documentoId = DB::table('wms_recepcion_agencia_documentos')->insertGetId([
                    'recepcion_id' => $recepcionId,
                    'tipo_registro' => (string) $nota->tipo_registro,
                    'id_registro' => (string) $nota->id,
                    'glosa' => $nota->glosa,
                    'fecha_documento' => $nota->fecha,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $salidas = WmsSalida::query()->where('tipo_registro', (string) $nota->tipo_registro)
                    ->where('id_registro', (string) $nota->id)->orderBy('pallet')->orderBy('codigo')->get();
                $palletsYaRecibidos = DB::table('wms_recepcion_agencia_pallets')
                    ->where('tipo_registro', (string) $nota->tipo_registro)
                    ->where('id_registro', (string) $nota->id)->pluck('pallet');

                $palletsPendientes = $salidas->groupBy('pallet')
                    ->reject(fn ($lineas, $pallet) => $palletsYaRecibidos->contains((string) $pallet));

                foreach ($palletsPendientes as $numeroPallet => $lineas) {
                    $palletId = DB::table('wms_recepcion_agencia_pallets')->insertGetId([
                        'recepcion_id' => $recepcionId,
                        'documento_id' => $documentoId,
                        'tipo_registro' => (string) $nota->tipo_registro,
                        'id_registro' => (string) $nota->id,
                        'pallet' => (string) $numeroPallet,
                        'estado' => 'pendiente_descarga',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    foreach ($lineas as $linea) {
                        DB::table('wms_recepcion_agencia_detalles')->insert([
                            'pallet_id' => $palletId,
                            'codigo_esperado' => $linea->codigo,
                            'lote_esperado' => $linea->clote,
                            'cantidad_esperada' => (int) $linea->cantidad,
                            'descrip' => $linea->descrip,
                            'descrip1' => $linea->descrip1,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }

            return response()->json([
                'ok' => true,
                'message' => 'Recepción creada. Los pallets quedaron pendientes de descarga.',
                'recepcion_id' => $recepcionId,
                'url' => route('wms.recepcion-agencias.show', $recepcionId),
            ], 201);
        });
    }

    public function detallePallet(int $recepcion, int $pallet): JsonResponse
    {
        $cabecera = $this->recepcion($recepcion);
        $p = $this->pallet($cabecera->id, $pallet);
        $detalles = DB::table('wms_recepcion_agencia_detalles')->where('pallet_id', $p->id)->orderBy('id')->get();
        $observaciones = DB::table('wms_recepcion_agencia_observaciones')
            ->where('pallet_id', $p->id)->orderByDesc('id')->get();

        return response()->json(['pallet' => $p, 'detalles' => $detalles, 'observaciones' => $observaciones]);
    }

    public function buscarPallets(Request $request, int $recepcion): JsonResponse
    {
        $cabecera = $this->recepcion($recepcion);
        $q = trim((string) $request->query('q', ''));
        $rows = DB::table('wms_recepcion_agencia_pallets')
            ->where('recepcion_id', $cabecera->id)
            ->when($q !== '', fn ($query) => $query->where('pallet', 'like', "%{$q}%"))
            ->orderBy('pallet')->limit(30)->get();

        return response()->json(['results' => $rows->map(fn ($p) => [
            'id' => $p->id, 'pallet' => $p->pallet, 'text' => "{$p->pallet} · " . strtoupper(str_replace('_', ' ', $p->estado)),
        ])]);
    }

    public function ubicacionesTemporales(): JsonResponse
    {
        $almacen = $this->almacenPrincipalOrFail();
        $items = WmsUbicacion::query()->where('almacen_id', $almacen->id)
            ->where('tipo', 'RECEPCION')->where('activo', true)->orderBy('codigo')->get();

        return response()->json(['results' => $items->map(fn ($u) => [
            'id' => $u->id, 'text' => $u->codigo . ' · ' . ($u->galpon?->codigo ?? 'Zona temporal'),
        ])]);
    }

    public function ubicacionesNormales(): JsonResponse
    {
        $almacen = $this->almacenPrincipalOrFail();
        $items = WmsUbicacion::query()->with('galpon')->where('almacen_id', $almacen->id)
            ->where('tipo', 'NORMAL')->where('activo', true)
            ->whereHas('galpon', fn ($q) => $q->where('activo', true))
            ->orderBy('codigo')->get();

        return response()->json(['results' => $items->map(fn ($u) => [
            'id' => $u->id, 'text' => $u->galpon->codigo . ' / ' . $u->codigo,
            'galpon' => $u->galpon->codigo, 'ubicacion' => $u->codigo,
        ])]);
    }

    public function descargar(Request $request, int $recepcion, int $pallet): JsonResponse
    {
        $data = $request->validate(['ubicacion_temporal_id' => ['required', 'integer']]);
        $cabecera = $this->recepcion($recepcion);

        $actualizado = DB::transaction(function () use ($cabecera, $pallet, $data) {
            $p = DB::table('wms_recepcion_agencia_pallets')->where('recepcion_id', $cabecera->id)
                ->where('id', $pallet)->lockForUpdate()->first();
            if (!$p) abort(404);
            if ($cabecera->estado !== 'abierta' || !in_array($p->estado, ['pendiente_descarga', 'descargado'], true)) {
                throw ValidationException::withMessages(['pallet' => 'La recepción está cerrada o el pallet ya pasó la etapa de descarga.']);
            }

            $ubicacion = WmsUbicacion::query()->where('id', $data['ubicacion_temporal_id'])
                ->where('almacen_id', $cabecera->almacen_id)->where('tipo', 'RECEPCION')
                ->where('activo', true)->first();
            if (!$ubicacion) throw ValidationException::withMessages(['ubicacion_temporal_id' => 'La ubicación temporal no es válida para este almacén.']);

            DB::table('wms_recepcion_agencia_pallets')->where('id', $p->id)->update([
                'ubicacion_temporal_id' => $ubicacion->id, 'estado' => 'descargado',
                'descargado_at' => now(), 'descargado_por' => auth()->id(), 'updated_at' => now(),
            ]);
            return true;
        });

        return response()->json(['ok' => $actualizado, 'message' => 'Descarga registrada.']);
    }

    public function verificar(Request $request, int $recepcion, int $pallet): JsonResponse
    {
        $data = $request->validate([
            'detalles' => ['required', 'array', 'min:1'],
            'detalles.*.id' => ['nullable', 'integer'],
            'detalles.*.codigo_fisico' => ['required', 'string', 'max:30'],
            'detalles.*.lote_fisico' => ['nullable', 'string', 'max:30'],
            'detalles.*.cantidad_fisica' => ['required', 'integer', 'min:0'],
            'detalles.*.observacion' => ['nullable', 'string', 'max:500'],
            'observacion_general' => ['nullable', 'string', 'max:1000'],
        ]);

        $cabecera = $this->recepcion($recepcion);

        return DB::transaction(function () use ($cabecera, $pallet, $data) {
            $recepcionActual = DB::table('wms_recepciones_agencia')->where('id', $cabecera->id)->lockForUpdate()->first();
            if (!$recepcionActual || $recepcionActual->estado !== 'abierta') {
                throw ValidationException::withMessages(['recepcion' => 'La recepción está cerrada.']);
            }
            $p = DB::table('wms_recepcion_agencia_pallets')->where('recepcion_id', $cabecera->id)
                ->where('id', $pallet)->lockForUpdate()->first();
            if (!$p) abort(404);
            if ($cabecera->estado !== 'abierta' || !$p->ubicacion_temporal_id) {
                throw ValidationException::withMessages(['pallet' => 'Primero descarga el pallet en una ubicación temporal.']);
            }
            if ($p->estado === 'ingresado') throw ValidationException::withMessages(['pallet' => 'El pallet ya tiene ingreso definitivo.']);

            $esperados = DB::table('wms_recepcion_agencia_detalles')->where('pallet_id', $p->id)->get()->keyBy('id');
            $enviados = collect($data['detalles']);
            $ids = $enviados->pluck('id')->filter()->map(fn ($id) => (int) $id);
            if ($esperados->keys()->diff($ids)->isNotEmpty()) {
                throw ValidationException::withMessages(['detalles' => 'Debes verificar todas las líneas esperadas del pallet.']);
            }

            // Una nueva verificación sustituye las observaciones pendientes anteriores.
            // Se conserva el historial marcándolas como resueltas, en lugar de borrarlas.
            DB::table('wms_recepcion_agencia_observaciones')
                ->where('pallet_id', $p->id)->where('estado', 'pendiente')
                ->update([
                    'estado' => 'resuelta',
                    'comentario_autorizacion' => 'Revisada durante una nueva verificación física.',
                    'updated_at' => now(),
                ]);

            $hayDiferencias = trim((string) ($data['observacion_general'] ?? '')) !== '';
            foreach ($enviados as $item) {
                if (!empty($item['id'])) {
                    $esperado = $esperados->get((int) $item['id']);
                    if (!$esperado) throw ValidationException::withMessages(['detalles' => 'Una línea no pertenece al pallet seleccionado.']);
                    $difiere = strtoupper(trim($item['codigo_fisico'])) !== strtoupper(trim($esperado->codigo_esperado))
                        || strtoupper(trim((string) ($item['lote_fisico'] ?? ''))) !== strtoupper(trim((string) $esperado->lote_esperado))
                        || (int) $item['cantidad_fisica'] !== (int) $esperado->cantidad_esperada
                        || trim((string) ($item['observacion'] ?? '')) !== '';
                    $hayDiferencias = $hayDiferencias || $difiere;
                    DB::table('wms_recepcion_agencia_detalles')->where('id', $esperado->id)->update([
                        'codigo_fisico' => strtoupper(trim($item['codigo_fisico'])),
                        'lote_fisico' => trim((string) ($item['lote_fisico'] ?? '')),
                        'cantidad_fisica' => (int) $item['cantidad_fisica'],
                        'observacion' => $item['observacion'] ?? null,
                        'updated_at' => now(),
                    ]);
                    if ($difiere) {
                        DB::table('wms_recepcion_agencia_observaciones')->insert([
                            'pallet_id' => $p->id, 'detalle_id' => $esperado->id,
                            'tipo' => 'diferencia_producto_lote_cantidad',
                            'descripcion' => trim((string) ($item['observacion'] ?? '')) ?: 'Diferencia detectada respecto al despacho.',
                            'estado' => 'pendiente', 'created_at' => now(), 'updated_at' => now(),
                        ]);
                    }
                } else {
                    $hayDiferencias = true;
                    $detailId = DB::table('wms_recepcion_agencia_detalles')->insertGetId([
                        'pallet_id' => $p->id, 'codigo_fisico' => strtoupper(trim($item['codigo_fisico'])),
                        'lote_fisico' => trim((string) ($item['lote_fisico'] ?? '')),
                        'cantidad_fisica' => (int) $item['cantidad_fisica'],
                        'observacion' => $item['observacion'] ?? 'Producto adicional no declarado en despacho.',
                        'es_adicional' => true, 'created_at' => now(), 'updated_at' => now(),
                    ]);
                    DB::table('wms_recepcion_agencia_observaciones')->insert([
                        'pallet_id' => $p->id, 'detalle_id' => $detailId,
                        'tipo' => 'producto_adicional',
                        'descripcion' => $item['observacion'] ?? 'Producto adicional no declarado en despacho.',
                        'estado' => 'pendiente', 'created_at' => now(), 'updated_at' => now(),
                    ]);
                }
            }

            if (trim((string) ($data['observacion_general'] ?? '')) !== '') {
                DB::table('wms_recepcion_agencia_observaciones')->insert([
                    'pallet_id' => $p->id, 'tipo' => 'observacion_general',
                    'descripcion' => trim($data['observacion_general']),
                    'estado' => 'pendiente', 'created_at' => now(), 'updated_at' => now(),
                ]);
            }

            DB::table('wms_recepcion_agencia_pallets')->where('id', $p->id)->update([
                'estado' => $hayDiferencias ? 'observado' : 'verificado',
                'verificado_at' => now(), 'verificado_por' => auth()->id(), 'updated_at' => now(),
            ]);

            return response()->json([
                'ok' => true,
                'estado' => $hayDiferencias ? 'observado' : 'verificado',
                'message' => $hayDiferencias
                    ? 'Se registró la verificación con observaciones. Este pallet queda bloqueado; los demás pueden continuar.'
                    : 'Pallet verificado correctamente y habilitado para ubicación definitiva.',
            ]);
        });
    }

    public function autorizar(int $recepcion, int $pallet): JsonResponse
    {
        abort_unless(auth()->user()->hasAnyRole(['WMS-ADMIN', 'SIS-ADMIN']), 403);
        $cabecera = $this->recepcion($recepcion);

        return DB::transaction(function () use ($cabecera, $pallet) {
            $recepcionActual = DB::table('wms_recepciones_agencia')->where('id', $cabecera->id)->lockForUpdate()->first();
            if (!$recepcionActual || $recepcionActual->estado !== 'abierta') {
                throw ValidationException::withMessages(['recepcion' => 'La recepción está cerrada.']);
            }
            $p = DB::table('wms_recepcion_agencia_pallets')->where('recepcion_id', $cabecera->id)
                ->where('id', $pallet)->lockForUpdate()->first();
            if (!$p || $p->estado !== 'observado') throw ValidationException::withMessages(['pallet' => 'El pallet no tiene observaciones pendientes.']);

            $pendientes = DB::table('wms_recepcion_agencia_observaciones')
                ->where('pallet_id', $p->id)->where('estado', 'pendiente')->count();
            if (!$pendientes) throw ValidationException::withMessages(['pallet' => 'No hay observaciones pendientes de autorización.']);

            DB::table('wms_recepcion_agencia_observaciones')->where('pallet_id', $p->id)
                ->where('estado', 'pendiente')->update([
                    'estado' => 'autorizada', 'autorizado_por' => auth()->id(),
                    'autorizado_at' => now(), 'comentario_autorizacion' => 'Autorización excepcional de ingreso.',
                    'updated_at' => now(),
                ]);
            DB::table('wms_recepcion_agencia_pallets')->where('id', $p->id)->update([
                'estado' => 'autorizado', 'updated_at' => now(),
            ]);

            return response()->json(['ok' => true, 'message' => 'Observaciones autorizadas. El pallet puede ingresar con sus cantidades físicas registradas.']);
        });
    }

    public function ingresar(Request $request, int $recepcion, int $pallet): JsonResponse
    {
        $data = $request->validate(['ubicacion_id' => ['required', 'integer']]);
        $cabecera = $this->recepcion($recepcion);
        $almacen = $this->almacenPrincipalOrFail();

        return DB::transaction(function () use ($cabecera, $pallet, $data, $almacen) {
            $recepcionActual = DB::table('wms_recepciones_agencia')->where('id', $cabecera->id)->lockForUpdate()->first();
            if (!$recepcionActual || $recepcionActual->estado !== 'abierta') {
                throw ValidationException::withMessages(['recepcion' => 'La recepción está cerrada.']);
            }
            $p = DB::table('wms_recepcion_agencia_pallets')->where('recepcion_id', $cabecera->id)
                ->where('id', $pallet)->lockForUpdate()->first();
            if (!$p) abort(404);
            if ($cabecera->estado !== 'abierta' || !in_array($p->estado, ['verificado', 'autorizado'], true)) {
                throw ValidationException::withMessages(['pallet' => 'Solo pueden ingresar pallets verificados o con excepción autorizada.']);
            }

            $ubicacion = WmsUbicacion::query()->with('galpon')->where('id', $data['ubicacion_id'])
                ->where('almacen_id', $almacen->id)->where('tipo', 'NORMAL')->where('activo', true)
                ->whereHas('galpon', fn ($q) => $q->where('activo', true))->first();
            if (!$ubicacion || !$ubicacion->galpon) throw ValidationException::withMessages(['ubicacion_id' => 'Selecciona una ubicación NORMAL activa del almacén.']);

            $detalles = DB::table('wms_recepcion_agencia_detalles')->where('pallet_id', $p->id)->orderBy('id')->get();
            if ($detalles->isEmpty() || $detalles->contains(fn ($d) => $d->codigo_fisico === null || $d->cantidad_fisica === null)) {
                throw ValidationException::withMessages(['pallet' => 'El detalle físico del pallet está incompleto.']);
            }
            if ((int) $detalles->sum('cantidad_fisica') <= 0) {
                throw ValidationException::withMessages(['pallet' => 'El pallet no tiene cajas físicas para ingresar al stock WMS.']);
            }

            $this->stockLock->bloquearPallets([$p->pallet]);

            $documento = DB::table('wms_recepcion_agencia_documentos')->where('id', $p->documento_id)->first();
            $rdocum = 'RA-' . strtoupper(base_convert((string) $p->id, 10, 36));
            if (WmsIngreso::query()->where('rdocum', $rdocum)->where('pallet', $p->pallet)->exists()) {
                throw ValidationException::withMessages(['pallet' => 'Ya existe un ingreso WMS para este pallet.']);
            }

            $codigosFisicos = $detalles->pluck('codigo_fisico')->filter()->unique()->values();
            $stockFisico = DB::connection('sisinvconsolidado2026')->table('stock')
                ->whereIn('CODIGO', $codigosFisicos)
                ->select('CODIGO', 'DESCRIP', 'DESCRIP1')
                ->get()->keyBy('CODIGO');

            foreach ($detalles as $d) {
                if ((int) $d->cantidad_fisica === 0) continue;
                $productoFisico = $stockFisico->get($d->codigo_fisico);
                if (!$productoFisico) {
                    throw ValidationException::withMessages([
                        'pallet' => "El código físico {$d->codigo_fisico} no existe en el maestro de productos. No se puede generar el ingreso.",
                    ]);
                }
                WmsIngreso::create([
                    'rdocum' => $rdocum,
                    'doc_origen' => 'T' . $documento->tipo_registro . '-' . $documento->id_registro,
                    'rfecha' => $documento->fecha_documento ?: now()->toDateString(),
                    'tipo_ingreso' => 'recepcion_agencia',
                    'motivo' => 'Recepción agencia #' . $cabecera->id . ' · Documento ' . $documento->id_registro,
                    'pallet' => $p->pallet,
                    'codigo' => $d->codigo_fisico,
                    'clote' => $d->lote_fisico,
                    'descrip' => $productoFisico->DESCRIP ?? $d->descrip,
                    'descrip1' => $productoFisico->DESCRIP1 ?? $d->descrip1,
                    'cantidad' => (int) $d->cantidad_fisica,
                    'almacen' => $almacen->codigo,
                    'galpon' => $ubicacion->galpon->codigo,
                    'ubicacion' => $ubicacion->codigo,
                ]);
            }

            DB::table('wms_recepcion_agencia_pallets')->where('id', $p->id)->update([
                'estado' => 'ingresado', 'ubicacion_final_id' => $ubicacion->id,
                'ingresado_at' => now(), 'ingresado_por' => auth()->id(), 'updated_at' => now(),
            ]);

            return response()->json(['ok' => true, 'message' => 'Ingreso definitivo registrado. El pallet ya forma parte del saldo físico WMS.']);
        });
    }

    public function cerrar(int $recepcion): JsonResponse
    {
        $cabecera = $this->recepcion($recepcion);
        DB::transaction(function () use ($cabecera) {
            DB::table('wms_recepciones_agencia')->where('id', $cabecera->id)->lockForUpdate()->first();
            DB::table('wms_recepciones_agencia')->where('id', $cabecera->id)->update([
                'estado' => 'cerrada', 'update_id' => auth()->id(), 'updated_at' => now(),
            ]);
        });

        $pendientes = DB::table('wms_recepcion_agencia_pallets')
            ->where('recepcion_id', $cabecera->id)->whereNotIn('estado', ['ingresado'])->count();

        return response()->json([
            'ok' => true,
            'message' => "Recepción cerrada. {$pendientes} pallets permanecen pendientes o con observaciones; su estado se conserva para seguimiento.",
            'pendientes' => $pendientes,
        ]);
    }

    public function crearUbicacionTemporal(Request $request): JsonResponse
    {
        abort_unless(auth()->user()->can('wms.configurar'), 403);
        $data = $request->validate([
            'codigo' => ['required', 'string', 'max:30', 'regex:/^[A-Za-z0-9._-]+$/'],
        ]);
        $almacen = $this->almacenPrincipalOrFail();
        $codigo = strtoupper(trim($data['codigo']));

        if (WmsUbicacion::query()->where('almacen_id', $almacen->id)->where('codigo', $codigo)->exists()) {
            throw ValidationException::withMessages(['codigo' => 'Ya existe una ubicación con ese código en este almacén.']);
        }

        $id = DB::table('wms_ubicaciones')->insertGetId([
            'almacen_id' => $almacen->id,
            'galpon_id' => null,
            'codigo' => $codigo,
            'tipo' => 'RECEPCION',
            'numero' => null,
            'activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['ok' => true, 'message' => 'Zona temporal creada.', 'id' => $id, 'codigo' => $codigo], 201);
    }

    private function almacenPrincipalOrFail(): \App\Models\WmsAlmacen
    {
        $almacen = $this->context->almacenPrincipal();

        if (!$almacen) {
            abort(403, 'No tienes un almacén principal WMS activo asignado. Solicita al administrador que configure tu almacén principal antes de realizar registros.');
        }

        return $almacen;
    }

    private function recepcion(int $id): object
    {
        $almacen = $this->almacenPrincipalOrFail();
        $row = DB::table('wms_recepciones_agencia')->where('id', $id)
            ->where('almacen_id', $almacen->id)->first();
        if (!$row) abort(404);
        return $row;
    }

    private function pallet(int $recepcionId, int $palletId): object
    {
        $row = DB::table('wms_recepcion_agencia_pallets')
            ->where('id', $palletId)->where('recepcion_id', $recepcionId)->first();
        if (!$row) abort(404);
        return $row;
    }
}
