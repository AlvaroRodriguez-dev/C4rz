<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
class IndicadoresOcsImportacion extends Model {
 protected $table='indicadores_ocs_importaciones';
 protected $guarded=[];
 protected function casts(): array { return ['periodo_mes'=>'integer','periodo_anio'=>'integer']; }
 public function equipos(): HasMany { return $this->hasMany(IndicadoresOcsEquipo::class,'importacion_id'); }
 public function getPeriodoLabelAttribute(): string { return str_pad((string)$this->periodo_mes,2,'0',STR_PAD_LEFT).'/'.$this->periodo_anio; }
}