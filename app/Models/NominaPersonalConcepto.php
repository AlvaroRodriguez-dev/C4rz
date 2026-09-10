<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NominaPersonalConcepto extends Model
{
    protected $table = 'nomina_personal_conceptos';

    protected $fillable = [
        'nomina_personal_id',
        'concepto_id',
        'fecha_inicio',
        'fecha_fin',
        'tipo_valor',
        'valor',
        'observaciones',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'valor' => 'decimal:6',
    ];

    public function personal(): BelongsTo
    {
        return $this->belongsTo(NominaPersonal::class, 'nomina_personal_id');
    }

    public function concepto(): BelongsTo
    {
        return $this->belongsTo(NominaConcepto::class, 'concepto_id');
    }
}
