<?php

namespace App\Http\Controllers\Comercial;

use App\Http\Controllers\Controller;
use App\Models\Agencia;
use Illuminate\Http\Request;

class AgenciaController extends Controller
{
    public function index()
    {
        $agencias = Agencia::orderBy('descripcion')->paginate(15);
        return view('comercial.agencias.index', compact('agencias'));
    }

    public function create()
    {
        return view('comercial.agencias.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'codigo' => 'required|string|max:20|unique:agencias,codigo',
            'descripcion' => 'required|string|max:150',
            'ciudad' => 'required|string|max:100',
            'direccion' => 'required|string|max:255',
            'url_maps' => 'nullable|url|max:500',
        ]);

        Agencia::create($data);

        return redirect()->route('comercial.agencias.index')->with('success', 'Agencia creada correctamente.');
    }

    public function edit(Agencia $agencia)
    {
        return view('comercial.agencias.edit', compact('agencia'));
    }

    public function update(Request $request, Agencia $agencia)
    {
        $data = $request->validate([
            'codigo' => 'required|string|max:20|unique:agencias,codigo,'.$agencia->id,
            'descripcion' => 'required|string|max:150',
            'ciudad' => 'required|string|max:100',
            'direccion' => 'required|string|max:255',
            'url_maps' => 'nullable|url|max:500',
        ]);

        $agencia->update($data);

        return redirect()->route('comercial.agencias.index')->with('success', 'Agencia actualizada correctamente.');
    }

    public function destroy(Agencia $agencia)
    {
        $agencia->delete();
        return redirect()->route('comercial.agencias.index')->with('success', 'Agencia eliminada.');
    }
}