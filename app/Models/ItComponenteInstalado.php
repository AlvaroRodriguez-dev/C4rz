<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ItComponenteInstalado extends Model
{
    use SoftDeletes;

    protected $table = 'it_componentes_instalados';

    protected $fillable = ['activo_id', 'componente_id', 'numero_serie_componente', 'fecha_instalacion', 'fecha_retiro', 'tipo_operacion', 'responsable_id', 'costo', 'created_by', 'updated_by', 'deleted_by'];

    protected $casts = ['fecha_instalacion' => 'date', 'fecha_retiro' => 'date', 'costo' => 'decimal:2'];

    public function componente()
    {
        return $this->belongsTo(ItComponente::class, 'componente_id');
    }
}