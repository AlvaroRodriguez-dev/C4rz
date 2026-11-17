<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ItCaracteristica extends Model
{
    use SoftDeletes;

    protected $table = 'it_caracteristicas';

    protected $fillable = ['activo_id', 'nombre_caracteristica', 'valor', 'fecha_inicio', 'fecha_fin', 'created_by', 'updated_by', 'deleted_by'];

    protected $casts = ['fecha_inicio' => 'date', 'fecha_fin' => 'date'];

    public function activo()
    {
        return $this->belongsTo(ItActivo::class, 'activo_id');
    }
}