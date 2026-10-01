<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WmsHuVerificacion extends Model
{
    protected $table = 'wms_hu_verificaciones';

    protected $fillable = [
        'hu_id',
        'entrega_id',
        'cantidad_esperada',
        'cantidad_verificada',
        'diferencia',
        'resultado',
        'observacion',
        'verificado_id',
        'verificado_at',
        'created_id',
        'update_id',
    ];

    protected $casts = [
        'verificado_at' => 'datetime',
    ];

    public function hu(): BelongsTo
    {
        return $this->belongsTo(WmsHu::class, 'hu_id');
    }

    public function entrega(): BelongsTo
    {
        return $this->belongsTo(WmsEntregaProduccion::class, 'entrega_id');
    }

    public function verificadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verificado_id');
    }
}
