<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WmsPalletCorrelativo extends Model
{
    protected $table = 'wms_pallet_correlativos';

    protected $fillable = [
        'almacen_id',
        'anio',
        'correlativo',
    ];

    public function almacen(): BelongsTo
    {
        return $this->belongsTo(WmsAlmacen::class, 'almacen_id');
    }
}
