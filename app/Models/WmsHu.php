<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WmsHu extends Model
{
    protected $table = 'wms_hu';

    protected $fillable = [
        'numero',
        'entrega_id',
        'almacen_id',
        'ubicacion_id',
        'formato',
        'capacidad_estandar',
        'cantidad_total',
        'tipo',
        'estado',
        'ubicado_at',
        'created_id',
        'update_id',
    ];

    protected $casts = [
        'ubicado_at' => 'datetime',
    ];

    public function entrega(): BelongsTo
    {
        return $this->belongsTo(WmsEntregaProduccion::class, 'entrega_id');
    }

    public function almacen(): BelongsTo
    {
        return $this->belongsTo(WmsAlmacen::class, 'almacen_id');
    }

    public function ubicacion(): BelongsTo
    {
        return $this->belongsTo(WmsUbicacion::class, 'ubicacion_id');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(WmsHuDetalle::class, 'hu_id');
    }
}
