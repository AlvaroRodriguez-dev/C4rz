<?php

namespace App\Http\Controllers;

use App\Models\NominaConcepto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NominaConceptoController extends Controller
{
    public function index(Request $request): View
    {
        $query = NominaConcepto::query();

        if ($request->filled('codigo')) {
            $query->where('codigo', 'like', '%' . trim($request->string('codigo')) . '%');
        }

        if ($request->filled('tipo')) {
            $query->where('tipo', $request->string('tipo'));
        }

        if ($request->filled('activo')) {
            $query->where('activo', (bool) $request->boolean('activo'));
        }

        $conceptos = $query->orderBy('tipo')->orderBy('codigo')->paginate(20)->withQueryString();

        return view('nomina.conceptos.index', compact('conceptos'));
    }

    public function create(): View
    {
        return view('nomina.conceptos.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'codigo' => ['required', 'string', 'max:50', 'unique:rh_conceptos,codigo'],
            'nombre' => ['required', 'string', 'max:150'],
            'tipo' => ['required', 'in:INGRESO,DESCUENTO,FISCAL'],
            'origen' => ['required', 'in:MANUAL,IMPORTADO,CALCULADO'],
            'descripcion' => ['nullable', 'string', 'max:5000'],
            'activo' => ['nullable', 'boolean'],
        ]);

        $validated['codigo'] = strtoupper(trim($validated['codigo']));
        $validated['activo'] = $request->boolean('activo');

        NominaConcepto::create($validated);

        return redirect()->route('nomina.conceptos.index')->with('success', 'Concepto creado correctamente.');
    }

    public function edit(NominaConcepto $concepto): View
    {
        return view('nomina.conceptos.edit', compact('concepto'));
    }

    public function update(Request $request, NominaConcepto $concepto): RedirectResponse
    {
        $validated = $request->validate([
            'codigo' => ['required', 'string', 'max:50', 'unique:rh_conceptos,codigo,' . $concepto->id],
            'nombre' => ['required', 'string', 'max:150'],
            'tipo' => ['required', 'in:INGRESO,DESCUENTO,FISCAL'],
            'origen' => ['required', 'in:MANUAL,IMPORTADO,CALCULADO'],
            'descripcion' => ['nullable', 'string', 'max:5000'],
            'activo' => ['nullable', 'boolean'],
        ]);

        $validated['codigo'] = strtoupper(trim($validated['codigo']));
        $validated['activo'] = $request->boolean('activo');

        $concepto->update($validated);

        return redirect()->route('nomina.conceptos.index')->with('success', 'Concepto actualizado correctamente.');
    }
}
