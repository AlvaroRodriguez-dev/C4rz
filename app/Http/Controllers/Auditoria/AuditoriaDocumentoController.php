<?php

namespace App\Http\Controllers\Auditoria;

use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AuditoriaDocumentoController extends Controller
{
    private const TIPOS = [
        'ingreso' => ['label' => 'Nota de Ingreso', 'connection' => 'sisinvconsolidado2026', 'header' => 'recep', 'detail' => 'recep1', 'document' => 'RDOCUM', 'detail_document' => 'RDOCUM'],
        'entrega' => ['label' => 'Nota de Entrega', 'connection' => 'sisinvconsolidado2026', 'header' => 'entregas', 'detail' => 'entregas1', 'document' => 'EDOCUM', 'detail_document' => 'EDOCUM'],
        'traspaso' => ['label' => 'Nota de Traspaso', 'connection' => 'sisinvconsolidado2026', 'header' => 'trasp', 'detail' => 'trasp1', 'document' => 'TDOCUM', 'detail_document' => 'TDOCUM'],
        'proforma' => ['label' => 'Proformas', 'connection' => 'sisinvconsolidado2026', 'header' => 'profor', 'detail' => 'profor1', 'document' => 'VDOCUMA', 'detail_document' => 'VDOCUMA'],
        'venta' => ['label' => 'Ventas', 'connection' => 'sisinvconsolidado2026', 'header' => 'ventas', 'detail' => 'ventas1', 'document' => 'VDOCUMA', 'detail_document' => 'VDOCUMA'],
        'despacho' => ['label' => 'Despachos', 'connection' => 'faboce2026', 'header' => 'log_registro', 'detail' => 'log_registro_detalle', 'document' => 'id', 'detail_document' => 'id_registro', 'tipo' => 1],
        'programacion' => ['label' => 'Programación', 'connection' => 'faboce2026', 'header' => 'log_registro', 'detail' => 'log_registro_detalle', 'document' => 'id', 'detail_document' => 'id_registro', 'tipo' => 2],
        'transito' => ['label' => 'Tránsitos', 'connection' => 'faboce2026', 'header' => 'log_registro', 'detail' => 'log_registro_detalle', 'document' => 'id', 'detail_document' => 'id_registro', 'tipo' => 3],
        'recepcion' => ['label' => 'Recepción', 'connection' => 'faboce2026', 'header' => 'log_registro', 'detail' => 'log_registro_detalle', 'document' => 'id', 'detail_document' => 'id_registro', 'tipo' => 4],
    ];

    public function index() { return view('auditoria.documento.index', ['tipos' => self::TIPOS]); }

    public function buscarDocumentos(Request $request)
    {
        $tipo = (string) $request->get('tipo'); $q = trim((string) $request->get('q', '')); abort_unless(isset(self::TIPOS[$tipo]), 404); $cfg = self::TIPOS[$tipo];
        $query = DB::connection($cfg['connection'])->table($cfg['header'])->select($cfg['document'])->when(isset($cfg['tipo']), fn ($query) => $query->where('tipo_registro', $cfg['tipo']))->when($q !== '', fn ($query) => $query->where($cfg['document'], 'like', "%{$q}%"))->orderByDesc($cfg['document'])->limit(30);
        return response()->json(['results' => $query->get()->map(fn ($row) => ['id' => (string) $row->{$cfg['document']}, 'text' => (string) $row->{$cfg['document']}])->values()]);
    }

    public function generar(Request $request)
    {
        $data = $request->validate(['tipo' => ['required', 'string'], 'documento' => ['required', 'string', 'max:100']]);
        return response()->json($this->obtenerReporte($data['tipo'], $data['documento']));
    }

    public function pdf(Request $request)
    {
        $data = $request->validate(['tipo' => ['required', 'string'], 'documento' => ['required', 'string', 'max:100']]); abort_unless(isset(self::TIPOS[$data['tipo']]), 404);
        return Pdf::loadView('auditoria.documento.pdf', $this->obtenerReporte($data['tipo'], $data['documento']))->setPaper('letter', 'portrait')->stream('auditoria-documento-'.$data['documento'].'.pdf');
    }

    private function obtenerReporte(string $tipo, string $documento): array
    {
        abort_unless(isset(self::TIPOS[$tipo]), 404); $cfg = self::TIPOS[$tipo]; $db = DB::connection($cfg['connection']); $schema = $db->getSchemaBuilder(); $columnasCabecera = $schema->getColumnListing($cfg['header']); $columnasDetalle = $schema->getColumnListing($cfg['detail']);
        $fechaCampo = $this->primerCampo($columnasCabecera, ['fecha', 'RFECHA', 'EFECHA', 'TFECHA', 'VFECHA', 'fecha_documento', 'fecha_doc']); $glosaCampo = $this->primerCampo($columnasCabecera, ['glosa', 'GLOSA', 'RGLOSA', 'EGLOSA', 'TGLOSA', 'VGLOSA', 'observaciones']);
        $select = array_values(array_filter([$cfg['document'], $fechaCampo, $glosaCampo, 'created_id', 'created_at', 'updated_id', 'updated_at', 'deleted_id', 'deleted_at'], fn ($field) => $field && in_array($field, $columnasCabecera, true)));
        $header = $db->table($cfg['header'])->where($cfg['document'], $documento)->when(isset($cfg['tipo']), fn ($query) => $query->where('tipo_registro', $cfg['tipo']))->first($select); abort_unless($header, 404, 'Documento no encontrado.'); $headerArray = (array) $header;
        return ['tipo' => $cfg['label'], 'documento' => $documento, 'tabla_cabecera' => $cfg['connection'].'.'.$cfg['header'], 'tabla_detalle' => $cfg['connection'].'.'.$cfg['detail'], 'header' => $headerArray, 'detalle' => $this->obtenerDetalle($db, $cfg, $documento, $columnasDetalle), 'usuarios' => $this->usuariosAuditoria($headerArray), 'audits' => $this->obtenerAuditoria($documento), 'fecha_campo' => $fechaCampo, 'glosa_campo' => $glosaCampo, 'generado_at' => now()->format('d/m/Y H:i:s')];
    }

    private function obtenerDetalle($db, array $cfg, string $documento, array $columnasDetalle)
    {
        $codigoCampo = $this->primerCampo($columnasDetalle, ['codigo', 'CODIGO', 'codigo_producto', 'CODIGO_PRODUCTO']); $cantidadCampo = $this->primerCampo($columnasDetalle, ['cantidad', 'CANTIDAD', 'RCANTIDAD', 'ECANTIDAD', 'TCANTIDAD', 'VCANTIDAD']);
        $campos = ['codigo' => $codigoCampo, 'cantidad' => $cantidadCampo, 'created_id' => in_array('created_id', $columnasDetalle, true) ? 'created_id' : null, 'created_at' => in_array('created_at', $columnasDetalle, true) ? 'created_at' : null, 'updated_id' => in_array('updated_id', $columnasDetalle, true) ? 'updated_id' : null, 'updated_at' => in_array('updated_at', $columnasDetalle, true) ? 'updated_at' : null, 'deleted_id' => in_array('deleted_id', $columnasDetalle, true) ? 'deleted_id' : null, 'deleted_at' => in_array('deleted_at', $columnasDetalle, true) ? 'deleted_at' : null];
        $select = []; if ($campos['codigo']) $select[] = 'd.'.$campos['codigo'].' as codigo'; if ($campos['cantidad']) $select[] = 'd.'.$campos['cantidad'].' as cantidad'; foreach (['created_id', 'created_at', 'updated_id', 'updated_at', 'deleted_id', 'deleted_at'] as $campo) if ($campos[$campo]) $select[] = 'd.'.$campos[$campo].' as '.$campo; if (!$select) return collect();
        $detalle = $db->table($cfg['detail'].' as d')->where('d.'.$cfg['detail_document'], $documento)->orderBy($campos['codigo'] ? 'd.'.$campos['codigo'] : 'd.'.$cfg['detail_document'])->get($select);

        // El código puede venir como entero, texto o con espacios. Normalizamos ambos lados para no perder la descripción.
        $codigos = $detalle->pluck('codigo')->filter(fn ($codigo) => $codigo !== null && trim((string) $codigo) !== '')->map(fn ($codigo) => trim((string) $codigo))->unique()->values();
        $productos = collect();
        if ($codigos->isNotEmpty()) {
            $stockDb = DB::connection('sisinvconsolidado2026'); $stockColumns = $stockDb->getSchemaBuilder()->getColumnListing('stock');
            if (in_array('codigo', $stockColumns, true)) {
                $stockSelect = ['codigo']; foreach (['descrip', 'descrip1'] as $campo) if (in_array($campo, $stockColumns, true)) $stockSelect[] = $campo;
                $productos = $stockDb->table('stock')->whereIn('codigo', $codigos->all())->get($stockSelect)->mapWithKeys(function ($producto) { return [trim((string) $producto->codigo) => $producto]; });
            }
        }

        $usuarioIds = $detalle->flatMap(fn ($row) => collect(['created_id', 'updated_id', 'deleted_id'])->map(fn ($campo) => $row->{$campo} ?? null)->filter(fn ($id) => $id !== null && trim((string) $id) !== '')->map(fn ($id) => trim((string) $id)))->unique()->values();
        $usuarios = collect();
        if ($usuarioIds->isNotEmpty()) {
            $userDb = DB::connection('faboce2026'); $userColumns = $userDb->getSchemaBuilder()->getColumnListing('users');
            if (in_array('id', $userColumns, true)) { $userSelect = ['id']; if (in_array('name', $userColumns, true)) $userSelect[] = 'name'; if (in_array('email', $userColumns, true)) $userSelect[] = 'email'; $usuarios = $userDb->table('users')->whereIn('id', $usuarioIds->all())->get($userSelect)->mapWithKeys(fn ($user) => [trim((string) $user->id) => $user]); }
        }

        return $detalle->map(function ($row) use ($productos, $usuarios) {
            $item = (array) $row; $codigo = trim((string) ($row->codigo ?? '')); $producto = $productos->get($codigo); $item['producto'] = $producto ? (trim((string) ($producto->descrip ?? '')) !== '' ? $producto->descrip : ($producto->descrip1 ?? null)) : null;
            foreach (['created_id', 'updated_id', 'deleted_id'] as $campo) { $id = $row->{$campo} ?? null; $key = trim((string) $id); $item[$campo.'_usuario'] = ($id !== null && $key !== '') ? ($usuarios->get($key)->name ?? 'Usuario no encontrado') : null; }
            return $item;
        })->values();
    }

    private function usuariosAuditoria(array $header): array
    {
        $ids = ['created_id' => $header['created_id'] ?? null, 'updated_id' => $header['updated_id'] ?? null, 'deleted_id' => $header['deleted_id'] ?? null]; $idsUnicos = array_values(array_unique(array_filter($ids, fn ($id) => $id !== null && $id !== ''))); if (!$idsUnicos) return [];
        $users = DB::connection('faboce2026')->table('users')->whereIn('id', $idsUnicos)->get(['id', 'name', 'email'])->keyBy('id'); $resultado = [];
        foreach ($ids as $campo => $id) { if ($id === null || $id === '') { $resultado[$campo] = null; continue; } $user = $users->get($id); $resultado[$campo] = ['id' => $id, 'name' => $user->name ?? 'Usuario no encontrado', 'email' => $user->email ?? null]; }
        return $resultado;
    }

    private function obtenerAuditoria(string $documento)
    {
        $db = DB::connection('faboce2026'); $columnas = $db->getSchemaBuilder()->getColumnListing('audits'); if (!in_array('old_values', $columnas, true) || !in_array('new_values', $columnas, true)) return collect(); $select = [];
        $camposAudits = ['id' => 'a.id as audit_id', 'event' => 'a.event', 'user_id' => 'a.user_id', 'auditable_type' => 'a.auditable_type', 'auditable_id' => 'a.auditable_id', 'old_values' => 'a.old_values', 'new_values' => 'a.new_values', 'url' => 'a.url', 'ip_address' => 'a.ip_address', 'user_agent' => 'a.user_agent', 'created_at' => 'a.created_at']; foreach ($camposAudits as $campo => $expresion) if (in_array($campo, $columnas, true)) $select[] = $expresion;
        $query = $db->table('audits as a')->where(function ($query) use ($documento) { $query->where('a.old_values', 'like', "%{$documento}%")->orWhere('a.new_values', 'like', "%{$documento}%"); });
        if (in_array('user_id', $columnas, true)) { $userColumns = $db->getSchemaBuilder()->getColumnListing('users'); if (in_array('id', $userColumns, true)) { $query->leftJoin('users as au', 'au.id', '=', 'a.user_id'); if (in_array('name', $userColumns, true)) $select[] = 'au.name as user_name'; if (in_array('email', $userColumns, true)) $select[] = 'au.email as user_email'; } }
        return $query->orderByDesc('a.created_at')->limit(200)->get($select)->map(function ($row) { $item = (array) $row; $item['old_values_json'] = $this->prettyJson($item['old_values'] ?? null); $item['new_values_json'] = $this->prettyJson($item['new_values'] ?? null); return $item; });
    }

    private function primerCampo(array $columnas, array $candidatos): ?string { foreach ($candidatos as $campo) if (in_array($campo, $columnas, true)) return $campo; return null; }
    private function prettyJson($value): string { if ($value === null || $value === '') return '{}'; $decoded = is_array($value) ? $value : json_decode((string) $value, true); return json_last_error() === JSON_ERROR_NONE || is_array($value) ? json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : (string) $value; }
}
