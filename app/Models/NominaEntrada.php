<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NominaEntrada extends Model
{
    protected $table = 'nomina_entradas';

    protected $fillable = [
        'periodo_id','importacion_id','ci','nombre_apellido',
        'horas_trabajadas','horas_nocturnas','horas_dominicales',
        'horas_feriados','horas_extra','dias_faltas','minutos_atrasados',
        'dias_vacaciones','dias_bajas_medicas','dias_salario_dominical',
        'estado','observaciones',
    ];

    protected $casts = [
        'horas_trabajadas'=>'decimal:2',
        'horas_nocturnas'=>'decimal:2',
        'horas_dominicales'=>'decimal:2',
        'horas_feriados'=>'decimal:2',
        'horas_extra'=>'decimal:2',
        'dias_faltas'=>'decimal:2',
        'dias_vacaciones'=>'decimal:2',
        'dias_bajas_medicas'=>'decimal:2',
        'dias_salario_dominical'=>'decimal:2',
    ];

    public function periodo(): BelongsTo
    {
        return $this->belongsTo(NominaPeriodo::class, 'periodo_id');
    }

    public function importacion(): BelongsTo
    {
        return $this->belongsTo(NominaImportacion::class, 'importacion_id');
    }
}