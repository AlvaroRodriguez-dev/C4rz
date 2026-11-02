<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WmsRecepcionExcepcion extends Model
{
    protected $table = 'wms_recepcion_excepciones';

    protected $fillable = [
        'wms_ingreso_id',
        'id_detalle_origen',
        'tipo',
        'lote_origen',
        'lote_fisico',
        'cantidad_origen',
        'cantidad_fisica',
        'observacion',
        'autorizado_por',
        'autorizado_at',
    ];

    protected $casts = [
        'wms_ingreso_id' => 'integer',
        'id_detalle_origen' => 'integer',
        'cantidad_origen' => 'integer',
        'cantidad_fisica' => 'integer',
        'autorizado_por' => 'integer',
        'autorizado_at' => 'datetime',
    ];

    public function ingreso(): BelongsTo
    {
        return $this->belongsTo(WmsIngreso::class, 'wms_ingreso_id');
    }

    public function autorizador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'autorizado_por');
    }
}
