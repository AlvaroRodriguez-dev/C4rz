<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ItAccesorio extends Model
{
    use SoftDeletes;

    protected $table = 'it_accesorios';

    protected $fillable = ['activo_id', 'nombre_accesorio', 'cantidad', 'valor_unitario', 'fecha_inicio', 'fecha_fin', 'created_by', 'updated_by', 'deleted_by'];

    protected $casts = ['fecha_inicio' => 'date', 'fecha_fin' => 'date', 'valor_unitario' => 'decimal:2'];
}