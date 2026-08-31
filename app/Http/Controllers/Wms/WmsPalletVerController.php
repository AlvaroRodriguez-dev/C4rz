<?php

namespace App\Http\Controllers\Wms;

use App\Http\Controllers\Controller;
use App\Models\WmsIngreso;
use App\Services\SaldoService;
use Illuminate\Http\Request;

use App\Models\WmsReubicacion;
use App\Models\WmsSalida;
use Illuminate\Support\Facades\DB;

class WmsPalletVerController extends Controller
{
    public function __construct(private SaldoService $saldoService) {}

    public function index()
    {
        return view('wms.pallet-ver.index');
    }

    /** AJAX - Select2: pallets existentes que coinciden con el término. */
    public function buscarPallets(Request $request)
    {
        $q = trim((string) $request->get('q'));

        $pallets = WmsIngreso::query()
            ->when($q !== '', fn($query) => $query->where('pallet', 'like', "%{$q}%"))
            ->distinct()
            ->orderByDesc('pallet')
            ->limit(30)
            ->pluck('pallet');

        return response()->json(['results' => $pallets->map(fn($p) => ['id' => $p, 'text' => $p])]);
    }

    /** AJAX - Contenido del pallet (por número o leído por QR). */
    public function contenidoPallet(string $pallet)
    {
        $items = $this->saldoService->calcular(['pallet' => $pallet]);

        return response()->json([
            'pallet' => $pallet,
            'fecha_lectura' => now()->format('d/m/Y H:i:s'),
            'items' => $items->values(),
            'total' => $items->sum('saldo'),
        ]);
    }

    /** AJAX - Contenido de una ubicación (por QR de Galpón/Ubicación). */
    public function contenidoUbicacion(string $galpon, string $ubicacion)
    {
        $items = $this->saldoService->calcular(['galpon' => $galpon, 'ubicacion' => $ubicacion]);

        return response()->json([
            'galpon' => $galpon,
            'ubicacion' => $ubicacion,
            'fecha_lectura' => now()->format('d/m/Y H:i:s'),
            'items' => $items->values(),
            'total' => $items->sum('saldo'),
            'total_pallets' => $items->pluck('pallet')->unique()->count(),
        ]);
    }

    /** AJAX - Select2: combinaciones Galpón/Ubicación existentes. */
    
    /** AJAX - Select2: combinaciones Galpón/Ubicación existentes en TODAS las fuentes. */
    public function buscarUbicaciones(Request $request)
    {
        $q = trim((string) $request->get('q'));

        $deIngresos = WmsIngreso::query()->select('galpon', 'ubicacion');

        $deSalidas = WmsSalida::query()->select('galpon', 'ubicacion');

        $deReubicacionesOrigen = WmsReubicacion::query()
            ->select('galpon_origen as galpon', 'ubicacion_origen as ubicacion');

        $deReubicacionesDestino = WmsReubicacion::query()
            ->select('galpon_destino as galpon', 'ubicacion_destino as ubicacion');

        $ubicaciones = $deIngresos
            ->unionAll($deSalidas)
            ->unionAll($deReubicacionesOrigen)
            ->unionAll($deReubicacionesDestino);

        $resultado = DB::query()
            ->fromSub($ubicaciones, 'u')
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('galpon', 'like', "%{$q}%")
                        ->orWhere('ubicacion', 'like', "%{$q}%");
                });
            })
            ->select('galpon', 'ubicacion')
            ->distinct()
            ->orderBy('galpon')
            ->orderBy('ubicacion')
            ->limit(30)
            ->get();

        $resultados = $resultado->map(fn($u) => [
            'id' => "{$u->galpon}|{$u->ubicacion}",
            'text' => "Galpón {$u->galpon} · Ubic. {$u->ubicacion}",
            'galpon' => $u->galpon,
            'ubicacion' => $u->ubicacion,
        ]);

        return response()->json(['results' => $resultados]);
    }

    public function indexUbicacion()
    {
        return view('wms.ubicacion-ver.index');
    }
}
