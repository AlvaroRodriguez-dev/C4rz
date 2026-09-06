<?php

namespace App\Http\Controllers\Auditoria;

use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AuditoriaDocumentoController extends Controller
{
    private const TIPOS = [
        'ingreso' => ['label' => 'Nota de Ingreso', 'connection' => 'sisinvconsolidado2026', 'header' => 'recep', 'detail' => 'recep1', 'document' => 'RDOCUM'],
        'entrega' => ['label' => 'Nota de Entrega', 'connection' => 'sisinvconsolidado2026', 'header' => 'entregas', 'detail' => 'entregas1', 'document' => 'EDOCUM'],
        'traspaso' => ['label' => 'Nota de Traspaso', 'connection' => 'sisinvconsolidado2026', 'header' => 'trasp', 'detail' => 'trasp1', 'document' => 'TDOCUM'],
        'proforma' => ['label' => 'Proformas', 'connection' => 'sisinvconsolidado2026', 'header' => 'profor', 'detail' => 'profor1', 'document' => 'VDOCUMA'],
        'venta' => ['label' => 'Ventas', 'connection' => 'sisinvconsolidado2026', 'header' => 'ventas', 'detail' => 'ventas1', 'document' => 'VDOCUMA'],
        'despacho' => ['label' => 'Despachos', 'connection' => 'faboce2026', 'header' => 'log_registro', 'detail' => 'log_registro_detalle', 'document' => 'id', 'tipo' => 1],
        'programacion' => ['label' => 'Programación', 'connection' => 'faboce2026', 'header' => 'log_registro', 'detail' => 'log_registro_detalle', 'document' => 'id', 'tipo' => 2],
        'transito' => ['label' => 'Tránsitos', 'connection' => 'faboce2026', 'header' => 'log_registro', 'detail' => 'log_registro_detalle', 'document' => 'id', 'tipo' => 3],
        'recepcion' => ['label' => 'Recepción', 'connection' => 'faboce2026', 'header' => 'log_registro', 'detail' => 'log_registro_detalle', 'document' => 'id', 'tipo' => 4],
    ];

    public function index()
    {
        return view('auditoria.documento.index', ['tipos' => self::TIPOS]);
    }

    public function buscarDocumentos(Request $request)
    {
        $tipo = (string) $request->get('tipo');
        $q = trim((string) $request->get('q', ''));
        abort_unless(isset(self::TIPOS[$tipo]), 404);

        $cfg = self::TIPOS[$tipo];
        $query = DB::connection($cfg['connection'])->table($cfg['header'])
            ->select($cfg['document'])
            ->when(isset($cfg['tipo']), fn ($query) => $query->where('tipo_registro', $cfg['tipo']))
            ->when($q !== '', fn ($query) => $query->where($cfg['document'], 'like', "%{$q}%"))
            ->orderByDesc($cfg['document'])
            ->limit(30);

        return response()->json([
            'results' => $query->get()->map(fn ($row) => [
                'id' => (string) $row->{$cfg['document']},
                'text' => (string) $row->{$cfg['document']},
            ])->values(),
        ]);
    }

    public function generar(Request $request)
    {
        $data = $request->validate([
            'tipo' => ['required', 'string'],
            'documento' => ['required', 'string', 'max:100'],
        ]);

        return response()->json($this->obtenerReporte($data['tipo'], $data['documento']));
    }

    public function pdf(Request $request)
    {
        $data = $request->validate([
            'tipo' => ['required', 'string'],
            'documento' => ['required', 'string', 'max:100'],
        ]);

        abort_unless(isset(self::TIPOS[$data['tipo']]), 404);

        return Pdf::loadView('auditoria.documento.pdf', $this->obtenerReporte($data['tipo'], $data['documento']))
            ->setPaper('letter', 'portrait')
            ->stream('auditoria-documento-'.$data['documento'].'.pdf');
    }

    private function obtenerReporte(string $tipo, string $documento): array
    {
        abort_unless(isset(self::TIPOS[$tipo]), 404);

        $cfg = self::TIPOS[$tipo];
        $db = DB::connection($cfg['connection']);
        $columnas = $db->getSchemaBuilder()->getColumnListing($cfg['header']);

        $fechaCampo = $this->primerCampo($columnas, ['fecha', 'RFECHA', 'EFECHA', 'TFECHA', 'VFECHA', 'fecha_documento', 'fecha_doc']);
        $glosaCampo = $this->primerCampo($columnas, ['glosa', 'GLOSA', 'RGLOSA', 'EGLOSA', 'TGLOSA', 'VGLOSA', 'observaciones']);

        $select = array_values(array_filter([
            $cfg['document'], $fechaCampo, $glosaCampo,
            'created_id', 'created_at', 'updated_id', 'updated_at', 'deleted_id', 'deleted_at',
        ], fn ($field) => $field && in_array($field, $columnas, true)));

        $header = $db->table($cfg['header'])
            ->where($cfg['document'], $documento)
            ->when(isset($cfg['tipo']), fn ($query) => $query->where('tipo_registro', $cfg['tipo']))
            ->first($select);

        abort_unless($header, 404, 'Documento no encontrado.');

        $headerArray = (array) $header;

        return [
            'tipo' => $cfg['label'],
            'documento' => $documento,
            'tabla_cabecera' => $cfg['connection'].'.'.$cfg['header'],
            'tabla_detalle' => $cfg['connection'].'.'.$cfg['detail'],
            'header' => $headerArray,
            'fecha_campo' => $fechaCampo,
            'glosa_campo' => $glosaCampo,
            'usuarios' => $this->usuariosAuditoria($headerArray),
            'audits' => $this->obtenerAuditoria($documento),
            'generado_at' => now()->format('d/m/Y H:i:s'),
        ];
    }

    private function usuariosAuditoria(array $header): array
    {
        $ids = [
            'created_id' => $header['created_id'] ?? null,
            'updated_id' => $header['updated_id'] ?? null,
            'deleted_id' => $header['deleted_id'] ?? null,
        ];

        $idsUnicos = array_values(array_unique(array_filter($ids, fn ($id) => $id !== null && $id !== '')));
        if (!$idsUnicos) {
            return [];
        }

        $users = DB::connection('faboce2026')->table('users')
            ->whereIn('id', $idsUnicos)
            ->get(['id', 'name', 'email'])
            ->keyBy('id');

        $resultado = [];
        foreach ($ids as $campo => $id) {
            if ($id === null || $id === '') {
                $resultado[$campo] = null;
                continue;
            }
            $user = $users->get($id);
            $resultado[$campo] = [
                'id' => $id,
                'name' => $user->name ?? 'Usuario no encontrado',
                'email' => $user->email ?? null,
            ];
        }

        return $resultado;
    }

    private function obtenerAuditoria(string $documento)
    {
        $db = DB::connection('faboce2026');
        $columnas = $db->getSchemaBuilder()->getColumnListing('audits');

        if (!in_array('old_values', $columnas, true) || !in_array('new_values', $columnas, true)) {
            return collect();
        }

        $select = array_values(array_intersect([
            'id', 'event', 'user_id', 'auditable_type', 'auditable_id',
            'old_values', 'new_values', 'url', 'ip_address', 'user_agent', 'created_at',
        ], $columnas));

        $query = $db->table('audits as a')
            ->where(function ($query) use ($documento) {
                $query->where('a.old_values', 'like', "%{$documento}%")
                    ->orWhere('a.new_values', 'like', "%{$documento}%");
            });

        if (in_array('user_id', $columnas, true)) {
            $userColumns = $db->getSchemaBuilder()->getColumnListing('users');
            if (in_array('id', $userColumns, true)) {
                $query->leftJoin('users as au', 'au.id', '=', 'a.user_id');
                $select[] = 'au.name as user_name';
                $select[] = 'au.email as user_email';
            }
        }

        return $query->orderByDesc('a.created_at')->limit(200)->get($select)->map(function ($row) {
            $item = (array) $row;
            $item['old_values_json'] = $this->prettyJson($item['old_values'] ?? null);
            $item['new_values_json'] = $this->prettyJson($item['new_values'] ?? null);
            return $item;
        });
    }

    private function primerCampo(array $columnas, array $candidatos): ?string
    {
        foreach ($candidatos as $campo) {
            if (in_array($campo, $columnas, true)) {
                return $campo;
            }
        }
        return null;
    }

    private function prettyJson($value): string
    {
        if ($value === null || $value === '') {
            return '{}';
        }

        $decoded = is_array($value) ? $value : json_decode((string) $value, true);

        return json_last_error() === JSON_ERROR_NONE || is_array($value)
            ? json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            : (string) $value;
    }
}
