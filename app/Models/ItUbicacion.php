<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ItUbicacion extends Model
{
    use SoftDeletes;

    protected $table = 'it_ubicaciones';

    protected $fillable = ['codigo', 'descripcion', 'ubicacion_padre_id', 'nivel_jerarquia'];

    public function padre()
    {
        return $this->belongsTo(self::class, 'ubicacion_padre_id');
    }

    public function hijos()
    {
        return $this->hasMany(self::class, 'ubicacion_padre_id');
    }
}
