<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItRecepcion extends Model
{
    protected $table = 'it_recepciones';

    protected $fillable = [
        'numero', 'orden_compra_id', 'fecha', 'recibido_por', 'estado',
        'documento_suministros', 'estado_suministros', 'observaciones',
    ];

    protected $casts = ['fecha' => 'date'];

    public function detalles()
    {
        return $this->hasMany(ItRecepcionDetalle::class, 'recepcion_id');
    }
}