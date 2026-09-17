<?php

namespace App\Http\Controllers;

use App\Models\NominaCategoria;
use App\Models\NominaPersonal;
use App\Services\NominaConfiguracionSalarialService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

class NominaConfiguracionSalarialController extends Controller
{
    public function __construct(
        private readonly NominaConfiguracionSalarialService $service
    ) {
    }

    public function index(Request $request): View
    {
        $query = NominaPersonal::query()->with([
            'configuracionesSalariales' => fn ($q) => $q->with('categoria')->latest('fecha_inicio'),
        ]);

        if ($request->filled('license')) {
            $query->where('license', 'like', '%' . trim($request->string('license')) . '%');
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->string('estado'));
        }

        $personas = $query->orderBy('apellido')->orderBy('nombre')->paginate(20)->withQueryString();

        return view('nomina.configuraciones-salariales.index', compact('personas'));
    }

    public function show(NominaPersonal $personal): View
    {
        $personal->load([
            'configuracionesSalariales' => fn ($q) => $q->with('categoria')->orderByDesc('fecha_inicio'),
        ]);

        $categorias = NominaCategoria::query()
            ->where('activo', true)
            ->orderBy('nombre')
            ->get();

        return view('nomina.configuraciones-salariales.show', compact('personal', 'categorias'));
    }

    public function store(Request $request, NominaPersonal $personal): RedirectResponse
    {
        $validated = $request->validate([
            'fecha_inicio' => ['required', 'date'],
            'haber_basico' => ['required', 'numeric', 'min:0'],
            'categoria_id' => ['nullable', 'integer', 'exists:rh_categorias,id'],
            'modalidad_remuneracion' => ['nullable', 'string', 'max:80'],
            'salario_cotizable' => ['nullable', 'numeric', 'min:0'],
            'observaciones' => ['nullable', 'string', 'max:5000'],
        ]);

        try {
            $this->service->guardar(
                $personal,
                $validated['fecha_inicio'],
                (float) $validated['haber_basico'],
                isset($validated['categoria_id']) ? (int) $validated['categoria_id'] : null,
                $validated['modalidad_remuneracion'] ?? null,
                isset($validated['salario_cotizable']) ? (float) $validated['salario_cotizable'] : null,
                $validated['observaciones'] ?? null,
            );

            return back()->with('success', 'Configuración salarial guardada correctamente.');
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }
}
