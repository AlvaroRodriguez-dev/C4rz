<?php

namespace App\Http\Controllers;

use App\Models\NominaPersonal;
use App\Services\NominaPersonalService;
use Illuminate\Http\Request;
use InvalidArgumentException;

class NominaPersonalController extends Controller
{
    public function __construct(
        private readonly NominaPersonalService $personalService
    ) {
    }

    public function index(Request $request)
    {
        $buscar = trim((string) $request->input('buscar', ''));

        $personas = NominaPersonal::query()
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $resultadosRrhh = [];

        if ($buscar !== '') {
            $personal = $this->personalService->findByLicense($buscar);

            if ($personal !== null) {
                $resultadosRrhh[] = $this->personalService->normalize($personal);
            }
        }

        return view('nomina.personal.index', compact(
            'personas',
            'buscar',
            'resultadosRrhh'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'license' => ['required', 'string', 'max:30'],
        ]);

        try {
            $persona = $this->personalService->register($validated['license']);

            return redirect()
                ->route('nomina.personal.index')
                ->with('success', "El trabajador con LICENSE {$persona->license} fue registrado en Nómina.");
        } catch (InvalidArgumentException $e) {
            return redirect()
                ->route('nomina.personal.index', ['buscar' => $validated['license']])
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }

    public function toggle(NominaPersonal $persona)
    {
        $persona->estado = $persona->estado === 'ACTIVO' ? 'INACTIVO' : 'ACTIVO';
        $persona->save();

        return redirect()
            ->route('nomina.personal.index')
            ->with('success', "Estado actualizado para LICENSE {$persona->license}.");
    }
}
