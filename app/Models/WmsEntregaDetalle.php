<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WmsEntregaDetalle extends Model
{
    protected $table = 'wms_entregas_detalle';

    protected $fillable = [
        'entrega_id',
        'orden',
        'codigo',
        'descripcion',
        'descripcion2',
        'calidad',
        'modelo',
        'formato',
        'lote',
        'cantidad_declarada',
        'cantidad_fisica',
        'cantidad_paletizada',
        'tono',
        'calibre',
        'estado',
        'observacion',
    ];

    public function entrega(): BelongsTo
    {
        return $this->belongsTo(WmsEntregaProduccion::class, 'entrega_id');
    }

    public function huDetalles(): HasMany
    {
        return $this->hasMany(WmsHuDetalle::class, 'entrega_detalle_id');
    }
}
