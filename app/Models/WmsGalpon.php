<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WmsGalpon extends Model
{
    protected $table = 'wms_galpones';

    protected $fillable = [
        'almacen_id',
        'codigo',
        'nombre',
        'desde_ubicacion',
        'hasta_ubicacion',
        'activo',
    ];

    protected $casts = [
        'desde_ubicacion' => 'integer',
        'hasta_ubicacion' => 'integer',
        'activo' => 'boolean',
    ];

    public function almacen(): BelongsTo
    {
        return $this->belongsTo(WmsAlmacen::class, 'almacen_id');
    }

    public function rangos(): HasMany
    {
        return $this->hasMany(WmsGalponRango::class, 'galpon_id');
    }

    public function ubicaciones(): HasMany
    {
        return $this->hasMany(WmsUbicacion::class, 'galpon_id');
    }
}
