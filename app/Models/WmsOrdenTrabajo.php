<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class WmsOrdenTrabajo extends Model
{
    use SoftDeletes;

    protected $table = 'wms_ordenes_trabajo';

    protected $fillable = ['tipo_registro', 'id_registro', 'glosa', 'estado', 'completada_at'];

    protected $casts = ['completada_at' => 'datetime'];

    protected static function booted(): void
    {
        static::creating(fn (self $m) => $m->created_id = Auth::id());
        static::updating(fn (self $m) => $m->update_id = Auth::id());
    }

    public function delete(): ?bool
    {
        $this->delete_id = Auth::id();
        $this->saveQuietly();
        return parent::delete();
    }

    public function detalles()
    {
        return $this->hasMany(WmsOrdenTrabajoDetalle::class, 'orden_trabajo_id');
    }

    public function creador()
    {
        return $this->belongsTo(User::class, 'created_id');
    }
}