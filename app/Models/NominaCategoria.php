<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NominaCategoria extends Model
{
    protected $table = 'nomina_categorias';

    protected $fillable = [
        'codigo',
        'nombre',
        'descripcion',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function configuracionesSalariales(): HasMany
    {
        return $this->hasMany(NominaConfiguracionSalarial::class, 'categoria_id');
    }
}
