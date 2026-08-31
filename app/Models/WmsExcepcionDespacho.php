<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class WmsExcepcionDespacho extends Model
{
    use SoftDeletes;

    protected $table = 'wms_excepciones_despacho';

    protected $fillable = [
        'tipo_registro', 'id_registro', 'codigo', 'descrip', 'descrip1',
        'lote_solicitado', 'lote_aplicado', 'cantidad', 'motivo',
    ];

    protected static function booted(): void
    {
        static::creating(fn (self $m) => $m->created_id = Auth::id());
        static::updating(fn (self $m) => $m->update_id = Auth::id());
    }

    public function creador()
    {
        return $this->belongsTo(User::class, 'created_id');
    }
}