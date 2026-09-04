<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WmsOrdenTrabajoDetalle extends Model
{
    protected $table = 'wms_orden_trabajo_detalle';

    protected $fillable = [
        'orden_trabajo_id',
        'pallet',
        'codigo',
        'clote',
        'lote_declarado',
        'es_excepcion_lote',
        'descrip',
        'descrip1',
        'cantidad',
        'almacen_origen',
        'galpon_origen',
        'ubicacion_origen',
        'chequeado',
        'chequeado_por',
        'chequeado_at',
    ];

    protected $casts = ['chequeado' => 'boolean', 'chequeado_at' => 'datetime', 'es_excepcion_lote' => 'boolean'];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            $model->normalizarUbicacion();
        });

        static::updating(function (self $model) {
            $model->normalizarUbicacion();
        });
    }

    private function normalizarUbicacion(): void
    {
        $this->galpon_origen = strtoupper(trim((string) $this->galpon_origen));
        $this->ubicacion_origen = strtoupper(trim((string) $this->ubicacion_origen));
    }

    public function ordenTrabajo()
    {
        return $this->belongsTo(WmsOrdenTrabajo::class, 'orden_trabajo_id');
    }

    public function chequeadoPor()
    {
        return $this->belongsTo(User::class, 'chequeado_por');
    }
}
