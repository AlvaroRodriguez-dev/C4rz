<?php

namespace App\Http\Controllers\Comercial;

use App\Http\Controllers\Controller;
use App\Models\Agencia;
use App\Models\ComercialContacto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ComercialContactoController extends Controller
{
    public function index(Request $request)
    {
        $query = ComercialContacto::with('agencia');

        if ($buscar = $request->input('buscar')) {
            $query->where(function ($q) use ($buscar) {
                $q->where('nombre', 'like', "%{$buscar}%")
                  ->orWhere('cargo', 'like', "%{$buscar}%")
                  ->orWhere('telefono', 'like', "%{$buscar}%")
                  ->orWhereHas('agencia', function ($qa) use ($buscar) {
                      $qa->where('descripcion', 'like', "%{$buscar}%");
                  });
            });
        }

        if ($request->filled('estado')) {
            $query->where('activo', $request->input('estado') === 'activo');
        }

        $contactos = $query->orderBy('nombre')->paginate(15)->withQueryString();

        return view('comercial.contactos.index', compact('contactos'));
    }

    public function create()
    {
        $agencias = Agencia::orderBy('descripcion')->get();
        return view('comercial.contactos.create', compact('agencias'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['activo'] = $request->boolean('activo');

        if ($request->hasFile('foto')) {
            $data['foto'] = $request->file('foto')->store('', 'fotos_publicas');
        }

        $contacto = ComercialContacto::create($data);

        return redirect()->route('comercial.contactos.show', $contacto)->with('success', 'Contacto creado correctamente.');
    }

    public function show(ComercialContacto $contacto)
    {
        $contacto->load('agencia');
        return view('comercial.contactos.show', compact('contacto'));
    }

    public function edit(ComercialContacto $contacto)
    {
        $agencias = Agencia::orderBy('descripcion')->get();
        return view('comercial.contactos.edit', compact('contacto', 'agencias'));
    }

    public function update(Request $request, ComercialContacto $contacto)
    {
        $data = $this->validated($request, $contacto->id);
        $data['activo'] = $request->boolean('activo');

        if ($request->hasFile('foto')) {
            if ($contacto->foto) {
                Storage::disk('fotos_publicas')->delete($contacto->foto);
            }
            $data['foto'] = $request->file('foto')->store('', 'fotos_publicas');
        }

        $contacto->update($data);

        return redirect()->route('comercial.contactos.show', $contacto)->with('success', 'Contacto actualizado correctamente.');
    }

    public function destroy(ComercialContacto $contacto)
    {
        $contacto->delete();
        return redirect()->route('comercial.contactos.index')->with('success', 'Contacto eliminado.');
    }

    public function restore($id)
    {
        $contacto = ComercialContacto::withTrashed()->findOrFail($id);
        $contacto->restore();
        return redirect()->route('comercial.contactos.index')->with('success', 'Contacto restaurado.');
    }

    private function validated(Request $request, $ignoreId = null): array
    {
        return $request->validate([
            'nombre' => 'required|string|max:150',
            'cargo' => 'required|string|max:150',
            'telefono' => 'required|string|max:30',
            'email' => 'required|email|max:150',
            'agencia_id' => 'required|exists:agencias,id',
            'foto' => 'nullable|image|max:2048',
        ]);
    }
}
