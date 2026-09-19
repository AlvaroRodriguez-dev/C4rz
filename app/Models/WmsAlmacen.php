<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WmsAlmacen extends Model
{
    protected $table = 'wms_almacenes';

    protected $fillable = [
        'codigo',
        'nombre',
        'tipo',
        'almacen_padre_id',
        'prefijo_documento',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function padre(): BelongsTo
    {
        return $this->belongsTo(self::class, 'almacen_padre_id');
    }

    public function subalmacenes(): HasMany
    {
        return $this->hasMany(self::class, 'almacen_padre_id');
    }

    public function galpones(): HasMany
    {
        return $this->hasMany(WmsGalpon::class, 'almacen_id');
    }

    public function ubicaciones(): HasMany
    {
        return $this->hasMany(WmsUbicacion::class, 'almacen_id');
    }

    public function usuarios(): HasMany
    {
        return $this->hasMany(WmsUsuarioAlmacen::class, 'almacen_id');
    }
}
