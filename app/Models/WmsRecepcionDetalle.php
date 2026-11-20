<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WmsRecepcionDetalle extends Model
{
    protected $table = 'wms_recepcion_detalles';

    protected $fillable = [
        'wms_ingreso_id',
        'id_detalle_origen',
        'cantidad_asignada',
    ];

    protected $casts = [
        'wms_ingreso_id' => 'integer',
        'id_detalle_origen' => 'integer',
        'cantidad_asignada' => 'integer',
    ];

    public function ingreso(): BelongsTo
    {
        return $this->belongsTo(WmsIngreso::class, 'wms_ingreso_id');
    }
}
