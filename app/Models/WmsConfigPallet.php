<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WmsConfigPallet extends Model
{
    protected $table = 'wms_config_pallets';
    protected $primaryKey = 'codigo';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'codigo',
        'descripcion',
        'cajas_x_pallet',
    ];
}