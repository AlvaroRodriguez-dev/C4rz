<?php

namespace App\Http\Controllers\Wms;

use App\Http\Controllers\Controller;
use App\Models\WmsAlmacen;
use App\Models\WmsGalpon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class WmsGalponController extends Controller
{
    public function index(Request $request)
    {
        $almacenes = WmsAlmacen::where('tipo', 'PRINCIPAL')
            ->where('activo', true)
            ->orderBy('codigo')
            ->get();

        $almacen = null;

        if ($request->filled('almacen')) {
            $almacen = $almacenes->firstWhere('id', (int) $request->input('almacen'));
        }

        if (!$almacen) {
            $almacen = $almacenes->first();
        }

        $galpones = $almacen
            ? WmsGalpon::where('almacen_id', $almacen->id)
                ->withCount('ubicaciones')
                ->withCount(['rangos as rangos_count' => fn ($query) => $query->where('activo', true)])
                ->orderBy('codigo')
                ->get()
            : collect();

        return view('wms.galpones.index', compact('almacenes', 'almacen', 'galpones'));
    }

    public function create()
    {
        $almacenes = WmsAlmacen::where('tipo', 'PRINCIPAL')
            ->where('activo', true)
            ->orderBy('codigo')
            ->get();

        return view('wms.galpones.create', compact('almacenes'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'almacen_id' => [
                'required',
                'integer',
                Rule::exists('wms_almacenes', 'id')->where(fn ($query) =>
                    $query->where('tipo', 'PRINCIPAL')->where('activo', true)
                ),
            ],
            'codigo' => [
                'required',
                'string',
                'max:20',
                'regex:/^[A-Za-z0-9_-]+$/',
            ],
            'nombre' => ['required', 'string', 'max:100'],
            'activo' => ['nullable', 'boolean'],
        ], [
            'codigo.regex' => 'El código solo admite letras, números, guion y guion bajo.',
            'almacen_id.exists' => 'El almacén seleccionado no está disponible.',
        ]);

        $codigo = strtoupper($validated['codigo']);

        if (WmsGalpon::where('almacen_id', $validated['almacen_id'])
            ->whereRaw('UPPER(codigo) = ?', [$codigo])
            ->exists()) {
            return back()
                ->withErrors(['codigo' => 'Ya existe un galpón con ese código en el almacén seleccionado.'])
                ->withInput();
        }

        WmsGalpon::create([
            'almacen_id' => $validated['almacen_id'],
            'codigo' => $codigo,
            'nombre' => $validated['nombre'],
            'desde_ubicacion' => null,
            'hasta_ubicacion' => null,
            'activo' => $request->boolean('activo', true),
        ]);

        return redirect()
            ->route('wms.galpones.index', ['almacen' => $validated['almacen_id']])
            ->with('success', 'Galpón registrado correctamente.');
    }

    public function edit(WmsGalpon $galpon)
    {
        $almacenes = WmsAlmacen::where('tipo', 'PRINCIPAL')
            ->where('activo', true)
            ->orderBy('codigo')
            ->get();

        return view('wms.galpones.edit', compact('galpon', 'almacenes'));
    }

    public function update(Request $request, WmsGalpon $galpon)
    {
        $validated = $request->validate([
            'almacen_id' => [
                'required',
                'integer',
                Rule::exists('wms_almacenes', 'id')->where(fn ($query) =>
                    $query->where('tipo', 'PRINCIPAL')->where('activo', true)
                ),
            ],
            'codigo' => [
                'required',
                'string',
                'max:20',
                'regex:/^[A-Za-z0-9_-]+$/',
            ],
            'nombre' => ['required', 'string', 'max:100'],
            'activo' => ['nullable', 'boolean'],
        ], [
            'codigo.regex' => 'El código solo admite letras, números, guion y guion bajo.',
            'almacen_id.exists' => 'El almacén seleccionado no está disponible.',
        ]);

        $codigo = strtoupper($validated['codigo']);

        if ((int) $validated['almacen_id'] !== (int) $galpon->almacen_id) {
            $tieneConfiguracion = $galpon->ubicaciones()->exists() || $galpon->rangos()->exists();

            if ($tieneConfiguracion) {
                return back()
                    ->withErrors([
                        'almacen_id' => 'No se puede cambiar de almacén un galpón que ya tiene tramos o posiciones configuradas.',
                    ])
                    ->withInput();
            }
        }

        $duplicado = WmsGalpon::where('almacen_id', $validated['almacen_id'])
            ->whereRaw('UPPER(codigo) = ?', [$codigo])
            ->whereKeyNot($galpon->id)
            ->exists();

        if ($duplicado) {
            return back()
                ->withErrors(['codigo' => 'Ya existe un galpón con ese código en el almacén seleccionado.'])
                ->withInput();
        }

        $galpon->update([
            'almacen_id' => $validated['almacen_id'],
            'codigo' => $codigo,
            'nombre' => $validated['nombre'],
            'activo' => $request->boolean('activo'),
        ]);

        return redirect()
            ->route('wms.galpones.index', ['almacen' => $galpon->almacen_id])
            ->with('success', 'Galpón actualizado correctamente.');
    }
}
