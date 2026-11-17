<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ItMarca extends Model
{
    use SoftDeletes;

    protected $table = 'it_marcas';

    protected $fillable = ['nombre', 'created_by', 'updated_by', 'deleted_by'];

    public function modelos()
    {
        return $this->hasMany(ItModelo::class, 'marca_id');
    }
}
