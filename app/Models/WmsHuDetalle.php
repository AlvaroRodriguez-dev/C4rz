<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WmsHuDetalle extends Model
{
    protected $table = 'wms_hu_detalle';

    protected $fillable = [
        'hu_id',
        'entrega_detalle_id',
        'codigo',
        'lote',
        'descripcion',
        'descripcion2',
        'formato',
        'calidad',
        'cantidad',
    ];

    public function hu(): BelongsTo
    {
        return $this->belongsTo(WmsHu::class, 'hu_id');
    }

    public function entregaDetalle(): BelongsTo
    {
        return $this->belongsTo(WmsEntregaDetalle::class, 'entrega_detalle_id');
    }
}
