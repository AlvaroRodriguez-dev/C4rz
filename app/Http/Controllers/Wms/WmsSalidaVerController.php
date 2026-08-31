<?php

namespace App\Http\Controllers\Wms;

use App\Http\Controllers\Controller;
use App\Models\WmsSalida;
use Illuminate\Http\Request;

class WmsSalidaVerController extends Controller
{
    public function index()
    {
        return view('wms.salidas.ver');
    }

    /**
     * AJAX - listado paginado con búsqueda libre sobre múltiples campos.
     */
    public function buscar(Request $request)
    {
        $search = trim((string) $request->get('q'));
        $page = (int) $request->get('page', 1);

        $query = WmsSalida::query()
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
                        ->orWhere('id_registro', 'like', "%{$search}%")
                        ->orWhere('glosa', 'like', "%{$search}%")
                        ->orWhere('tipo_registro', 'like', "%{$search}%")
                        ->orWhereRaw("DATE_FORMAT(created_at, '%d/%m/%Y') like ?", ["%{$search}%"])
                        ->orWhereHas('creador', function ($u) use ($search) {
                            $u->where('name', 'like', "%{$search}%");
                        });
                });
            })
            ->orderByDesc('created_at');

        $resultado = $query->paginate(15, ['*'], 'page', $page);

        $data = $resultado->through(function (WmsSalida $salida) {
            return [
                'id' => $salida->id,
                'tipo_registro' => $salida->tipo_registro,
                'id_registro' => $salida->id_registro,
                'glosa' => $salida->glosa,
                'pallet' => $salida->pallet,
                'codigo' => $salida->codigo,
                'clote' => $salida->clote,
                'descripcion' => trim("{$salida->descrip} {$salida->descrip1}"),
                'cantidad' => $salida->cantidad,
                'almacen' => $salida->almacen,
                'galpon' => $salida->galpon,
                'ubicacion' => $salida->ubicacion,
                'usuario' => $salida->creador->name ?? 'N/D',
                'creado' => $salida->created_at->format('d/m/Y H:i'),
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