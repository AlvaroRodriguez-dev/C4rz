<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WmsRecepcionBloqueo extends Model
{
    protected $table = 'wms_recepcion_bloqueos';

    protected $fillable = [
        'id_recepcion',
    ];

    
}
