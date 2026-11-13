<?php

namespace App\Http\Controllers\Wms;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\WmsAlmacen;
use App\Models\WmsUsuarioAlmacen;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WmsUsuarioAlmacenController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->input('search'));

        $users = User::query()
            ->with(['wmsAlmacenes' => fn ($q) => $q->where('tipo', 'PRINCIPAL')->orderBy('codigo')])
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('wms.usuario-almacenes.index', compact('users', 'search'));
    }

    public function edit(User $user)
    {
        $almacenes = WmsAlmacen::query()
            ->where('tipo', 'PRINCIPAL')
            ->where('activo', true)
            ->orderBy('codigo')
            ->get();

        $asignaciones = WmsUsuarioAlmacen::query()
            ->where('user_id', $user->id)
            ->get()
            ->keyBy('almacen_id');

        return view('wms.usuario-almacenes.edit', compact('user', 'almacenes', 'asignaciones'));
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'almacenes' => ['nullable', 'array'],
            'almacenes.*' => ['integer', 'distinct', 'exists:wms_almacenes,id'],
            'principal' => ['nullable', 'integer'],
        ]);

        $seleccionados = collect($data['almacenes'] ?? [])->map(fn ($id) => (int) $id)->values();
        $principal = isset($data['principal']) ? (int) $data['principal'] : null;

        if ($principal !== null && !$seleccionados->contains($principal)) {
            return back()
                ->withErrors(['principal' => 'El almacén principal debe estar entre los almacenes asignados.'])
                ->withInput();
        }

        $permitidos = WmsAlmacen::query()
            ->where('tipo', 'PRINCIPAL')
            ->where('activo', true)
            ->whereIn('id', $seleccionados)
            ->pluck('id');

        if ($permitidos->count() !== $seleccionados->count()) {
            return back()
                ->withErrors(['almacenes' => 'Solo se pueden asignar almacenes PRINCIPALES activos.'])
                ->withInput();
        }

        DB::transaction(function () use ($user, $seleccionados, $principal) {
            WmsUsuarioAlmacen::where('user_id', $user->id)
                ->update(['activo' => false, 'es_principal' => false]);

            foreach ($seleccionados as $almacenId) {
                WmsUsuarioAlmacen::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'almacen_id' => $almacenId,
                    ],
                    [
                        'activo' => true,
                        'es_principal' => $principal === $almacenId,
                    ]
                );
            }
        });

        return redirect()
            ->route('wms.usuario-almacenes.index')
            ->with('success', "Almacenes WMS actualizados para {$user->name}.");
    }
}
