<?php

namespace App\Services;

use App\Models\WmsGalpon;
use App\Models\WmsUbicacion;
use Illuminate\Validation\ValidationException;

class WmsUbicacionService
{
    public function galpones(?WmsContextService $context = null)
    {
        $context ??= app(WmsContextService::class);

        return WmsGalpon::query()
            ->where('almacen_id', $context->almacen()->id)
            ->where('activo', true)
            ->orderBy('codigo')
            ->get();
    }

    public function ubicacionesPorGalpon(WmsGalpon $galpon, bool $soloActivas = true)
    {
        $query = WmsUbicacion::query()
            ->where('almacen_id', $galpon->almacen_id)
            ->where('galpon_id', $galpon->id)
            ->where('tipo', 'NORMAL')
            ->orderBy('numero');

        if ($soloActivas) {
            $query->where('activo', true);
        }

        return $query->get();
    }

    public function validarNormal(?int $almacenId, string $galponCodigo, string $ubicacionCodigo): WmsUbicacion
    {
        $galponCodigo = strtoupper(trim($galponCodigo));
        $ubicacionCodigo = trim($ubicacionCodigo);

        $galpon = WmsGalpon::query()
            ->where('almacen_id', $almacenId)
            ->whereRaw('UPPER(codigo) = ?', [$galponCodigo])
            ->where('activo', true)
            ->first();

        if (!$galpon) {
            throw ValidationException::withMessages([
                'ubicacion' => "El galpón {$galponCodigo} no pertenece al almacén operativo o está inactivo.",
            ]);
        }

        $ubicacion = WmsUbicacion::query()
            ->where('almacen_id', $almacenId)
            ->where('galpon_id', $galpon->id)
            ->where('tipo', 'NORMAL')
            ->where('codigo', $ubicacionCodigo)
            ->where('activo', true)
            ->first();

        if (!$ubicacion) {
            throw ValidationException::withMessages([
                'ubicacion' => "La ubicación {$ubicacionCodigo} no es una posición NORMAL activa del galpón {$galponCodigo}.",
            ]);
        }

        return $ubicacion;
    }
}
