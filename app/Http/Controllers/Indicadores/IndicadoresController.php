<?php
namespace App\Http\Controllers\Indicadores;
use App\Http\Controllers\Controller;
use App\Models\IndicadoresOcsImportacion;
use App\Services\Indicadores\OcsInventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
class IndicadoresController extends Controller {
 public function index() {
  $importaciones=IndicadoresOcsImportacion::orderByDesc('periodo_anio')->orderByDesc('periodo_mes')->paginate(12);
  $actual=IndicadoresOcsImportacion::orderByDesc('periodo_anio')->orderByDesc('periodo_mes')->first();
  return view('indicadores.index',compact('importaciones','actual'));
 }
 public function create() { return view('indicadores.create'); }
 public function store(Request $request,OcsInventoryService $service) {
  $data=$request->validate(['archivo'=>['required','file','extensions:csv,txt','max:20480'],'mes'=>['required','integer','between:1,12'],'anio'=>['required','integer','between:2020,2100']]);
  $file=$request->file('archivo'); $hash=hash_file('sha256',$file->getRealPath());
  if (IndicadoresOcsImportacion::where('archivo_hash', $hash)
            ->where(function ($query) use ($data) {
                $query->where('periodo_anio', '!=', $data['anio'])
                    ->orWhere('periodo_mes', '!=', $data['mes']);
            })->exists()) {
            throw ValidationException::withMessages(['archivo' => 'Este archivo ya fue importado para otro período.']);
        }
  $result=$service->process($file->getRealPath(),(int)$data['anio'],(int)$data['mes']);
  if($result['total_computadoras']===0) throw ValidationException::withMessages(['archivo'=>'No se encontraron equipos Windows de escritorio o portátiles en el archivo.']);
  $stored=$file->store('indicadores/ocs','local');
  $sem=fn($v,$yellow,$red)=>$v<=$yellow?'verde':($v<=$red?'amarillo':'rojo');
  try {
   DB::transaction(function() use($data,$result,$hash,$file,$stored,$sem,$request) {
    $old=IndicadoresOcsImportacion::where('periodo_anio',$data['anio'])->where('periodo_mes',$data['mes'])->first();
    if($old){$old->delete();}
    $import=IndicadoresOcsImportacion::create([
     'periodo_anio'=>$data['anio'],'periodo_mes'=>$data['mes'],'archivo_original'=>$file->getClientOriginalName(),'archivo_ruta'=>$stored,'archivo_hash'=>$hash,
     'total_registros'=>$result['total_registros'],'total_computadoras'=>$result['total_computadoras'],
     'so_sin_soporte'=>$result['so_sin_soporte'],'so_pendiente_verificacion'=>$result['so_pendiente_verificacion'],
     'ram_hasta_4gb'=>$result['ram_hasta_4gb'],'antiguedad_mayor_5'=>$result['antiguedad_mayor_5'],'sin_fecha_compra'=>$result['sin_fecha_compra'],
     'porcentaje_so_sin_soporte'=>$result['porcentaje_so_sin_soporte'],'porcentaje_ram_hasta_4gb'=>$result['porcentaje_ram_hasta_4gb'],'porcentaje_antiguedad_mayor_5'=>$result['porcentaje_antiguedad_mayor_5'],
     'semaforo_so_sin_soporte'=>$sem($result['porcentaje_so_sin_soporte'],10,15),'semaforo_ram_hasta_4gb'=>$sem($result['porcentaje_ram_hasta_4gb'],15,20),'semaforo_antiguedad_mayor_5'=>$sem($result['porcentaje_antiguedad_mayor_5'],15,30),'user_id'=>$request->user()->id
    ]);
    foreach(array_chunk($result['rows'],500) as $chunk) $import->equipos()->createMany($chunk);
   });
  } catch(\Throwable $e) { Storage::disk('local')->delete($stored); throw $e; }
  return redirect()->route('indicadores.index')->with('success','Inventario procesado y los tres indicadores fueron calculados.');
 }
 public function show(IndicadoresOcsImportacion $importacion) {
  $equipos=$importacion->equipos()->orderBy('tipo_equipo')->orderBy('dispositivo')->paginate(50);
  return view('indicadores.show',compact('importacion','equipos'));
 }
}