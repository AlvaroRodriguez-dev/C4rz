<?php

namespace App\Http\Controllers;

use App\Models\NominaConfiguracionLaboral;
use App\Models\NominaPersonal;
use App\Services\NominaConfiguracionLaboralService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NominaConfiguracionLaboralController extends Controller
{
    public function __construct(
        private readonly NominaConfiguracionLaboralService $service
    ) {
    }

    public function index(Request $request): View
    {
        $query = NominaPersonal::query()->with([
            'configuracionesLaborales' => fn ($q) => $q->latest('fecha_inicio'),
        ]);

        if ($request->filled('license')) {
            $query->where('license', 'like', '%' . trim($request->string('license')) . '%');
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->string('estado'));
        }

        $personas = $query->orderBy('apellido')->orderBy('nombre')->paginate(20)->withQueryString();

        return view('nomina.configuraciones-laborales.index', compact('personas'));
    }

    public function sincronizar(string $license): RedirectResponse
    {
        $this->service->registrarActual($license);

        return back()->with('success', "Configuración laboral de {$license} sincronizada correctamente desde RRHH.");
    }

    public function show(NominaPersonal $personal): View
    {
        $personal->load([
            'configuracionesLaborales' => fn ($q) => $q->orderByDesc('fecha_inicio'),
        ]);

        return view('nomina.configuraciones-laborales.show', compact('personal'));
    }
}
