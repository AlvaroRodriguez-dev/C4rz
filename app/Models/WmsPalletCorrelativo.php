<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WmsPalletCorrelativo extends Model
{
    protected $table = 'wms_pallet_correlativos';

    protected $fillable = ['anio', 'correlativo'];
}