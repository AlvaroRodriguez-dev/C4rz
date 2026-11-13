<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WmsUbicacion extends Model
{
    protected $table = 'wms_ubicaciones';

    protected $fillable = [
        'almacen_id',
        'galpon_id',
        'codigo',
        'tipo',
        'numero',
        'activo',
    ];

    protected $casts = [
        'numero' => 'integer',
        'activo' => 'boolean',
    ];

    public function almacen(): BelongsTo
    {
        return $this->belongsTo(WmsAlmacen::class, 'almacen_id');
    }

    public function galpon(): BelongsTo
    {
        return $this->belongsTo(WmsGalpon::class, 'galpon_id');
    }

    public function esNormal(): bool
    {
        return $this->tipo === 'NORMAL';
    }

    public function esPreparacion(): bool
    {
        return $this->tipo === 'PREPARACION';
    }

    public function esDespacho(): bool
    {
        return $this->tipo === 'DESPACHO';
    }
}
