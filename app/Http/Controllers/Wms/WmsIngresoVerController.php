<?php

namespace App\Http\Controllers\Wms;

use App\Http\Controllers\Controller;
use App\Models\WmsIngreso;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WmsIngresoVerController extends Controller
{
    public function index()
    {
        return view('wms.ingresos.ver');
    }

    /**
     * AJAX - listado paginado con búsqueda libre sobre múltiples campos.
     */
    public function buscar(Request $request)
    {
        $search = trim((string) $request->get('q'));
        $page = (int) $request->get('page', 1);

        $query = WmsIngreso::query()
            ->with('creador:id,name')
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('codigo', 'like', "%{$search}%")
                        ->orWhere('descrip', 'like', "%{$search}%")
                        ->orWhere('descrip1', 'like', "%{$search}%")
                        ->orWhere('pallet', 'like', "%{$search}%")
                        ->orWhere('clote', 'like', "%{$search}%")
                        ->orWhere('galpon', 'like', "%{$search}%")
                        ->orWhere('ubicacion', 'like', "%{$search}%")
                        ->orWhere('cantidad', 'like', "%{$search}%")
                        ->orWhere('rdocum', 'like', "%{$search}%")
                        ->orWhereRaw("DATE_FORMAT(rfecha, '%d/%m/%Y') like ?", ["%{$search}%"])
                        ->orWhereHas('creador', function ($u) use ($search) {
                            $u->where('name', 'like', "%{$search}%");
                        })
                        ->orWhere('motivo', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('created_at');

        $resultado = $query->paginate(15, ['*'], 'page', $page);

        $data = $resultado->through(function (WmsIngreso $ingreso) {
            return [
                'id' => $ingreso->id,
                'rdocum' => $ingreso->rdocum,
                'rfecha' => optional($ingreso->rfecha)->format('d/m/Y'),
                'tipo_ingreso' => $ingreso->tipo_ingreso,   // <-- nuevo
                'motivo' => $ingreso->motivo,                // <-- nuevo
                'pallet' => $ingreso->pallet,
                'codigo' => $ingreso->codigo,
                'clote' => $ingreso->clote,
                'descripcion' => trim("{$ingreso->descrip} {$ingreso->descrip1}"),
                'cantidad' => $ingreso->cantidad,
                'almacen' => $ingreso->almacen,
                'galpon' => $ingreso->galpon,
                'ubicacion' => $ingreso->ubicacion,
                'usuario' => $ingreso->creador->name ?? 'N/D',
                'creado' => $ingreso->created_at->format('d/m/Y H:i'),
            ];
        });

        return response()->json([
            'data' => $data->items(),
            'current_page' => $resultado->currentPage(),
            'last_page' => $resultado->lastPage(),
            'total' => $resultado->total(),
        ]);
    }
}
