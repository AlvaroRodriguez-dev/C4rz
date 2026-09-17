<?php

namespace App\Http\Controllers;

use App\Models\NominaConcepto;
use App\Models\NominaPersonal;
use App\Models\NominaPersonalConcepto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NominaPersonalConceptoController extends Controller
{
    public function index(Request $request): View
    {
        $query = NominaPersonalConcepto::query()->with(['personal', 'concepto']);

        if ($request->filled('license')) {
            $license = trim((string) $request->input('license'));
            $query->whereHas('personal', fn ($q) => $q->where('license', 'like', "%{$license}%"));
        }

        if ($request->filled('concepto_id')) {
            $query->where('concepto_id', $request->integer('concepto_id'));
        }

        $asignaciones = $query->orderByDesc('fecha_inicio')->orderBy('rh_personal_id')->paginate(20)->withQueryString();
        $conceptos = NominaConcepto::where('activo', true)->orderBy('codigo')->get();

        return view('nomina.personal-conceptos.index', compact('asignaciones', 'conceptos'));
    }

    public function create(Request $request): View
    {
        $personal = NominaPersonal::query()->where('estado', 'ACTIVO')->orderBy('apellido')->orderBy('nombre')->get();
        $conceptos = NominaConcepto::query()->where('activo', true)->orderBy('codigo')->get();

        return view('nomina.personal-conceptos.create', [
            'personal' => $personal,
            'conceptos' => $conceptos,
            'selectedPersonal' => $request->integer('personal_id') ?: null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'rh_personal_id' => ['required', 'integer', 'exists:rh_personal,id'],
            'concepto_id' => ['required', 'integer', 'exists:rh_conceptos,id'],
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['nullable', 'date', 'after_or_equal:fecha_inicio'],
            'tipo_valor' => ['required', 'in:IMPORTE,PORCENTAJE,CANTIDAD'],
            'valor' => ['required', 'numeric', 'min:0'],
            'observaciones' => ['nullable', 'string', 'max:5000'],
        ]);

        $this->ensureNoOverlap($validated);
        NominaPersonalConcepto::create($validated);

        return redirect()->route('nomina.personal-conceptos.index')->with('success', 'Concepto asignado al personal correctamente.');
    }

    public function edit(NominaPersonalConcepto $personalConcepto): View
    {
        $personal = NominaPersonal::query()->orderBy('apellido')->orderBy('nombre')->get();
        $conceptos = NominaConcepto::query()->orderBy('codigo')->get();

        return view('nomina.personal-conceptos.edit', compact('personalConcepto', 'personal', 'conceptos'));
    }

    public function update(Request $request, NominaPersonalConcepto $personalConcepto): RedirectResponse
    {
        $validated = $request->validate([
            'rh_personal_id' => ['required', 'integer', 'exists:rh_personal,id'],
            'concepto_id' => ['required', 'integer', 'exists:rh_conceptos,id'],
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['nullable', 'date', 'after_or_equal:fecha_inicio'],
            'tipo_valor' => ['required', 'in:IMPORTE,PORCENTAJE,CANTIDAD'],
            'valor' => ['required', 'numeric', 'min:0'],
            'observaciones' => ['nullable', 'string', 'max:5000'],
        ]);

        $this->ensureNoOverlap($validated, $personalConcepto->id);
        $personalConcepto->update($validated);

        return redirect()->route('nomina.personal-conceptos.index')->with('success', 'Asignación actualizada correctamente.');
    }

    private function ensureNoOverlap(array $data, ?int $ignoreId = null): void
    {
        $query = NominaPersonalConcepto::query()
            ->where('rh_personal_id', $data['rh_personal_id'])
            ->where('concepto_id', $data['concepto_id'])
            ->whereDate('fecha_inicio', '<=', $data['fecha_fin'] ?? '9999-12-31')
            ->where(function ($q) use ($data) {
                $q->whereNull('fecha_fin')->orWhereDate('fecha_fin', '>=', $data['fecha_inicio']);
            });

        if ($ignoreId !== null) {
            $query->where('id', '<>', $ignoreId);
        }

        if ($query->exists()) {
            abort(422, 'Existe otra asignación del mismo concepto para el trabajador dentro del periodo indicado.');
        }
    }
}
