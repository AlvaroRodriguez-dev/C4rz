<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NominaConfiguracionLaboral extends Model
{
    protected $table = 'nomina_configuraciones_laborales';

    protected $fillable = [
        'nomina_personal_id',
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
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'fecha_ingreso' => 'date',
        'fecha_retiro' => 'date',
        'es_fiscal' => 'boolean',
        'es_interna' => 'boolean',
    ];

    public function personal(): BelongsTo
    {
        return $this->belongsTo(NominaPersonal::class, 'nomina_personal_id');
    }
}
