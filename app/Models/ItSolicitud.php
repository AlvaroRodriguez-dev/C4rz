<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItSolicitud extends Model
{
    protected $table = 'it_solicitudes';

    protected $fillable = [
        'numero', 'solicitante_id', 'area_id', 'ubicacion_id', 'fecha_solicitud',
        'prioridad', 'estado', 'motivo', 'descripcion', 'responsable_id',
        'fecha_cierre', 'observaciones', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'fecha_solicitud' => 'date',
        'fecha_cierre' => 'datetime',
    ];

    public function detalles()
    {
        return $this->hasMany(ItSolicitudDetalle::class, 'solicitud_id');
    }

    public function ubicacion()
    {
        return $this->belongsTo(ItUbicacion::class, 'ubicacion_id');
    }

    public function responsable()
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            $model->created_by ??= auth()->id();
        });

        static::updating(function (self $model) {
            $model->updated_by = auth()->id();
        });
    }
}
