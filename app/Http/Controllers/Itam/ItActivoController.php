<?php

namespace App\Http\Controllers\Itam;

use App\Http\Controllers\Controller;
use App\Models\ItActivo;
use App\Models\ItEstado;
use App\Models\ItMarca;
use App\Models\ItModelo;
use App\Models\ItTipoActivo;
use App\Models\ItUbicacion;
use App\Services\Itam\ItActivoService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ItActivoController extends Controller
{
    public function index(Request $request)
    {
        $activos = ItActivo::query()
            ->with(['tipoActivo', 'marca', 'modelo', 'estado', 'ubicacion'])
            ->when($request->filled('buscar'), function ($query) use ($request) {
                $buscar = trim((string) $request->input('buscar'));
                $query->where(function ($q) use ($buscar) {
                    $q->where('codigo_ti', 'like', "%{$buscar}%")
                        ->orWhere('numero_serie', 'like', "%{$buscar}%")
                        ->orWhereHas('tipoActivo', fn ($tipo) => $tipo->where('descripcion', 'like', "%{$buscar}%"))
                        ->orWhereHas('marca', fn ($marca) => $marca->where('nombre', 'like', "%{$buscar}%"))
                        ->orWhereHas('modelo', fn ($modelo) => $modelo->where('nombre', 'like', "%{$buscar}%"));
                });
            })
            ->when($request->filled('tipo_activo_id'), fn ($q) => $q->where('tipo_activo_id', $request->integer('tipo_activo_id')))
            ->when($request->filled('estado_id'), fn ($q) => $q->where('estado_id', $request->integer('estado_id')))
            ->when($request->filled('ubicacion_id'), fn ($q) => $q->where('ubicacion_id', $request->integer('ubicacion_id')))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('itam.activos.index', [
            'activos' => $activos,
            'tiposActivo' => ItTipoActivo::orderBy('descripcion')->get(),
            'estados' => ItEstado::orderBy('descripcion')->get(),
            'ubicaciones' => ItUbicacion::orderBy('codigo')->get(),
        ]);
    }

    public function create()
    {
        return view('itam.activos.create', [
            'tiposActivo' => ItTipoActivo::orderBy('descripcion')->get(),
            'marcas' => ItMarca::orderBy('nombre')->get(),
            'modelos' => ItModelo::with('marca')->orderBy('nombre')->get(),
            'ubicaciones' => ItUbicacion::orderBy('codigo')->get(),
        ]);
    }

    public function store(Request $request, ItActivoService $service)
    {
        $data = $request->validate([
            'tipo_activo_id' => ['required', 'integer', Rule::exists('it_tipos_activo', 'id')->whereNull('deleted_at')],
            'marca_id' => ['nullable', 'integer', Rule::exists('it_marcas', 'id')->whereNull('deleted_at')],
            'modelo_id' => ['nullable', 'integer', Rule::exists('it_modelos', 'id')->whereNull('deleted_at')],
            'numero_serie' => [
                Rule::requiredIf(fn () => (bool) ItTipoActivo::find($request->integer('tipo_activo_id'))?->requiere_serial),
                'nullable', 'string', 'max:150',
                Rule::unique('it_activos', 'numero_serie')
                    ->where(fn ($query) => $query->where('tipo_activo_id', $request->integer('tipo_activo_id')))
                     ,
            ],
            'numero_parte' => ['nullable', 'string', 'max:150'],
            'ubicacion_id' => ['required', 'integer', Rule::exists('it_ubicaciones', 'id')->whereNull('deleted_at')],
            'fecha_adquisicion' => ['nullable', 'date'],
            'costo' => ['nullable', 'numeric', 'min:0', 'max:999999999999.99'],
            'garantia_fecha' => ['nullable', 'date', 'after_or_equal:fecha_adquisicion'],
            'origen_documento' => ['required', Rule::in(['INVENTARIO_INICIAL', 'ADQUISICION'])],
            'observaciones' => ['nullable', 'string', 'max:5000'],
        ], [
            'numero_serie.required' => 'El tipo de activo seleccionado requiere número de serie.',
            'numero_serie.unique' => 'Ya existe un activo de este tipo con el mismo número de serie.',
        ]);

        if ($data['modelo_id'] ?? false) {
            $modelo = ItModelo::findOrFail($data['modelo_id']);
            if (!empty($data['marca_id']) && (int) $modelo->marca_id !== (int) $data['marca_id']) {
                return back()->withInput()->withErrors([
                    'modelo_id' => 'El modelo seleccionado no corresponde a la marca indicada.',
                ]);
            }
            $data['marca_id'] ??= $modelo->marca_id;
        }

        $activo = $service->registrar($data);

        return redirect()->route('itam.activos.show', $activo)
            ->with('success', "Activo {$activo->codigo_ti} registrado correctamente.");
    }

    public function show(ItActivo $activo)
    {
        $activo->load([
            'tipoActivo', 'marca', 'modelo', 'estado', 'ubicacion',
            'caracteristicas' => fn ($q) => $q->whereNull('fecha_fin')->orderBy('nombre_caracteristica'),
            'componentesInstalados' => fn ($q) => $q->whereNull('fecha_retiro')->with('componente'),
            'accesorios' => fn ($q) => $q->whereNull('fecha_fin'),
        ]);

        return view('itam.activos.show', compact('activo'));
    }
}
