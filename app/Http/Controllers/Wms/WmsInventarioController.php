<?php

namespace App\Http\Controllers\Wms;

use App\Http\Controllers\Controller;
use App\Models\WmsIngreso;
use App\Models\WmsUsuarioAlmacen;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WmsInventarioController extends Controller
{
    public function __construct(private \App\Services\SaldoService $saldoService) {}

    private function almacenesAutorizados()
    {
        return WmsUsuarioAlmacen::query()
            ->where('user_id', auth()->id())
            ->where('activo', true)
            ->whereHas('almacen', fn($q) => $q->where('activo', true))
            ->with('almacen')
            ->get()
            ->pluck('almacen')
            ->filter()
            ->unique('id')
            ->values();
    }

    public function index()
    {
        $almacenes = $this->almacenesAutorizados();
        return view('wms.inventario.index', compact('almacenes'));
    }

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
            ->orderBy('codigo')->limit(30)->get();

        return response()->json(['results' => $productos->map(fn($p) => [
            'id' => $p->codigo, 'text' => trim("{$p->codigo} · {$p->descrip} {$p->descrip1}")
        ])]);
    }

    public function inventario(Request $request)
    {
        $almacenes = $this->almacenesAutorizados();
        if ($almacenes->isEmpty()) return response()->json(['message' => 'No tiene almacenes asignados.'], 403);

        $codigoAlmacenes = $almacenes->pluck('codigo')->all();
        $seleccion = (string) $request->input('almacen', '');
        $codigoFiltro = null;
        if ($seleccion === 'all') {
            $codigoFiltro = $codigoAlmacenes;
        } elseif ($seleccion !== '') {
            $almacen = $almacenes->firstWhere('id', (int)$seleccion);
            if (!$almacen) return response()->json(['message' => 'Almacén no autorizado.'], 403);
            $codigoFiltro = [$almacen->codigo];
        } elseif ($almacenes->count() === 1) {
            $codigoFiltro = [$almacenes->first()->codigo];
        } else {
            return response()->json(['message' => 'Seleccione un almacén o inventario consolidado.'], 422);
        }

        $filtros = [
            'codigo' => $request->filled('codigo') ? trim($request->codigo) : null,
            'pallet' => $request->filled('pallet') ? trim($request->pallet) : null,
            'galpon' => $request->filled('galpon') ? trim($request->galpon) : null,
            'ubicacion' => $request->filled('ubicacion') ? trim($request->ubicacion) : null,
        ];

        $saldos = collect();
        foreach ($codigoFiltro as $codAlmacen) {
            $saldos = $saldos->concat($this->saldoService->calcular(array_filter($filtros) + ['almacen' => $codAlmacen]));
        }
        if ($request->filled('lote')) $saldos = $saldos->filter(fn($s) => stripos((string)$s['clote'], trim($request->lote)) !== false);
        $saldos = $saldos->values();

        $porPallet = $saldos->groupBy(fn($s) => implode('|', [$s['almacen'],$s['galpon'],$s['ubicacion'],$s['pallet']]))
            ->map(function($items) {
                $first=$items->first();
                return ['almacen'=>$first['almacen'],'galpon'=>$first['galpon'],'ubicacion'=>$first['ubicacion'],'pallet'=>$first['pallet'],'total'=>$items->sum('saldo'),
                    'productos'=>$items->map(fn($s)=>['codigo'=>$s['codigo'],'descrip'=>$s['descrip'],'descrip1'=>$s['descrip1'],'clote'=>$s['clote'] ?: 'S/L','saldo'=>$s['saldo']])->values()];
            })->values();

        $total = $saldos->sum('saldo');
        return response()->json([
            'total_cajas'=>$total,
            'total_pallets'=>$saldos->pluck('pallet')->unique()->count(),
            'total_productos'=>$saldos->pluck('codigo')->unique()->count(),
            'total_ubicaciones'=>$saldos->map(fn($s)=>implode('|',[$s['almacen'],$s['galpon'],$s['ubicacion']]))->unique()->count(),
            'detalle'=>$saldos->map(fn($s)=>['almacen'=>$s['almacen'],'galpon'=>$s['galpon'],'ubicacion'=>$s['ubicacion'],'pallet'=>$s['pallet'],'codigo'=>$s['codigo'],'descrip'=>trim(($s['descrip']??'').' '.($s['descrip1']??'')),'lote'=>$s['clote'] ?: 'S/L','saldo'=>$s['saldo']])->values(),
            'por_pallet'=>$porPallet,
        ]);
    }

    public function saldos(Request $request, string $codigo)
    {
        $almacenes=$this->almacenesAutorizados();
        if($almacenes->isEmpty()) return response()->json(['message'=>'No tiene almacenes asignados.'],403);
        $selected=(string)$request->query('almacen','');
        $codes=$selected==='all' ? $almacenes->pluck('codigo')->all() : ($selected!=='' ? ($almacenes->firstWhere('id',(int)$selected)?->codigo ? [$almacenes->firstWhere('id',(int)$selected)->codigo] : null) : ($almacenes->count()===1 ? [$almacenes->first()->codigo] : null));
        if(!$codes) return response()->json(['message'=>'Seleccione un almacén autorizado.'],422);
        $saldos=collect();
        foreach($codes as $code) $saldos=$saldos->concat($this->saldoService->calcular(['codigo'=>$codigo,'almacen'=>$code]));
        $saldos=$saldos->values();
        return response()->json(['total_general'=>$saldos->sum('saldo'),'total_pallets'=>$saldos->pluck('pallet')->unique()->count(),'detalle'=>$saldos->values()]);
    }
}
