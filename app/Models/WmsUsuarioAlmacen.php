<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WmsUsuarioAlmacen extends Model
{
    protected $table = 'wms_usuario_almacenes';

    protected $fillable = [
        'user_id',
        'almacen_id',
        'activo',
        'es_principal',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'es_principal' => 'boolean',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function almacen(): BelongsTo
    {
        return $this->belongsTo(WmsAlmacen::class, 'almacen_id');
    }
}
