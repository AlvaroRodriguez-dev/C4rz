<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NominaPeriodo extends Model
{
    protected $table = 'nomina_periodos';

    protected $fillable = [
        'anio',
        'mes',
        'descripcion',
        'estado',
        'version_vigente_id',
        'fecha_cierre',
        'cerrado_por',
    ];

    protected $casts = [
        'fecha_cierre' => 'datetime',
    ];

    public function importaciones(): HasMany
    {
        return $this->hasMany(NominaImportacion::class, 'periodo_id');
    }

    public function entradas(): HasMany
    {
        return $this->hasMany(NominaEntrada::class, 'periodo_id');
    }

    public function horasEspeciales(): HasMany
    {
        return $this->hasMany(NominaHoraEspecial::class, 'periodo_id');
    }

    public function bonos(): HasMany
    {
        return $this->hasMany(NominaBono::class, 'periodo_id');
    }

    public function descuentos(): HasMany
    {
        return $this->hasMany(NominaDescuento::class, 'periodo_id');
    }

    public function corridas(): HasMany
    {
        return $this->hasMany(NominaCorrida::class, 'periodo_id');
    }

    public function versiones(): HasMany
    {
        return $this->hasMany(NominaVersion::class, 'periodo_id');
    }

    public function aprobaciones(): HasMany
    {
        return $this->hasMany(NominaAprobacion::class, 'periodo_id');
    }

    public function auditorias(): HasMany
    {
        return $this->hasMany(NominaAuditoria::class, 'periodo_id');
    }
}