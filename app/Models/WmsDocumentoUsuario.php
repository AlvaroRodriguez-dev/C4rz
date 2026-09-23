<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WmsDocumentoUsuario extends Model
{
    use SoftDeletes;

    protected $table = 'wms_documento_usuario';

    protected $fillable = [
        'id_documento',
        'id_usuario',
        'created_id',
        'updated_id',
        'deleted_id',
    ];

    public function documento(): BelongsTo
    {
        return $this->belongsTo(WmsDocumento::class, 'id_documento');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_usuario');
    }
}
