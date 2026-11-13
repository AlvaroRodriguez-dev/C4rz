<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WmsGalponRango extends Model
{
    protected $table = 'wms_galpon_rangos';

    protected $fillable = [
        'galpon_id',
        'desde',
        'hasta',
        'activo',
    ];

    protected $casts = [
        'desde' => 'integer',
        'hasta' => 'integer',
        'activo' => 'boolean',
    ];

    public function galpon(): BelongsTo
    {
        return $this->belongsTo(WmsGalpon::class, 'galpon_id');
    }

    public function cantidad(): int
    {
        return max(0, $this->hasta - $this->desde + 1);
    }
}
