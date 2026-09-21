<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItEvaluacion extends Model
{
    protected $table = 'it_evaluaciones';

    protected $fillable = [
        'solicitud_id',
        'evaluador_id',
        'fecha_evaluacion',
        'resultado',
        'justificacion',
        'observaciones',
    ];

    protected $casts = [
        'fecha_evaluacion' => 'datetime',
    ];

    public function solicitud()
    {
        return $this->belongsTo(ItSolicitud::class, 'solicitud_id');
    }

    public function evaluador()
    {
        return $this->belongsTo(User::class, 'evaluador_id');
    }

    public function detalles()
    {
        return $this->hasMany(ItEvaluacionDetalle::class, 'evaluacion_id');
    }
}
