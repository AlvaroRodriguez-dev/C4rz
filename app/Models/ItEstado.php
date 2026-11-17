<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ItEstado extends Model
{
    use SoftDeletes;

    protected $table = 'it_estados';

    protected $fillable = [
        'codigo', 'descripcion', 'permite_asignacion', 'permite_traslado',
        'created_by', 'updated_by', 'deleted_by',
    ];

    protected $casts = [
        'permite_asignacion' => 'boolean',
        'permite_traslado' => 'boolean',
    ];
}
