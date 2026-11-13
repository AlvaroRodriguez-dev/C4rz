<?php

namespace App\\Http\\Controllers\\Itam;

use App\\Http\\Controllers\\Controller;
use App\\Models\\ItSolicitud;
use App\\Models\\ItTipoActivo;
use App\\Models\\ItUbicacion;
use App\\Services\\ItSolicitudService;
use Illuminate\\Http\\JsonResponse;
use Illuminate\\Http\\Request;
use Illuminate\\Support\\Facades\\DB;
use Illuminate\\Validation\\Rule;
use Illuminate\\Validation\\ValidationException;
use Throwable;

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
            'detalles.*.tipo_item' => ['required', 'in:ACTIVO'],
            'detalles.*.tipo_activo_id' => [
                'required',
                'integer',
                Rule::exists('it_tipos_activo', 'id')->whereNull('deleted_at'),
            ],
            'detalles.*.descripcion_solicitada' => ['required', 'string', 'max:255'],
            'detalles.*.cantidad' => ['required', 'integer', 'min:1'],
            'detalles.*.unidad' => ['nullable', 'string', 'max:30'],
            'detalles.*.especificaciones' => ['nullable', 'string'],
            'detalles.*.observaciones' => ['nullable', 'string'],
        ]);

        // RRHH es la fuente de verdad: nunca confiar en solicitante/área enviados por el navegador.
        try {
            $personal = DB::connection('pgsql_rrhh')
                ->table('rrhh_personal')
                ->where('ID', $data['solicitante_id'])
                ->whereNull('DELETED_AT')
                ->first(['ID as id', 'AREA_ID as area_id']);
        } catch (Throwable $exception) {
            report($exception);

            throw ValidationException::withMessages([
                'solicitante_id' => 'No fue posible verificar al solicitante en RRHH. La solicitud no se registró; intente nuevamente cuando el servicio esté disponible.',
            ]);
        }

        if (!$personal) {
            throw ValidationException::withMessages([
                'solicitante_id' => 'El solicitante no existe o se encuentra inactivo en RRHH. Seleccione un trabajador activo.',
            ]);
        }

        $data['solicitante_id'] = (int) $personal->id;
        $data['area_id'] = $personal->area_id !== null ? (int) $personal->area_id : null;

        $solicitud = $service->crear($data);

        return redirect()
            ->route('itam.solicitudes.show', $solicitud)
            ->with('success', "Solicitud {$solicitud->numero} creada correctamente.");
    }

    public function show(ItSolicitud $solicitud)
    {
        $solicitud->load([
            'detalles.tipoActivo',
            'ubicacion',
            'responsable',
            'evaluaciones' => fn ($query) => $query
                ->with(['evaluador', 'detalles.solicitudDetalle'])
                ->latest('id'),
        ]);

        try {
            $personal = DB::connection('pgsql_rrhh')
                ->table('rrhh_personal')
                ->where('ID', $solicitud->solicitante_id)
                ->whereNull('DELETED_AT')
                ->first([
                    'ID as id',
                    'NAME as name',
                    'LASTNAME as lastname',
                    'LICENSE as licence_id',
                    'AREA_ID as area_id',
                    'CHARGE_ID as cargo_id',
                    'AGECODIGO as agencia_id',
                ]);
        } catch (Throwable $exception) {
            report($exception);
            $personal = null;
        }

        return view('itam.solicitudes.show', compact('solicitud', 'personal'));
    }

    public function buscarPersonal(Request $request): JsonResponse
    {
        $texto = trim((string) $request->input('q', ''));

        if (mb_strlen($texto) < 2) {
            return response()->json([]);
        }

        try {
            $personal = DB::connection('pgsql_rrhh')
                ->table('rrhh_personal')
                ->whereNull('DELETED_AT')
                ->where(function ($query) use ($texto) {
                    $query->where('NAME', 'ilike', "%{$texto}%")
                        ->orWhere('LASTNAME', 'ilike', "%{$texto}%")
                        ->orWhere('LICENSE', 'ilike', "%{$texto}%");
                })
                ->orderBy('LASTNAME')
                ->orderBy('NAME')
                ->limit(20)
                ->get([
                    'ID as id',
                    'NAME as name',
                    'LASTNAME as lastname',
                    'LICENSE as licence_id',
                    'AREA_ID as area_id',
                    'CHARGE_ID as cargo_id',
                    'AGECODIGO as agencia_id',
                ]);

            return response()->json($personal);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => 'El servicio de RRHH no está disponible. No se puede validar personal en este momento.',
            ], 503);
        }
    }
}
