<?php

namespace App\Services;

use App\Models\User;
use App\Models\WmsAlmacen;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

class WmsContextService
{
    public function usuario(?User $usuario = null): User
    {
        $usuario ??= Auth::user();

        if (!$usuario) {
            throw new RuntimeException('No existe un usuario autenticado para operar en WMS.');
        }

        return $usuario;
    }

    public function almacenes(?User $usuario = null)
    {
        return $this->usuario($usuario)
            ->belongsToMany(
                WmsAlmacen::class,
                'wms_usuario_almacenes',
                'user_id',
                'almacen_id'
            )
            ->wherePivot('activo', true)
            ->where('wms_almacenes.activo', true)
            ->orderBy('wms_almacenes.codigo')
            ->get();
    }

    /**
     * Devuelve exclusivamente el almacén activo marcado como principal.
     * No aplica selección alternativa: las operaciones deben detenerse si falta esta asignación.
     */
    public function almacenPrincipal(?User $usuario = null): ?WmsAlmacen
    {
        $usuario = $this->usuario($usuario);

        return $usuario->belongsToMany(
            WmsAlmacen::class,
            'wms_usuario_almacenes',
            'user_id',
            'almacen_id'
        )
            ->wherePivot('activo', true)
            ->wherePivot('es_principal', true)
            ->where('wms_almacenes.activo', true)
            ->first();
    }

    public function almacen(?User $usuario = null): WmsAlmacen
    {
        $usuario = $this->usuario($usuario);

        $almacen = $usuario->belongsToMany(
            WmsAlmacen::class,
            'wms_usuario_almacenes',
            'user_id',
            'almacen_id'
        )
            ->wherePivot('activo', true)
            ->where('wms_almacenes.activo', true)
            ->orderByDesc('wms_usuario_almacenes.es_principal')
            ->orderBy('wms_almacenes.codigo')
            ->first();

        if (!$almacen) {
            throw new RuntimeException('El usuario no tiene un almacén WMS activo asignado.');
        }

        return $almacen;
    }

    public function codigo(?User $usuario = null): string
    {
        return (string) $this->almacen($usuario)->codigo;
    }

    public function galpones(?User $usuario = null)
    {
        return $this->almacen($usuario)
            ->galpones()
            ->where('activo', true)
            ->orderBy('codigo')
            ->get();
    }

    public function ubicaciones(?User $usuario = null)
    {
        return $this->almacen($usuario)
            ->ubicaciones()
            ->where('activo', true)
            ->orderBy('codigo')
            ->get();
    }
}
