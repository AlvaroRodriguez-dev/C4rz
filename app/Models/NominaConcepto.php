<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NominaConcepto extends Model
{
    protected $table = 'nomina_conceptos';

    protected $fillable = [
        'codigo',
        'nombre',
        'tipo',
        'origen',
        'activo',
        'descripcion',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function personalConceptos(): HasMany
    {
        return $this->hasMany(NominaPersonalConcepto::class, 'concepto_id');
    }

    public function bonos(): HasMany
    {
        return $this->hasMany(NominaBono::class, 'concepto_id');
    }

    public function descuentos(): HasMany
    {
        return $this->hasMany(NominaDescuento::class, 'concepto_id');
    }

    public function resultadoDetalles(): HasMany
    {
        return $this->hasMany(NominaResultadoDetalle::class, 'concepto_id');
    }
}
