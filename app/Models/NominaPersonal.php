<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NominaPersonal extends Model
{
    protected $table = 'rh_personal';

    protected $fillable = [
        'license',
        'estado',
        'observaciones',
    ];

    public function configuracionesLaborales(): HasMany
    {
        return $this->hasMany(NominaConfiguracionLaboral::class, 'rh_personal_id');
    }

    public function configuracionesSalariales(): HasMany
    {
        return $this->hasMany(NominaConfiguracionSalarial::class, 'rh_personal_id');
    }

    public function conceptos(): HasMany
    {
        return $this->hasMany(NominaPersonalConcepto::class, 'rh_personal_id');
    }

    public function cuentasPago(): HasMany
    {
        return $this->hasMany(NominaCuentaPago::class, 'rh_personal_id');
    }
}
