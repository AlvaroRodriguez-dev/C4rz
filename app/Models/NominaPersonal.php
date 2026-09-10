<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NominaPersonal extends Model
{
    protected $table = 'nomina_personal';

    protected $fillable = [
        'license',
        'estado',
        'observaciones',
    ];

    public function configuracionesLaborales(): HasMany
    {
        return $this->hasMany(NominaConfiguracionLaboral::class, 'nomina_personal_id');
    }

    public function configuracionesSalariales(): HasMany
    {
        return $this->hasMany(NominaConfiguracionSalarial::class, 'nomina_personal_id');
    }

    public function conceptos(): HasMany
    {
        return $this->hasMany(NominaPersonalConcepto::class, 'nomina_personal_id');
    }

    public function cuentasPago(): HasMany
    {
        return $this->hasMany(NominaCuentaPago::class, 'nomina_personal_id');
    }
}
