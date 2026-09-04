<?php

namespace App\Services;

use App\Models\WmsIngreso;
use App\Models\WmsOrdenTrabajoDetalle;
use App\Models\WmsSalida;
use App\Models\WmsReubicacion;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SaldoService
{
    /**
     * Saldo neto = ingresos - salidas - reubicaciones(origen) + reubicaciones(destino)
     * agrupado por pallet+codigo+clote+almacen+galpon+ubicacion.
     *
     * Galpón y ubicación se normalizan a MAYÚSCULAS al construir la clave y
     * al devolver el saldo, evitando que "Playa" y "PLAYA" se consideren
     * ubicaciones físicas diferentes.
     *
     * Filtros opcionales: codigo, pallet, galpon, ubicacion.
     */
    public function calcular(array $filtros = []): Collection
    {
        $saldos = collect();

        $normalizarUbicacion = fn($valor) => strtoupper(trim((string) $valor));

        $keyOf = fn($p, $c, $l, $a, $g, $u) =>
            "{$p}|{$c}|{$l}|{$a}|{$normalizarUbicacion($g)}|{$normalizarUbicacion($u)}";

        $agregar = function ($r, $pallet, $galpon, $ubicacion, $almacen, int $signo) use (&$saldos, $keyOf, $normalizarUbicacion) {
            $galponNormalizado = $normalizarUbicacion($galpon);
            $ubicacionNormalizada = $normalizarUbicacion($ubicacion);

            $key = $keyOf($pallet, $r->codigo, $r->clote, $almacen, $galponNormalizado, $ubicacionNormalizada);

            $item = $saldos->get($key, [
                'pallet' => $pallet,
                'codigo' => $r->codigo,
                'clote' => $r->clote,
                'almacen' => $almacen,
                'galpon' => $galponNormalizado,
                'ubicacion' => $ubicacionNormalizada,
                'descrip' => $r->descrip,
                'descrip1' => $r->descrip1,
                'saldo' => 0,
            ]);

            $item['saldo'] += $signo * (int) $r->total;

            $saldos->put($key, $item);
        };

        $this->queryBase(WmsIngreso::query(), $filtros)
            ->select('pallet', 'codigo', 'clote', 'almacen', 'galpon', 'ubicacion', 'descrip', 'descrip1', DB::raw('SUM(cantidad) as total'))
            ->groupBy('pallet', 'codigo', 'clote', 'almacen', 'galpon', 'ubicacion', 'descrip', 'descrip1')
            ->get()->each(fn($r) => $agregar($r, $r->pallet, $r->galpon, $r->ubicacion, $r->almacen, 1));

        $this->queryBase(WmsSalida::query(), $filtros)
            ->select('pallet', 'codigo', 'clote', 'almacen', 'galpon', 'ubicacion', 'descrip', 'descrip1', DB::raw('SUM(cantidad) as total'))
            ->groupBy('pallet', 'codigo', 'clote', 'almacen', 'galpon', 'ubicacion', 'descrip', 'descrip1')
            ->get()->each(fn($r) => $agregar($r, $r->pallet, $r->galpon, $r->ubicacion, $r->almacen, -1));

        $this->queryReubicaciones($filtros, 'origen')
            ->select('pallet_origen', 'codigo', 'clote', 'almacen_origen', 'galpon_origen', 'ubicacion_origen', 'descrip', 'descrip1', DB::raw('SUM(cantidad) as total'))
            ->groupBy('pallet_origen', 'codigo', 'clote', 'almacen_origen', 'galpon_origen', 'ubicacion_origen', 'descrip', 'descrip1')
            ->get()->each(fn($r) => $agregar($r, $r->pallet_origen, $r->galpon_origen, $r->ubicacion_origen, $r->almacen_origen, -1));

        $this->queryReubicaciones($filtros, 'destino')
            ->select('pallet_destino', 'codigo', 'clote', 'almacen_destino', 'galpon_destino', 'ubicacion_destino', 'descrip', 'descrip1', DB::raw('SUM(cantidad) as total'))
            ->groupBy('pallet_destino', 'codigo', 'clote', 'almacen_destino', 'galpon_destino', 'ubicacion_destino', 'descrip', 'descrip1')
            ->get()->each(fn($r) => $agregar($r, $r->pallet_destino, $r->codigo, $r->clote, $r->almacen_destino, $r->galpon_destino, $r->ubicacion_destino, $r->almacen_destino, 1));

        return $saldos->values()->filter(fn($s) => $s['saldo'] > 0)->values();
    }

    public function saldoDeGrupo(string $codigo, ?string $clote, string $pallet, string $almacen, string $galpon, string $ubicacion): int
    {
        $galpon = strtoupper(trim($galpon));
        $ubicacion = strtoupper(trim($ubicacion));

        $grupo = $this->calcular(['codigo' => $codigo, 'pallet' => $pallet])
            ->first(fn($s) => $s['clote'] == $clote && $s['almacen'] == $almacen && $s['galpon'] == $galpon && $s['ubicacion'] == $ubicacion);

        return $grupo['saldo'] ?? 0;
    }

    /**
     * Ubicación actual de un pallet (asume que un pallet físico está en un solo lugar).
     * Retorna null si el pallet no tiene saldo registrado (no existe o está vacío).
     */
    public function ubicacionActualDePallet(string $pallet): ?array
    {
        $primero = $this->calcular(['pallet' => $pallet])->first();

        return $primero ? [
            'almacen' => $primero['almacen'],
            'galpon' => $primero['galpon'],
            'ubicacion' => $primero['ubicacion'],
        ] : null;
    }

    private function queryBase($query, array $filtros)
    {
        return $query
            ->when($filtros['codigo'] ?? null, fn($q, $v) => $q->where('codigo', $v))
            ->when($filtros['pallet'] ?? null, fn($q, $v) => $q->where('pallet', $v))
            ->when($filtros['galpon'] ?? null, fn($q, $v) => $q->where('galpon', $v))
            ->when($filtros['ubicacion'] ?? null, fn($q, $v) => $q->where('ubicacion', $v));
    }

    private function queryReubicaciones(array $filtros, string $lado)
    {
        $palletCol = "pallet_{$lado}";
        $galponCol = "galpon_{$lado}";
        $ubicacionCol = "ubicacion_{$lado}";

        return WmsReubicacion::query()
            ->when($filtros['codigo'] ?? null, fn($q, $v) => $q->where('codigo', $v))
            ->when($filtros['pallet'] ?? null, fn($q, $v) => $q->where($palletCol, $v))
            ->when($filtros['galpon'] ?? null, fn($q, $v) => $q->where($galponCol, $v))
            ->when($filtros['ubicacion'] ?? null, fn($q, $v) => $q->where($ubicacionCol, $v));
    }

    /**
     * Cantidad ya reservada (pendiente de chequeo) de una combinación específica,
     * para descontarla del saldo disponible al momento de generar una nueva Salida.
     */
    public function reservadoEnOtsPendientes(string $codigo, ?string $clote, string $pallet, string $almacen, string $galpon, string $ubicacion): int
    {
        return (int) WmsOrdenTrabajoDetalle::query()
            ->whereHas('ordenTrabajo', fn($q) => $q->where('estado', 'pendiente'))
            ->where('codigo', $codigo)
            ->where('clote', $clote)
            ->where('pallet', $pallet)
            ->where('almacen_origen', $almacen)
            ->where('galpon_origen', $galpon)
            ->where('ubicacion_origen', $ubicacion)
            ->sum('cantidad');
    }
}
