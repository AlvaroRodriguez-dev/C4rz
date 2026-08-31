<?php

namespace App\Http\Controllers;

use App\Models\RrhhAgencia;        // ← modelo actualizado
use App\Models\User;
use Illuminate\Http\Request;

class RrhhAgenciasController extends Controller
{
    public function index()
    {
        $agencias = RrhhAgencia::withCount('users')
            ->orderBy('nombre')
            ->get();
        return view('rrhh.agencias.index', compact('agencias'));
    }

    public function create()
    {
        return view('rrhh.agencias.form', ['agencia' => new RrhhAgencia()]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'codigo'     => 'required|string|max:20|unique:rrhh_agencias,codigo',
            'nombre'     => 'required|string|max:150',
            'latitud'    => 'required|numeric|between:-90,90',
            'longitud'   => 'required|numeric|between:-180,180',
            'tolerancia' => 'required|integer|min:10|max:5000',
        ]);

        RrhhAgencia::create($request->only(
            'codigo',
            'nombre',
            'latitud',
            'longitud',
            'tolerancia'
        ));

        return redirect()->route('rrhh.agencias.index')
            ->with('success', 'Agencia creada correctamente.');
    }

    public function edit(RrhhAgencia $agencia)
    {
        return view('rrhh.agencias.form', compact('agencia'));
    }

    public function update(Request $request, RrhhAgencia $agencia)
    {
        $request->validate([
            'codigo'     => 'required|string|max:20|unique:rrhh_agencias,codigo,' . $agencia->id,
            'nombre'     => 'required|string|max:150',
            'latitud'    => 'required|numeric|between:-90,90',
            'longitud'   => 'required|numeric|between:-180,180',
            'tolerancia' => 'required|integer|min:10|max:5000',
            'activo'     => 'boolean',
        ]);

        $agencia->update($request->only(
            'codigo',
            'nombre',
            'latitud',
            'longitud',
            'tolerancia',
            'activo'
        ));

        return redirect()->route('rrhh.agencias.index')
            ->with('success', 'Agencia actualizada correctamente.');
    }

    public function destroy(RrhhAgencia $agencia)
    {
        $agencia->delete();
        return redirect()->route('rrhh.agencias.index')
            ->with('success', 'Agencia eliminada correctamente.');
    }

    public function asignaciones()
    {
        $usuarios = User::with('rrhhAgencias')
            ->orderBy('name')
            ->get();                    // ← sin whereNotNull('license')

        $agencias = RrhhAgencia::where('activo', true)
            ->orderBy('nombre')
            ->get();

        return view('rrhh.agencias.asignaciones', compact('usuarios', 'agencias'));
    }

    public function guardarAsignacion(Request $request)
    {
        $request->validate([
            'user_id'       => 'required|exists:users,id',
            'agencia_ids'   => 'nullable|array',
            'agencia_ids.*' => 'exists:rrhh_agencias,id',    // ← tabla actualizada
        ]);

        $user = User::findOrFail($request->user_id);
        $user->rrhhAgencias()->sync($request->agencia_ids ?? []);    // ← relación actualizada

        return response()->json([
            'success' => true,
            'message' => 'Agencias asignadas correctamente.',
            'total'   => count($request->agencia_ids ?? []),
        ]);
    }
}
