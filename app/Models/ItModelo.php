<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ItModelo extends Model
{
    use SoftDeletes;

    protected $table = 'it_modelos';

    protected $fillable = [
        'marca_id', 'nombre', 'descripcion', 'especificaciones',
        'created_by', 'updated_by', 'deleted_by',
    ];

    protected $casts = ['especificaciones' => 'array'];

    public function marca()
    {
        return $this->belongsTo(ItMarca::class, 'marca_id');
    }
}
