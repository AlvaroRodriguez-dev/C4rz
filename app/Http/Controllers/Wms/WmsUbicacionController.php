<?php

namespace App\Http\Controllers\Wms;

use App\Http\Controllers\Controller;
use App\Models\WmsAlmacen;
use App\Models\WmsGalpon;
use App\Models\WmsUbicacion;
use App\Services\WmsContextService;
use App\Services\WmsUbicacionService;
use Illuminate\Http\Request;

class WmsUbicacionController extends Controller
{
    public function index(Request $request)
    {
        $almacenes = WmsAlmacen::query()
            ->where('tipo', 'PRINCIPAL')
            ->orderBy('codigo')
            ->get();

        $almacen = null;

        if ($request->filled('almacen')) {
            $almacen = $almacenes->firstWhere('id', (int) $request->input('almacen'));
        }

        if (!$almacen) {
            $almacen = $almacenes->firstWhere('activo', true) ?? $almacenes->first();
        }

        $galpones = $almacen
            ? WmsGalpon::query()
                ->where('almacen_id', $almacen->id)
                ->orderBy('codigo')
                ->get()
            : collect();

        $galpon = null;

        if ($request->filled('galpon') && $almacen) {
            $galpon = $galpones->firstWhere('id', (int) $request->input('galpon'));
        }

        $tipo = strtoupper(trim((string) $request->input('tipo', '')));
        if (!in_array($tipo, ['NORMAL', 'PREPARACION', 'DESPACHO'], true)) {
            $tipo = '';
        }

        $estado = strtolower(trim((string) $request->input('estado', 'ACTIVO')));
        if (!in_array($estado, ['ACTIVO', 'INACTIVO', 'TODOS'], true)) {
            $estado = 'ACTIVO';
        }

        $busqueda = trim((string) $request->input('q', ''));

        $query = WmsUbicacion::query()
            ->with('galpon')
            ->when($almacen, fn ($q) => $q->where('almacen_id', $almacen->id))
            ->when($galpon, fn ($q) => $q->where('galpon_id', $galpon->id))
            ->when($tipo !== '', fn ($q) => $q->where('tipo', $tipo))
            ->when($estado === 'ACTIVO', fn ($q) => $q->where('activo', true))
            ->when($estado === 'INACTIVO', fn ($q) => $q->where('activo', false))
            ->when($busqueda !== '', function ($q) use ($busqueda) {
                $q->where(function ($sub) use ($busqueda) {
                    $sub->where('codigo', 'like', "%{$busqueda}%")
                        ->orWhere('numero', $busqueda);
                });
            })
            ->orderByRaw("CASE tipo WHEN 'NORMAL' THEN 1 WHEN 'PREPARACION' THEN 2 WHEN 'DESPACHO' THEN 3 ELSE 9 END")
            ->orderBy('numero')
            ->orderBy('codigo');

        $resumenQuery = clone $query;
        $resumen = [
            'total' => (clone $resumenQuery)->count(),
            'normales' => (clone $resumenQuery)->where('tipo', 'NORMAL')->count(),
            'especiales' => (clone $resumenQuery)->whereIn('tipo', ['PREPARACION', 'DESPACHO'])->count(),
            'activas' => (clone $resumenQuery)->where('activo', true)->count(),
        ];

        $ubicaciones = $query
            ->paginate(50)
            ->withQueryString();

        return view('wms.ubicaciones.index', compact(
            'almacenes',
            'almacen',
            'galpones',
            'galpon',
            'tipo',
            'estado',
            'busqueda',
            'resumen',
            'ubicaciones'
        ));
    }

    public function opciones(): \Illuminate\Http\JsonResponse
    {
        $almacen = $this->context->almacen();

        return response()->json([
            'almacen' => [
                'id' => $almacen->id,
                'codigo' => $almacen->codigo,
                'nombre' => $almacen->nombre,
            ],
            'galpones' => $this->ubicacionService
                ->galpones($this->context)
                ->map(fn (WmsGalpon $galpon) => [
                    'id' => $galpon->id,
                    'codigo' => $galpon->codigo,
                    'nombre' => $galpon->nombre,
                ])
                ->values(),
        ]);
    }

    public function porGalpon(WmsGalpon $galpon): \Illuminate\Http\JsonResponse
    {
        $almacen = $this->context->almacen();

        if ((int) $galpon->almacen_id !== (int) $almacen->id || !$galpon->activo) {
            abort(404);
        }

        return response()->json([
            'almacen' => $almacen->codigo,
            'galpon' => [
                'id' => $galpon->id,
                'codigo' => $galpon->codigo,
                'nombre' => $galpon->nombre,
            ],
            'ubicaciones' => $this->ubicacionService
                ->ubicacionesPorGalpon($galpon)
                ->map(fn (WmsUbicacion $ubicacion) => [
                    'id' => $ubicacion->id,
                    'codigo' => $ubicacion->codigo,
                    'numero' => $ubicacion->numero,
                ])
                ->values(),
        ]);
    }

}
