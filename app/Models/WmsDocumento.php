<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class WmsDocumento extends Model
{
    use SoftDeletes;

    protected $table = 'wms_documentos';

    protected $fillable = [
        'id_tipo_registro',
        'almacen_id',
        'id_documento',
        'agencia',
        'agecodigo',
        'puntoVenta',
        'recibo',
        'tipo_precio',
        'titulo',
        'inicial',
        'database',
        'codigoSucursal',
        'codigoPuntoVenta',
        'created_id',
        'updated_id',
        'deleted_id',
        'tipo_documento',
    ];

    public function tipo(): BelongsTo
    {
        return $this->belongsTo(WmsTipoDocumento::class, 'id_tipo_registro');
    }

    public function almacen(): BelongsTo
    {
        return $this->belongsTo(WmsAlmacen::class, 'almacen_id');
    }

    public function usuarios(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'wms_documento_usuario',
            'id_documento',
            'id_usuario'
        )->withTimestamps();
    }

    public function entregaProduccion(): HasOne
    {
        return $this->hasOne(WmsEntregaProduccion::class, 'documento_id');
    }
}
