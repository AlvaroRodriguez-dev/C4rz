<?php

namespace App\Http\Controllers\Wms;

use App\Http\Controllers\Controller;
use App\Models\WmsHu;
use App\Models\WmsUbicacion;
use App\Models\WmsAlmacen;
use App\Models\WmsUsuarioAlmacen;
use App\Models\WmsIngreso;
use App\Services\SaldoService;
use Illuminate\Http\Request;

use App\Models\WmsReubicacion;
use App\Models\WmsSalida;
use Illuminate\Support\Facades\DB;

class WmsPalletVerController extends Controller
{
    public function __construct(private SaldoService $saldoService) {}

    public function index(Request $request)
    {
        if ($request->get('modo') === 'historico') {
            return view('wms.pallet-ver.index');
        }

        if ($request->filled('hu')) {
            $hu = WmsHu::query()
                ->with(['almacen', 'entrega', 'detalles.entregaDetalle'])
                ->findOrFail((int) $request->get('hu'));

            return view('wms.hu.show', compact('hu'));
        }

        $buscar = trim((string) $request->get('buscar'));
        $estado = trim((string) $request->get('estado'));

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
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        $estados = WmsHu::query()
            ->select('estado')
            ->whereNotNull('estado')
            ->distinct()
            ->orderBy('estado')
            ->pluck('estado');

        return view('wms.pallet-ver.hu-index', compact('hus', 'estados', 'buscar', 'estado'));
    }

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

    public function buscarUbicaciones(Request $request)
    {
        $q = trim((string) $request->get('q'));

        $deIngresos = WmsIngreso::query()->select('galpon', 'ubicacion');
        $deSalidas = WmsSalida::query()->select('galpon', 'ubicacion');
        $deReubicacionesOrigen = WmsReubicacion::query()->select('galpon_origen as galpon', 'ubicacion_origen as ubicacion');
        $deReubicacionesDestino = WmsReubicacion::query()->select('galpon_destino as galpon', 'ubicacion_destino as ubicacion');

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
        $almacenes = WmsAlmacen::query()
            ->where('tipo', 'PRINCIPAL')->where('activo', true)
            ->whereIn('id', WmsUsuarioAlmacen::query()
                ->where('user_id', auth()->id())->where('activo', true)->select('almacen_id'))
            ->orderBy('codigo')->get(['id', 'codigo', 'nombre']);

        return view('wms.ubicacion-ver.index', compact('almacenes'));
    }

    public function contenidoUbicacionAlmacen(Request $request, int $almacen, string $numero)
    {
        $almacenModel = WmsAlmacen::query()->where('id', $almacen)
            ->where('tipo', 'PRINCIPAL')->where('activo', true)
            ->whereHas('usuarios', fn($q) => $q->where('user_id', auth()->id())->where('activo', true))
            ->first();
        if (!$almacenModel) return response()->json(['message' => 'Almacén no autorizado o inactivo.'], 403);

        $ubicacion = WmsUbicacion::query()->with(['galpon', 'almacen'])
            ->where('almacen_id', $almacen)->where('numero', $numero)
            ->where('activo', true)->where('tipo', 'NORMAL')->first();

        if (!$ubicacion || !$ubicacion->galpon) {
            return response()->json(['message' => 'No se encontró una ubicación normal activa con ese número en el almacén seleccionado.'], 404);
        }

        $items = $this->saldoService->calcular([
            'almacen' => (string) $almacenModel->codigo,
            'galpon' => (string) $ubicacion->galpon->codigo,
            'ubicacion' => (string) $ubicacion->codigo,
        ]);

        return response()->json([
            'almacen' => $almacen,
            'galpon' => $ubicacion->galpon->codigo,
            'ubicacion' => $ubicacion->codigo,
            'fecha_lectura' => now()->format('d/m/Y H:i:s'),
            'items' => $items->values(),
            'total' => $items->sum('saldo'),
            'total_pallets' => $items->pluck('pallet')->unique()->count(),
        ]);
    }
}
