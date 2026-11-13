<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ItTipoActivo extends Model
{
    use SoftDeletes;

    protected $table = 'it_tipos_activo';

    protected $fillable = ['codigo', 'descripcion', 'requiere_serial'];

    protected $casts = ['requiere_serial' => 'boolean'];
}
