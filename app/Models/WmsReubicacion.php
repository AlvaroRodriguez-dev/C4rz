<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class WmsReubicacion extends Model
{
    use SoftDeletes;

    protected $table = 'wms_reubicaciones';

    protected $fillable = [
        'tipo', 'codigo', 'clote', 'descrip', 'descrip1', 'cantidad',
        'pallet_origen', 'almacen_origen', 'galpon_origen', 'ubicacion_origen',
        'pallet_destino', 'almacen_destino', 'galpon_destino', 'ubicacion_destino',
        'observacion',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $m) {
            $m->created_id = Auth::id();
            $m->normalizarUbicaciones();
        });

        static::updating(function (self $m) {
            $m->update_id = Auth::id();
            $m->normalizarUbicaciones();
        });
    }

    private function normalizarUbicaciones(): void
    {
        $this->galpon_origen = strtoupper(trim((string) $this->galpon_origen));
        $this->ubicacion_origen = strtoupper(trim((string) $this->ubicacion_origen));
        $this->galpon_destino = strtoupper(trim((string) $this->galpon_destino));
        $this->ubicacion_destino = strtoupper(trim((string) $this->ubicacion_destino));
    }

    public function delete(): ?bool
    {
        $this->delete_id = Auth::id();
        $this->saveQuietly();

        return parent::delete();
    }

    public function creador()
    {
        return $this->belongsTo(User::class, 'created_id');
    }
}
