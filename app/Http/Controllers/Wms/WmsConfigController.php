<?php

namespace App\Http\Controllers\Wms;

use App\Http\Controllers\Controller;
use App\Models\WmsConfigPallet;
use Illuminate\Http\Request;

class WmsConfigController extends Controller
{
    public function index()
    {
        $configuraciones = WmsConfigPallet::orderBy('codigo')->get();

        return view('wms.configurar.index', compact('configuraciones'));
    }

    public function create()
    {
        return view('wms.configurar.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'codigo' => [
                'required',
                'alpha_num',
                'size:4',
                'uppercase',
                'unique:wms_config_pallets,codigo',
            ],
            'descripcion' => [
                'required',
                'string',
                'max:20',
            ],
            'cajas_x_pallet' => [
                'required',
                'integer',
                'min:1',
                
            ],
        ], [
            'codigo.size' => 'El código debe tener exactamente 4 caracteres.',
            'codigo.alpha_num' => 'El código solo admite letras y números.',
            'codigo.unique' => 'Ya existe una configuración con ese código.',
            'cajas_x_pallet.unique' => 'Ya existe una configuración con esa misma cantidad de cajas x pallet.',
        ]);

        $validated['codigo'] = strtoupper($validated['codigo']);

        WmsConfigPallet::create($validated);

        return redirect()
            ->route('wms.configurar.index')
            ->with('success', 'Configuración registrada correctamente.');
    }

    public function destroy(string $codigo)
    {
        WmsConfigPallet::findOrFail($codigo)->delete();

        return redirect()
            ->route('wms.configurar.index')
            ->with('success', 'Configuración eliminada.');
    }
}