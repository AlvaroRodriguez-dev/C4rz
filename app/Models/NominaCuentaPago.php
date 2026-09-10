<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NominaCuentaPago extends Model
{
    protected $table = 'nomina_cuentas_pago';

    protected $fillable = [
        'nomina_personal_id',
        'fecha_inicio',
        'fecha_fin',
        'institucion_bancaria',
        'cuenta_bancaria',
        'tipo_pago',
        'principal',
        'observaciones',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'principal' => 'boolean',
    ];

    public function personal(): BelongsTo
    {
        return $this->belongsTo(NominaPersonal::class, 'nomina_personal_id');
    }
}
