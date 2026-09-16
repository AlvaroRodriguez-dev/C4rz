<?php

namespace App\Http\Controllers\Itam;

use App\Http\Controllers\Controller;
use App\Models\ItSolicitud;
use App\Models\ItTipoActivo;
use App\Models\ItUbicacion;
use App\Services\ItSolicitudService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ItSolicitudController extends Controller
{
    public function index(Request $request)
    {
        $solicitudes = ItSolicitud::with(['detalles', 'ubicacion'])
            ->when($request->filled('buscar'), function ($query) use ($request) {
                $buscar = trim($request->string('buscar'));
                $query->where(function ($q) use ($buscar) {
                    $q->where('numero', 'like', "%{$buscar}%")
                        ->orWhere('descripcion', 'like', "%{$buscar}%")
                        ->orWhere('motivo', 'like', "%{$buscar}%");
                });
            })
            ->when($request->filled('estado'), fn ($q) => $q->where('estado', $request->string('estado')))
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('itam.solicitudes.index', compact('solicitudes'));
    }

    public function create()
    {
        $tiposActivo = ItTipoActivo::query()->orderBy('descripcion')->get();
        $ubicaciones = ItUbicacion::query()->orderBy('codigo')->get();

        return view('itam.solicitudes.create', compact('tiposActivo', 'ubicaciones'));
    }

    public function store(Request $request, ItSolicitudService $service)
    {
        $data = $request->validate([
            'solicitante_id' => ['required', 'integer', 'min:1'],
            'area_id' => ['nullable', 'integer', 'min:1'],
            'ubicacion_id' => ['nullable', 'integer', 'exists:it_ubicaciones,id'],
            'fecha_solicitud' => ['required', 'date'],
            'prioridad' => ['required', 'in:BAJA,NORMAL,ALTA,URGENTE'],
            'motivo' => ['nullable', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string'],
            'detalles' => ['required', 'array', 'min:1'],
            'detalles.*.tipo_item' => ['required', 'in:ACTIVO,COMPONENTE,ACCESORIO,SERVICIO'],
            'detalles.*.tipo_activo_id' => ['nullable', 'integer', 'exists:it_tipos_activo,id'],
            'detalles.*.descripcion_solicitada' => ['required', 'string', 'max:255'],
            'detalles.*.cantidad' => ['required', 'integer', 'min:1'],
            'detalles.*.unidad' => ['nullable', 'string', 'max:30'],
            'detalles.*.especificaciones' => ['nullable', 'string'],
            'detalles.*.observaciones' => ['nullable', 'string'],
        ]);

        $solicitud = $service->crear($data);

        return redirect()
            ->route('itam.solicitudes.show', $solicitud)
            ->with('success', "Solicitud {$solicitud->numero} creada correctamente.");
    }

    public function show(ItSolicitud $solicitud)
    {
        $solicitud->load(['detalles.tipoActivo', 'ubicacion', 'responsable']);

        $personal = DB::connection('pgsql_rrhh')
            ->table('rrhh_personal')
            ->where('id', $solicitud->solicitante_id)
            ->whereNull('deleted_at')
            ->first(['id', 'name', 'lastname', 'licence_id', 'area_id', 'cargo_id', 'agencia_id']);

        return view('itam.solicitudes.show', compact('solicitud', 'personal'));
    }

    public function buscarPersonal(Request $request): JsonResponse
    {
        $texto = trim((string) $request->input('q', ''));

        if (mb_strlen($texto) < 2) {
            return response()->json([]);
        }

        $personal = DB::connection('pgsql_rrhh')
            ->table('rrhh_personal')
            ->whereNull('deleted_at')
            ->where(function ($query) use ($texto) {
                $query->where('name', 'ilike', "%{$texto}%")
                    ->orWhere('lastname', 'ilike', "%{$texto}%")
                    ->orWhere('licence_id', 'ilike', "%{$texto}%");
            })
            ->orderBy('lastname')
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'name', 'lastname', 'licence_id', 'area_id', 'cargo_id', 'agencia_id']);

        return response()->json($personal);
    }
}
