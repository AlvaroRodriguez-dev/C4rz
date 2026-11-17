<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItRecepcionDetalle extends Model
{
    protected $table = 'it_recepcion_detalles';

    protected $fillable = [
        'recepcion_id', 'orden_compra_detalle_id', 'codigo_suministros', 'descripcion',
        'cantidad_recibida', 'cantidad_identificada', 'cantidad_pendiente', 'costo_unitario',
        'suministros_rdocum', 'suministros_orden', 'observaciones',
    ];

    protected $casts = [
        'cantidad_recibida' => 'integer',
        'cantidad_identificada' => 'integer',
        'cantidad_pendiente' => 'integer',
        'costo_unitario' => 'decimal:2',
    ];

    public function recepcion()
    {
        return $this->belongsTo(ItRecepcion::class, 'recepcion_id');
    }
}