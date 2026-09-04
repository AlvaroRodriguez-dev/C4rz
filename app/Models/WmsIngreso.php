<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class WmsIngreso extends Model
{
    use SoftDeletes;

    protected $table = 'wms_ingresos';

    protected $fillable = [
        'rdocum',
        'rfecha',
        'tipo_ingreso',
        'motivo',
        'pallet',
        'codigo',
        'clote',
        'descrip',
        'descrip1',
        'cantidad',
        'almacen',
        'galpon',
        'ubicacion',
    ];

    protected $casts = [
        'rfecha' => 'date',
        'deleted_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            $model->created_id = Auth::id();
            $model->normalizarUbicacion();
        });

        static::updating(function (self $model) {
            $model->update_id = Auth::id();
            $model->normalizarUbicacion();
        });
    }

    private function normalizarUbicacion(): void
    {
        $this->galpon = strtoupper(trim((string) $this->galpon));
        $this->ubicacion = strtoupper(trim((string) $this->ubicacion));
    }

    /**
     * Soft delete registrando quién elimina.
     * Se sobreescribe porque SoftDeletes solo persiste deleted_at/updated_at
     * automáticamente; delete_id se guarda explícitamente antes de anular.
     */
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
