<?php

namespace App\Http\Controllers\Wms;

use App\Http\Controllers\Controller;
use App\Models\WmsHu;
use Illuminate\Http\Request;

class WmsHuController extends Controller
{
    public function index(Request $request)
    {
        $buscar = trim((string) $request->get('buscar'));
        $estado = trim((string) $request->get('estado'));
        $almacenId = $request->get('almacen_id');

        $hus = WmsHu::query()
            ->with(['almacen', 'entrega'])
            ->when($buscar !== '', function ($query) use ($buscar) {
                $query->where(function ($q) use ($buscar) {
                    $q->where('numero', 'like', "%{$buscar}%")
                        ->orWhere('formato', 'like', "%{$buscar}%")
                        ->orWhereHas('entrega', function ($entrega) use ($buscar) {
                            $entrega->where('documento_id', 'like', "%{$buscar}%")
                                ->orWhere('folio_fisico', 'like', "%{$buscar}%");
                        });
                });
            })
            ->when($estado !== '', fn ($query) => $query->where('estado', $estado))
            ->when($almacenId, fn ($query) => $query->where('almacen_id', $almacenId))
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        $estados = WmsHu::query()
            ->select('estado')
            ->whereNotNull('estado')
            ->distinct()
            ->orderBy('estado')
            ->pluck('estado');

        return view('wms.hu.index', compact('hus', 'estados', 'buscar', 'estado', 'almacenId'));
    }

    public function show(WmsHu $hu)
    {
        $hu->load(['almacen', 'entrega', 'detalles.entregaDetalle']);

        return view('wms.hu.show', compact('hu'));
    }
}
