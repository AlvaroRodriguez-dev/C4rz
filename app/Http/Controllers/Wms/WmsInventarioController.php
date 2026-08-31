<?php

namespace App\Http\Controllers\Wms;

use App\Http\Controllers\Controller;
use App\Models\WmsIngreso;
use App\Models\WmsSalida;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WmsInventarioController extends Controller
{

    public function __construct(private \App\Services\SaldoService $saldoService) {}

    public function index()
    {
        return view('wms.inventario.index');
    }

    /**
     * AJAX - Select2: productos distintos ya registrados en wms_ingresos.
     */
    public function buscarProductos(Request $request)
    {
        $q = trim((string) $request->get('q'));

        $productos = WmsIngreso::query()
            ->select('codigo', 'descrip', 'descrip1')
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('codigo', 'like', "%{$q}%")
                        ->orWhere('descrip', 'like', "%{$q}%")
                        ->orWhere('descrip1', 'like', "%{$q}%");
                });
            })
            ->groupBy('codigo', 'descrip', 'descrip1')
            ->orderBy('codigo')
            ->limit(30)
            ->get();

        $resultados = $productos->map(fn($p) => [
            'id' => $p->codigo,
            'text' => trim("{$p->codigo} · {$p->descrip} {$p->descrip1}"),
        ]);

        return response()->json(['results' => $resultados]);
    }

    /**
     * AJAX - Saldos agrupados de un producto: total general, por pallet, por ubicación.
     */
    public function saldos(string $codigo)
    {
        $saldos = $this->calcularSaldosPorClave($codigo);

        $totalGeneral = collect($saldos)->sum('saldo');
        $totalPallets = collect($saldos)->pluck('pallet')->unique()->count();   // <-- nuevo

        $porPallet = collect($saldos)
            ->groupBy('pallet')
            ->map(function ($items, $pallet) {
                $primero = $items->first();

                return [
                    'pallet' => $pallet,
                    'galpon' => $primero['galpon'],
                    'ubicacion' => $primero['ubicacion'],
                    'total_pallet' => $items->sum('saldo'),
                    'lotes' => $items->groupBy('clote')->map(function ($grupo, $clote) {
                        return [
                            'clote' => $clote ?: 'S/L',
                            'total' => $grupo->sum('saldo'),
                        ];
                    })->values(),
                ];
            })
            ->sortBy('pallet')
            ->values();

        $porUbicacion = collect($saldos)
            ->groupBy(fn($item) => "{$item['galpon']}|{$item['ubicacion']}")
            ->map(function ($items) {
                $primero = $items->first();

                return [
                    'galpon' => $primero['galpon'],
                    'ubicacion' => $primero['ubicacion'],
                    'total_ubicacion' => $items->sum('saldo'),
                    'lotes' => $items->groupBy('clote')->map(function ($grupo, $clote) {
                        return [
                            'clote' => $clote ?: 'S/L',
                            'total' => $grupo->sum('saldo'),
                        ];
                    })->values(),
                ];
            })
            ->sortBy(fn($item) => "{$item['galpon']}|{$item['ubicacion']}")
            ->values();

        return response()->json([
            'total_general' => $totalGeneral,
            'total_pallets' => $totalPallets,   // <-- nuevo
            'por_pallet' => $porPallet,
            'por_ubicacion' => $porUbicacion,
        ]);
    }

    /**
     * Saldo = ingresos - salidas, agrupado por pallet+clote+almacen+galpon+ubicacion.
     * Misma lógica usada en WmsSalidaController.
     */
    private function calcularSaldosPorClave(string $codigo): array
    {
        return $this->saldoService->calcular(['codigo' => $codigo])->all();
    }
}
