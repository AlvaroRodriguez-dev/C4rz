<?php
namespace App\Services\Indicadores;
use Carbon\Carbon;
use Illuminate\Support\Str;
use RuntimeException;
class OcsInventoryService {
 public function process(string $path, int $year, int $month): array {
  $h=fopen($path,'r'); if(!$h) throw new RuntimeException('No se pudo leer el archivo.');
  $first=fgets($h); if($first===false) throw new RuntimeException('El archivo está vacío.');
  $delimiter=substr_count($first,';')>substr_count($first,',')?';':',';
  rewind($h); $headers=fgetcsv($h,0,$delimiter);
  $headers=array_map(fn($v)=>$this->norm((string)$v),$headers ?: []);
  $map=[]; foreach($headers as $i=>$name) $map[$name]=$i;
  $get=function(array $row,array $names) use($map) { foreach($names as $n){$k=$this->norm($n);if(isset($map[$k]))return trim((string)($row[$map[$k]]??''));} return ''; };
  $rows=[]; $tot=0; $pcs=0; $nos=0; $pending=0; $ram=0; $old=0; $missing=0;
  while(($row=fgetcsv($h,0,$delimiter))!==false) {
   if(count($row)===1 && trim((string)$row[0])==='') continue;
   $tot++;
   $os=$get($row,['Sistema Operativo','Sistema operativo']); $isAndroid=Str::contains(Str::lower($os),'android');
   $isServer=Str::contains(Str::lower($os),'server');
   $type=$isAndroid?'movil':($isServer?'servidor':'computadora');
   $ramRaw=$get($row,['RAM (MB)','RAM']); $ramMb=is_numeric(str_replace(',','.',$ramRaw))?(int)round((float)str_replace(',','.',$ramRaw)):null;
   $support=$this->supportStatus($os);
   $buy=$this->parseDate($get($row,['Fecha aproximada de compra','Fecha de compra estimada']));
   $ageMonths=$buy?max(0,Carbon::parse($buy)->startOfMonth()->diffInMonths(Carbon::create($year,$month,1)->startOfMonth(),false)):null;
   $isOld=$ageMonths!==null && $ageMonths>60;
   if($type==='computadora') { $pcs++; if($support==='sin_soporte')$nos++; if($support==='pendiente')$pending++; if($ramMb!==null && $ramMb<=4096)$ram++; if($buy===null)$missing++; if($isOld)$old++; }
   $rows[]=['tag'=>$get($row,['Información de cuenta: TAG','TAG']),'dispositivo'=>$get($row,['Dispositivo']),'id_dispositivo'=>$get($row,['ID Dispositivo']),'sistema_operativo'=>$os,'ram_mb'=>$ramMb,'tipo_cpu'=>$get($row,['Tipo de CPU']),'propietario'=>$get($row,['Propietario']),'ultimo_inventario'=>$this->parseDate($get($row,['Último inventario','Ultimo inventario'])),'fecha_compra_estimada'=>$buy,'tipo_equipo'=>$type,'estado_soporte'=>$type==='computadora'?$support:null,'antiguedad_meses'=>$ageMonths,'ram_hasta_4gb'=>$type==='computadora' && $ramMb!==null && $ramMb<=4096,'antiguedad_mayor_5'=>$type==='computadora' && $isOld];
  }
  fclose($h); if($tot===0) throw new RuntimeException('El archivo no contiene registros.');
  if(!isset($map[$this->norm('Dispositivo')]) || !isset($map[$this->norm('Sistema Operativo')])) throw new RuntimeException('El CSV no contiene las columnas Dispositivo y Sistema Operativo.');
  $pct=fn($n)=>$pcs?round($n/$pcs*100,2):0;
  return ['rows'=>$rows,'total_registros'=>$tot,'total_computadoras'=>$pcs,'so_sin_soporte'=>$nos,'so_pendiente_verificacion'=>$pending,'ram_hasta_4gb'=>$ram,'antiguedad_mayor_5'=>$old,'sin_fecha_compra'=>$missing,'porcentaje_so_sin_soporte'=>$pct($nos),'porcentaje_ram_hasta_4gb'=>$pct($ram),'porcentaje_antiguedad_mayor_5'=>$pct($old)];
 }
 private function norm(string $s): string { $s=Str::ascii(mb_strtolower(trim(preg_replace('/^\xEF\xBB\xBF/','',$s)))); return preg_replace('/\s+/',' ',$s); }
 private function parseDate(string $v): ?string { if($v==='')return null; foreach(['Y-m-d','d/m/Y','m/d/Y','d-m-Y','Y-m-d H:i:s','d/m/Y H:i:s'] as $f){try{return Carbon::createFromFormat($f,$v)->toDateString();}catch(\Throwable){}} try{return Carbon::parse($v)->toDateString();}catch(\Throwable){return null;} }
 private function supportStatus(string $os): string { $s=Str::lower($os); if($s==='')return 'pendiente'; if(preg_match('/windows xp|windows 7|windows 8(?:\s|$)|windows 8\.1|server 2008|server 2012/', $s))return 'sin_soporte'; if(Str::contains($s,'windows 10') && !Str::contains($s,'ltsc'))return 'sin_soporte'; if(Str::contains($s,'windows 10') || Str::contains($s,'windows 11') || Str::contains($s,'server'))return 'pendiente'; return 'pendiente'; }
}