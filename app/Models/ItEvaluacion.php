<?php

namespace App\\Models;

use Illuminate\\Database\\Eloquent\\Model;

class ItEvaluacion extends Model
{
    protected $table = 'it_evaluaciones';

    protected $fillable = [
        'solicitud_id', 'evaluador_id', 'fecha_evaluacion', 'resultado',
        'justificacion', 'observaciones', 'revision_de_id',
    ];

    protected $casts = ['fecha_evaluacion' => 'datetime'];

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

    public function revisionDe()
    {
        return $this->belongsTo(self::class, 'revision_de_id');
    }

    public function revisadaPor()
    {
        return $this->hasOne(self::class, 'revision_de_id');
    }
}
