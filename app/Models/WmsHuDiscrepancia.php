<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WmsHuDiscrepancia extends Model
{
    protected $table = 'wms_hu_discrepancias';

    protected $fillable = [
        'hu_id',
        'entrega_id',
        'hu_detalle_id',
        'tipo',
        'valor_esperado',
        'valor_observado',
        'valor_anterior',
        'observacion',
        'estado',
        'reportado_id',
        'reportado_at',
        'corregido_id',
        'corregido_at',
        'valor_corregido',
    ];

    protected $casts = [
        'reportado_at' => 'datetime',
        'corregido_at' => 'datetime',
    ];

    public function hu(): BelongsTo
    {
        return $this->belongsTo(WmsHu::class, 'hu_id');
    }

    public function detalle(): BelongsTo
    {
        return $this->belongsTo(WmsHuDetalle::class, 'hu_detalle_id');
    }

    public function entrega(): BelongsTo
    {
        return $this->belongsTo(WmsEntregaProduccion::class, 'entrega_id');
    }

    public function reportadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reportado_id');
    }

    public function corregidoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'corregido_id');
    }
}
