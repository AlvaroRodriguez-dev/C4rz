<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ItActivo extends Model
{
    use SoftDeletes;

    protected $table = 'it_activos';

    protected $fillable = [
        'codigo_ti',
        'tipo_activo_id',
        'marca_id',
        'modelo_id',
        'numero_serie',
        'numero_parte',
        'estado_id',
        'ubicacion_id',
        'custodio_id',
        'usuario_asignado_id',
        'fecha_adquisicion',
        'proveedor_id',
        'costo',
        'garantia_fecha',
        'origen_documento',
        'origen_linea',
        'fecha_alta',
        'observaciones',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'fecha_adquisicion' => 'date',
        'fecha_alta' => 'date',
        'garantia_fecha' => 'date',
        'costo' => 'decimal:2',
    ];

    public function tipoActivo()
    {
        return $this->belongsTo(ItTipoActivo::class, 'tipo_activo_id');
    }

    public function marca()
    {
        return $this->belongsTo(ItMarca::class, 'marca_id');
    }

    public function modelo()
    {
        return $this->belongsTo(ItModelo::class, 'modelo_id');
    }

    public function estado()
    {
        return $this->belongsTo(ItEstado::class, 'estado_id');
    }

    public function ubicacion()
    {
        return $this->belongsTo(ItUbicacion::class, 'ubicacion_id');
    }

    public function caracteristicas()
    {
        return $this->hasMany(ItCaracteristica::class, 'activo_id');
    }

    public function componentesInstalados()
    {
        return $this->hasMany(ItComponenteInstalado::class, 'activo_id');
    }

    public function accesorios()
    {
        return $this->hasMany(ItAccesorio::class, 'activo_id');
    }

    protected static function booted(): void
    {
        static::creating(function (self $activo) {
            $activo->created_by ??= auth()->id();
            $activo->fecha_alta ??= now()->toDateString();
        });

        static::updating(function (self $activo) {
            $activo->updated_by = auth()->id();
        });

        static::deleting(function (self $activo) {
            $activo->deleted_by = auth()->id();
            $activo->saveQuietly();
        });
    }
}
