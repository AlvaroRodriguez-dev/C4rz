<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ItComponente extends Model
{
    use SoftDeletes;

    protected $table = 'it_componentes';

    protected $fillable = ['codigo', 'tipo_componente', 'marca_id', 'modelo', 'capacidad', 'created_by', 'updated_by', 'deleted_by'];

    public function marca()
    {
        return $this->belongsTo(ItMarca::class, 'marca_id');
    }
}