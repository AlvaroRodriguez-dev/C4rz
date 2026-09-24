<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WmsEntregaProduccion extends Model
{
    protected $table = 'wms_entregas_produccion';

    protected $fillable = [
        'documento_id',
        'folio_fisico',
        'almacen_id',
        'planta',
        'formato',
        'fecha_entrega',
        'fecha_recepcion',
        'verificado_at',
        'verificado_id',
        'origen',
        'turno_hora',
        'total_declarado',
        'total_fisico',
        'estado',
        'rdocum_sas',
        'posteado_erp_at',
        'observaciones',
        'error_integracion',
        'created_id',
        'update_id',
    ];

    protected $casts = [
        'fecha_entrega' => 'date',
        'fecha_recepcion' => 'date',
        'posteado_erp_at' => 'datetime',
        'verificado_at' => 'datetime',
    ];

    public function documento(): BelongsTo
    {
        return $this->belongsTo(WmsDocumento::class, 'documento_id');
    }

    public function verificadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verificado_id');
    }

    public function almacen(): BelongsTo
    {
        return $this->belongsTo(WmsAlmacen::class, 'almacen_id');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(WmsEntregaDetalle::class, 'entrega_id');
    }

    public function hu(): HasMany
    {
        return $this->hasMany(WmsHu::class, 'entrega_id');
    }
}
