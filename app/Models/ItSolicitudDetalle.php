<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItSolicitudDetalle extends Model
{
    protected $table = 'it_solicitud_detalles';

    protected $fillable = [
        'solicitud_id', 'tipo_item', 'tipo_activo_id', 'descripcion_solicitada',
        'cantidad', 'especificaciones', 'unidad', 'estado', 'cantidad_aprobada',
        'cantidad_comprada', 'cantidad_recibida', 'cantidad_identificada', 'observaciones',
    ];

    protected $casts = [
        'cantidad' => 'integer',
        'cantidad_aprobada' => 'integer',
        'cantidad_comprada' => 'integer',
        'cantidad_recibida' => 'integer',
        'cantidad_identificada' => 'integer',
    ];

    public function solicitud()
    {
        return $this->belongsTo(ItSolicitud::class, 'solicitud_id');
    }

    public function tipoActivo()
    {
        return $this->belongsTo(ItTipoActivo::class, 'tipo_activo_id');
    }
}
