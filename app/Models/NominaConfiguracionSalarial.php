<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NominaConfiguracionSalarial extends Model
{
    protected $table = 'nomina_configuraciones_salariales';

    protected $fillable = [
        'nomina_personal_id',
        'fecha_inicio',
        'fecha_fin',
        'haber_basico',
        'categoria_id',
        'modalidad_remuneracion',
        'salario_cotizable',
        'observaciones',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'haber_basico' => 'decimal:2',
        'salario_cotizable' => 'decimal:2',
    ];

    public function personal(): BelongsTo
    {
        return $this->belongsTo(NominaPersonal::class, 'nomina_personal_id');
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(NominaCategoria::class, 'categoria_id');
    }
}
