<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WmsTipoDocumento extends Model
{
    use SoftDeletes;

    protected $table = 'wms_tipo_documentos';

    public $incrementing = false;

    protected $keyType = 'int';

    protected $fillable = [
        'id',
        'codigo',
        'descripcion',
        'talonario',
        'created_id',
        'updated_id',
        'deleted_id',
    ];

    public function documentos(): HasMany
    {
        return $this->hasMany(WmsDocumento::class, 'id_tipo_registro');
    }
}
