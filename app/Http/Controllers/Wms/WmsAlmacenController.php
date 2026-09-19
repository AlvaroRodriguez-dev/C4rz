<?php

namespace App\Http\Controllers\Wms;

use App\Http\Controllers\Controller;
use App\Models\WmsAlmacen;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class WmsAlmacenController extends Controller
{
    public function index()
    {
        $almacenes = WmsAlmacen::with('padre')
            ->orderBy('tipo')
            ->orderBy('codigo')
            ->get();

        return view('wms.almacenes.index', compact('almacenes'));
    }

    public function create()
    {
        $padres = WmsAlmacen::where('tipo', 'PRINCIPAL')
            ->where('activo', true)
            ->orderBy('codigo')
            ->get();

        return view('wms.almacenes.create', compact('padres'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'codigo' => [
                'required',
                'string',
                'max:10',
                'alpha_num',
                'unique:wms_almacenes,codigo',
            ],
            'nombre' => ['required', 'string', 'max:100'],
            'tipo' => ['required', Rule::in(['PRINCIPAL', 'SUB'])],
            'almacen_padre_id' => [
                'nullable',
                'integer',
                'exists:wms_almacenes,id',
                Rule::requiredIf(fn () => $request->input('tipo') === 'SUB'),
            ],
            'prefijo_documento' => ['nullable', 'string', 'max:5'],
            'activo' => ['nullable', 'boolean'],
        ], [
            'codigo.unique' => 'Ya existe un almacén con ese código.',
            'codigo.alpha_num' => 'El código solo admite letras y números.',
            'almacen_padre_id.required' => 'Un almacén SUB debe tener un almacén principal como padre.',
        ]);

        if ($validated['tipo'] === 'PRINCIPAL') {
            $validated['almacen_padre_id'] = null;
        }

        $validated['codigo'] = strtoupper($validated['codigo']);
        $validated['prefijo_documento'] = isset($validated['prefijo_documento'])
            ? strtoupper($validated['prefijo_documento'])
            : null;
        $validated['activo'] = $request->boolean('activo', true);

        WmsAlmacen::create($validated);

        return redirect()
            ->route('wms.almacenes.index')
            ->with('success', 'Almacén registrado correctamente.');
    }

    public function edit(WmsAlmacen $almacen)
    {
        $padres = WmsAlmacen::where('tipo', 'PRINCIPAL')
            ->where('activo', true)
            ->whereKeyNot($almacen->id)
            ->orderBy('codigo')
            ->get();

        return view('wms.almacenes.edit', compact('almacen', 'padres'));
    }

    public function update(Request $request, WmsAlmacen $almacen)
    {
        $validated = $request->validate([
            'codigo' => [
                'required',
                'string',
                'max:10',
                'alpha_num',
                Rule::unique('wms_almacenes', 'codigo')->ignore($almacen->id),
            ],
            'nombre' => ['required', 'string', 'max:100'],
            'tipo' => ['required', Rule::in(['PRINCIPAL', 'SUB'])],
            'almacen_padre_id' => [
                'nullable',
                'integer',
                'exists:wms_almacenes,id',
                Rule::requiredIf(fn () => $request->input('tipo') === 'SUB'),
            ],
            'prefijo_documento' => ['nullable', 'string', 'max:5'],
            'activo' => ['nullable', 'boolean'],
        ], [
            'codigo.unique' => 'Ya existe un almacén con ese código.',
            'codigo.alpha_num' => 'El código solo admite letras y números.',
            'almacen_padre_id.required' => 'Un almacén SUB debe tener un almacén principal como padre.',
        ]);

        if ($validated['tipo'] === 'PRINCIPAL') {
            $validated['almacen_padre_id'] = null;
        }

        if ((int) ($validated['almacen_padre_id'] ?? 0) === $almacen->id) {
            return back()
                ->withErrors(['almacen_padre_id' => 'Un almacén no puede ser padre de sí mismo.'])
                ->withInput();
        }

        $validated['codigo'] = strtoupper($validated['codigo']);
        $validated['prefijo_documento'] = isset($validated['prefijo_documento'])
            ? strtoupper($validated['prefijo_documento'])
            : null;
        $validated['activo'] = $request->boolean('activo');

        $almacen->update($validated);

        return redirect()
            ->route('wms.almacenes.index')
            ->with('success', 'Almacén actualizado correctamente.');
    }
}
