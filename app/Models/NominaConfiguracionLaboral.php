<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NominaConfiguracionLaboral extends Model
{
    protected $table = 'rh_configuraciones_laborales';

    protected $fillable = [
        'rh_personal_id',
        'fecha_inicio',
        'fecha_fin',
        'area',
        'regional',
        'centro_costo',
        'tipo_contrato',
        'clasificacion_laboral',
        'codigo_simec',
        'codigo_seguro_social',
        'fecha_ingreso',
        'fecha_retiro',
        'es_fiscal',
        'es_interna',
        'observaciones',
        'area_id_externo',
        'area_codigo',
        'area_nombre',
        'seccion_id_externo',
        'seccion_nombre',
        'cargo_id_externo',
        'cargo_nombre',
        'jerarquia_id_externo',
        'jerarquia_nombre',
        'agencia_codigo',
        'agencia_nombre',
        'ciudad',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'fecha_ingreso' => 'date',
        'fecha_retiro' => 'date',
        'es_fiscal' => 'boolean',
        'es_interna' => 'boolean',
        'area_id_externo' => 'integer',
        'seccion_id_externo' => 'integer',
        'cargo_id_externo' => 'integer',
        'jerarquia_id_externo' => 'integer',
    ];

    public function personal(): BelongsTo
    {
        return $this->belongsTo(NominaPersonal::class, 'rh_personal_id');
    }
}
