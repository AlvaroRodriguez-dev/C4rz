<?php
namespace App\Http\Controllers\Wms;
use App\Http\Controllers\Controller;
use App\Models\WmsConfigPallet;
use App\Services\WmsContextService;
use App\Services\WmsLiberacionProduccionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class WmsLiberacionProduccionController extends Controller
{
 public function __construct(private WmsContextService $context,private WmsLiberacionProduccionService $service){}
 public function create(){
  $formatos=WmsConfigPallet::query()->orderBy('codigo')->get();
  $almacen=$this->context->almacen();
  return view('wms.produccion.liberacion',compact('formatos','almacen'));
 }
 public function buscarProductos(Request $request){
  $q=trim((string)$request->get('q'));$formato=strtoupper(trim((string)$request->get('formato')));
  if($formato==='') return response()->json(['results'=>[]]);
  $productos=DB::connection('sisinvconsolidado2026')->table('stock')->where('CODIGO','like','6C%')
   ->whereRaw('UPPER(SUBSTRING(CODIGO,6,4)) = ?',[$formato])
   ->when($q!=='',function($query)use($q){$query->where(function($sub)use($q){$sub->where('CODIGO','like','%'.$q.'%')->orWhere('DESCRIP','like','%'.$q.'%')->orWhere('DESCRIP1','like','%'.$q.'%');});})
   ->select('CODIGO','DESCRIP','DESCRIP1')->orderBy('CODIGO')->limit(30)->get();
  return response()->json(['results'=>$productos->map(fn($p)=>[
   'id'=>$p->CODIGO,'text'=>trim($p->CODIGO.' · '.$p->DESCRIP.' '.$p->DESCRIP1),'codigo'=>$p->CODIGO,
   'descripcion'=>trim($p->DESCRIP.' '.$p->DESCRIP1),'modelo'=>substr($p->CODIGO,-4),
   'calidad'=>match(substr($p->CODIGO,4,1)){ '1'=>'EXTRA','2'=>'COMERCIAL','3'=>'ECONOMICO',default=>null }
  ])]);
 }
 public function store(Request $request){
  $data=$request->validate([
   'planta'=>['required','string','max:100'],'fecha_entrega'=>['required','date'],'turno_hora'=>['nullable','string','max:30'],
   'formato'=>['required','string','max:20'],'folio_fisico'=>['nullable','string','max:30'],'observaciones'=>['nullable','string','max:2000'],
   'lineas'=>['required','array','min:1'],'lineas.*.codigo'=>['required','string','max:30'],'lineas.*.descripcion'=>['nullable','string','max:200'],
   'lineas.*.modelo'=>['nullable','string','max:20'],'lineas.*.calidad'=>['required','in:EXTRA,COMERCIAL,ECONOMICO'],
   'lineas.*.cantidad'=>['required','integer','min:1'],'lineas.*.tono'=>['nullable','integer','min:0','max:999'],
   'lineas.*.calibre'=>['nullable','integer','min:0','max:99']
  ]);
  try{$entrega=$this->service->crear($data);return response()->json([
   'ok'=>true,'message'=>'Liberación RG-CB-36 creada correctamente.',
   'redirect'=>route('wms.produccion.verificacion.show',$entrega),'documento'=>$entrega->documento?->id_documento,'total'=>$entrega->total_declarado
  ]);}catch(RuntimeException $e){return response()->json(['message'=>$e->getMessage()],422);}
 }
}