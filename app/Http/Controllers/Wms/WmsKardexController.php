<?php

namespace App\Http\Controllers\Wms;

use App\Http\Controllers\Controller;
use App\Models\WmsIngreso;
use App\Models\WmsReubicacion;
use App\Models\WmsSalida;
use App\Models\WmsUsuarioAlmacen;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class WmsKardexController extends Controller
{
    private function almacenesAutorizados()
    {
        return WmsUsuarioAlmacen::where('user_id', auth()->id())->where('activo', true)
            ->whereHas('almacen', fn($q) => $q->where('activo', true))->with('almacen')
            ->get()->pluck('almacen')->filter()->unique('id')->values();
    }

    private function almacenCodigo(Request $request): ?string
    {
        $almacenes = $this->almacenesAutorizados();
        $seleccion = (string)$request->input('almacen', '');
        if ($seleccion === '' && $almacenes->count() === 1) return $almacenes->first()->codigo;
        return $seleccion !== '' ? ($almacenes->firstWhere('id', (int)$seleccion)?->codigo) : null;
    }

    public function index()
    {
        return view('wms.kardex.index', ['almacenes' => $this->almacenesAutorizados()]);
    }

    public function lotes(Request $request, string $codigo)
    {
        $almacen = $this->almacenCodigo($request);
        if (!$almacen) return response()->json(['results'=>[]], 403);
        $lotes = WmsIngreso::where('codigo',$codigo)->where('almacen',$almacen)->whereNotNull('clote')->distinct()->orderBy('clote')->pluck('clote');
        return response()->json(['results'=>$lotes->map(fn($l)=>['id'=>$l,'text'=>$l])]);
    }

    public function galpones(Request $request, string $codigo)
    {
        $almacen=$this->almacenCodigo($request);
        if(!$almacen) return response()->json(['results'=>[]],403);
        $galpones=WmsIngreso::where('codigo',$codigo)->where('almacen',$almacen)->when($request->get('clote'),fn($q,$v)=>$q->where('clote',$v))->distinct()->orderBy('galpon')->pluck('galpon');
        return response()->json(['results' => $galpones->map(fn($g) => ['id' => $g, 'text' => $g])]);
    }

    public function ubicaciones(Request $request, string $codigo)
    {
        $almacen=$this->almacenCodigo($request);
        if(!$almacen) return response()->json(['results'=>[]],403);
        $ubicaciones=WmsIngreso::where('codigo',$codigo)->where('almacen',$almacen)->when($request->get('clote'),fn($q,$v)=>$q->where('clote',$v))->when($request->get('galpon'),fn($q,$v)=>$q->where('galpon',$v))->distinct()->orderBy('ubicacion')->pluck('ubicacion');
        return response()->json(['results' => $ubicaciones->map(fn($u) => ['id' => $u, 'text' => $u])]);
    }

    public function pallets(Request $request, string $codigo)
    {
        $almacen=$this->almacenCodigo($request);
        if(!$almacen) return response()->json(['results'=>[]],403);
        $pallets=WmsIngreso::where('codigo',$codigo)->where('almacen',$almacen)->distinct()->orderBy('pallet')->pluck('pallet');
        return response()->json(['results'=>$pallets->map(fn($p)=>['id'=>$p,'text'=>$p])]);
    }

    public function reporte(Request $request)
    {
        $codigo=$request->get('codigo'); $clote=$request->get('clote'); $galpon=$request->get('galpon');
        $ubicacion=$request->get('ubicacion'); $pallet=$request->get('pallet');
        $almacen=$this->almacenCodigo($request);
        if(!$almacen) return response()->json(['errors'=>['general'=>['Seleccione un almacén autorizado.']]],422);
        if(!$codigo || !$request->get('fecha_inicio') || !$request->get('fecha_fin'))
            return response()->json(['errors'=>['general'=>['Producto y periodo son obligatorios.']]],422);
        $desde=Carbon::parse($request->get('fecha_inicio'))->startOfDay(); $hasta=Carbon::parse($request->get('fecha_fin'))->endOfDay();
        $movimientos=$this->obtenerMovimientos($codigo,$almacen,$clote,$galpon,$ubicacion,$pallet);
        $saldoInicial=$movimientos->filter(fn($m)=>$m['fecha']->lt($desde))->sum('cantidad');
        $delPeriodo=$movimientos->filter(fn($m)=>$m['fecha']->between($desde,$hasta))->sortBy('fecha')->values();
        $saldoCorriente=$saldoInicial;
        $filas=[['fecha'=>$desde->format('d/m/Y'),'tipo'=>'SALDO INICIAL','documento'=>'—','detalle_ubicacion'=>'—','clote'=>'—','usuario'=>'—','entrada'=>null,'salida'=>null,'saldo'=>$saldoInicial]];
        foreach($delPeriodo as $m){$saldoCorriente+=$m['cantidad'];$filas[]=['fecha'=>$m['fecha']->format('d/m/Y H:i'),'tipo'=>$m['tipo'],'documento'=>$m['documento'],'detalle_ubicacion'=>$m['detalle_ubicacion'],'clote'=>$m['clote']?:'S/L','usuario'=>$m['usuario'],'entrada'=>$m['cantidad']>0?$m['cantidad']:null,'salida'=>$m['cantidad']<0?abs($m['cantidad']):null,'saldo'=>$saldoCorriente,'contable'=>$m['contable']];}
        return response()->json(['filas'=>$filas,'saldo_final'=>$saldoCorriente,'total_entradas'=>$delPeriodo->where('contable',true)->where('cantidad','>',0)->sum('cantidad'),'total_salidas'=>abs($delPeriodo->where('contable',true)->where('cantidad','<',0)->sum('cantidad'))]);
    }

    private function datosExportacion(Request $request): array
    {
        $codigo=$request->get('codigo');$clote=$request->get('clote');$galpon=$request->get('galpon');$ubicacion=$request->get('ubicacion');$pallet=$request->get('pallet');
        $almacen=$this->almacenCodigo($request);
        if(!$almacen||!$codigo||!$request->get('fecha_inicio')||!$request->get('fecha_fin')) abort(422,'Producto, almacén autorizado y periodo son obligatorios.');
        $desde=Carbon::parse($request->get('fecha_inicio'))->startOfDay();$hasta=Carbon::parse($request->get('fecha_fin'))->endOfDay();
        $movimientos=$this->obtenerMovimientos($codigo,$almacen,$clote,$galpon,$ubicacion,$pallet);
        $saldoInicial=$movimientos->filter(fn($m)=>$m['fecha']->lt($desde))->sum('cantidad');
        $delPeriodo=$movimientos->filter(fn($m)=>$m['fecha']->between($desde,$hasta))->sortBy('fecha')->values();
        $saldo=$saldoInicial;$filas=[['fecha'=>$desde->format('d/m/Y'),'tipo'=>'SALDO INICIAL','documento'=>'—','detalle_ubicacion'=>'—','clote'=>'—','usuario'=>'—','entrada'=>null,'salida'=>null,'saldo'=>$saldoInicial]];
        foreach($delPeriodo as $m){$saldo+=$m['cantidad'];$filas[]=['fecha'=>$m['fecha']->format('d/m/Y H:i'),'tipo'=>$m['tipo'],'documento'=>$m['documento'],'detalle_ubicacion'=>$m['detalle_ubicacion'],'clote'=>$m['clote']?:'S/L','usuario'=>$m['usuario'],'entrada'=>$m['cantidad']>0?$m['cantidad']:null,'salida'=>$m['cantidad']<0?abs($m['cantidad']):null,'saldo'=>$saldo];}
        return ['filas'=>$filas,'saldo_final'=>$saldo,'codigo'=>$codigo,'nombre_almacen'=>$this->almacenesAutorizados()->firstWhere('codigo',$almacen)?->nombre??$almacen,'desde'=>$desde->format('d/m/Y'),'hasta'=>$hasta->format('d/m/Y')];
    }

    public function exportarExcel(Request $request)
    {
        $data=$this->datosExportacion($request);
        return response()->view('wms.kardex.excel',$data,200,['Content-Type'=>'application/vnd.ms-excel; charset=UTF-8','Content-Disposition'=>'attachment; filename="kardex_'.$data['codigo'].'_'.now()->format('Ymd_His').'.xls"']);
    }

    public function exportarPdf(Request $request)
    {
        $data=$this->datosExportacion($request);
        $pdf=\Barryvdh\DomPDF\Facade\Pdf::loadView('wms.kardex.pdf',$data)->setPaper('a4','landscape');
        return $pdf->download('kardex_'.$data['codigo'].'_'.now()->format('Ymd_His').'.pdf');
    }

    private function obtenerMovimientos(string $codigo,string $almacen,?string $clote,?string $galpon,?string $ubicacion,?string $pallet): Collection
    {
        $movimientos=collect();
        WmsIngreso::with('creador:id,name')->where('codigo',$codigo)->where('almacen',$almacen)->when($clote,fn($q,$v)=>$q->where('clote',$v))->when($galpon,fn($q,$v)=>$q->where('galpon',$v))->when($ubicacion,fn($q,$v)=>$q->where('ubicacion',$v))->when($pallet,fn($q,$v)=>$q->where('pallet',$v))->get()->each(function(WmsIngreso $r)use(&$movimientos){$ajuste=$r->tipo_ingreso==='ajuste';$movimientos->push(['fecha'=>$r->created_at,'tipo'=>$ajuste?'INGRESO (AJUSTE)':'INGRESO','documento'=>$ajuste?"Ajuste: {$r->rdocum} · Pallet {$r->pallet}".($r->motivo?" · Motivo: {$r->motivo}":''):"Nota: {$r->rdocum} · Pallet {$r->pallet}",'detalle_ubicacion'=>"Origen: Galpón {$r->galpon} · {$r->ubicacion}",'clote'=>$r->clote,'usuario'=>$r->creador->name??'N/D','cantidad'=>(int)$r->cantidad,'contable'=>true]);});
        WmsSalida::with('creador:id,name')->where('codigo',$codigo)->where('almacen',$almacen)->when($clote,fn($q,$v)=>$q->where('clote',$v))->when($galpon,fn($q,$v)=>$q->where('galpon',$v))->when($ubicacion,fn($q,$v)=>$q->where('ubicacion',$v))->when($pallet,fn($q,$v)=>$q->where('pallet',$v))->get()->each(function(WmsSalida $r)use(&$movimientos){$destino="Nota Tipo {$r->tipo_registro} #{$r->id_registro}".($r->glosa?" · {$r->glosa}":'');$movimientos->push(['fecha'=>$r->created_at,'tipo'=>'SALIDA','documento'=>"{$destino} · Pallet {$r->pallet}",'detalle_ubicacion'=>"Origen: Galpón {$r->galpon} · {$r->ubicacion} → Destino: {$destino}",'clote'=>$r->clote,'usuario'=>$r->creador->name??'N/D','cantidad'=>-1*(int)$r->cantidad,'contable'=>true]);});
        WmsReubicacion::with('creador:id,name')->where('codigo',$codigo)->where('almacen_origen',$almacen)->when($clote,fn($q,$v)=>$q->where('clote',$v))->when($galpon,fn($q,$v)=>$q->where('galpon_origen',$v))->when($ubicacion,fn($q,$v)=>$q->where('ubicacion_origen',$v))->when($pallet,fn($q,$v)=>$q->where('pallet_origen',$v))->get()->each(function(WmsReubicacion $r)use(&$movimientos){$movimientos->push(['fecha'=>$r->created_at,'tipo'=>'REUBICACIÓN (salida)','documento'=>"Pallet {$r->pallet_origen} → Pallet {$r->pallet_destino}",'detalle_ubicacion'=>"Origen: Galpón {$r->galpon_origen} · {$r->ubicacion_origen} → Destino: Galpón {$r->galpon_destino} · {$r->ubicacion_destino}",'clote'=>$r->clote,'usuario'=>$r->creador->name??'N/D','cantidad'=>-1*(int)$r->cantidad,'contable'=>false]);});
        WmsReubicacion::with('creador:id,name')->where('codigo',$codigo)->where('almacen_destino',$almacen)->when($clote,fn($q,$v)=>$q->where('clote',$v))->when($galpon,fn($q,$v)=>$q->where('galpon_destino',$v))->when($ubicacion,fn($q,$v)=>$q->where('ubicacion_destino',$v))->when($pallet,fn($q,$v)=>$q->where('pallet_destino',$v))->get()->each(function(WmsReubicacion $r)use(&$movimientos){$movimientos->push(['fecha'=>$r->created_at,'tipo'=>'REUBICACIÓN (entrada)','documento'=>"Pallet {$r->pallet_origen} → Pallet {$r->pallet_destino}",'detalle_ubicacion'=>"Origen: Galpón {$r->galpon_origen} · {$r->ubicacion_origen} → Destino: Galpón {$r->galpon_destino} · {$r->ubicacion_destino}",'clote'=>$r->clote,'usuario'=>$r->creador->name??'N/D','cantidad'=>(int)$r->cantidad,'contable'=>false]);});
        return $movimientos->sortBy('fecha')->values();
    }
}
