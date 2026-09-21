<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItEvaluacionDetalle extends Model
{
    protected $table = 'it_evaluacion_detalles';

    protected $fillable = [
        'evaluacion_id',
        'solicitud_detalle_id',
        'resultado',
        'cantidad',
        'observaciones',
    ];

    protected $casts = [
        'cantidad' => 'integer',
    ];

    public function evaluacion()
    {
        return $this->belongsTo(ItEvaluacion::class, 'evaluacion_id');
    }

    public function solicitudDetalle()
    {
        return $this->belongsTo(ItSolicitudDetalle::class, 'solicitud_detalle_id');
    }
}
