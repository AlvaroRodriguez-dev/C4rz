<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class IndicadoresOcsEquipo extends Model {
 protected $table='indicadores_ocs_equipos'; protected $guarded=[];
 protected function casts(): array { return ['ultimo_inventario'=>'date','fecha_compra_estimada'=>'date','ram_hasta_4gb'=>'boolean','antiguedad_mayor_5'=>'boolean']; }
}