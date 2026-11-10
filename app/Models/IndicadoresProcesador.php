<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IndicadoresProcesador extends Model
{
    protected $table = 'indicadores_procesadores';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'fecha_lanzamiento' => 'date',
            'verificado_en' => 'date',
            'activo' => 'boolean',
        ];
    }
}
