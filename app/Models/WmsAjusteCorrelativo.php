<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WmsAjusteCorrelativo extends Model
{
    protected $table = 'wms_ajuste_correlativos';
    protected $fillable = ['anio', 'correlativo'];
}