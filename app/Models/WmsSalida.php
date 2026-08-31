<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class WmsSalida extends Model
{
    use SoftDeletes;

    protected $table = 'wms_salidas';

    // WmsSalida
    protected $fillable = [
        'tipo_registro',
        'id_registro',
        'glosa',
        'pallet',
        'codigo',
        'clote',
        'lote_declarado',
        'es_excepcion_lote',   // <-- nuevo 26/07/2026
        'descrip',
        'descrip1',
        'cantidad',
        'almacen',
        'galpon',
        'ubicacion',
    ];

    protected $casts = [
        'deleted_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            $model->created_id = Auth::id();
        });

        static::updating(function (self $model) {
            $model->update_id = Auth::id();
        });
    }

    public function delete(): ?bool
    {
        $this->delete_id = Auth::id();
        $this->saveQuietly();

        return parent::delete();
    }

    public function creador()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_id');
    }

    public function actualizador()
    {
        return $this->belongsTo(\App\Models\User::class, 'update_id');
    }

    public function eliminador()
    {
        return $this->belongsTo(\App\Models\User::class, 'delete_id');
    }
}
