<?php

namespace App\Services;

use App\Models\WmsExcepcionDespacho;
use Illuminate\Support\Carbon;

class LoteExcepcionService
{
    public function __construct(private SaldoService $saldoService) {}

    public function stockTotal(string $codigo, ?string $clote): int
    {
        return (int) $this->saldoService->calcular(['codigo' => $codigo])
            ->filter(fn($s) => $s['clote'] == $clote)
            ->sum('saldo');
    }

    /**
     * Extrae la fecha de producción de un lote con formato "AAMMDD-NNNNN".
     * Retorna null si el lote es nulo o no coincide con ese formato.
     */
    public function fechaProduccion(?string $lote): ?Carbon
    {
        if (!$lote || !preg_match('/^(\d{2})(\d{2})(\d{2})/', $lote, $m)) {
            return null;
        }

        try {
            return Carbon::createFromDate(2000 + (int) $m[1], (int) $m[2], (int) $m[3])->startOfDay();
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Determina si un lote es elegible para Cambio de Lote por Excepción,
     * según su fecha de producción vs. la fecha límite configurada.
     * Si no se puede determinar la fecha (formato inválido), NO es elegible
     * (comportamiento conservador: ante la duda, se bloquea).
     */
    public function esElegibleParaCambioLote(?string $lote): bool
    {
        $fecha = $this->fechaProduccion($lote);

        if (!$fecha) {
            return false;
        }

        return $fecha->lt(Carbon::parse(config('wms.cambio_lote_fecha_limite')));
    }

    /**
     * Lotes alternativos con stock disponible, excluyendo el lote declarado,
     * ordenados por fecha de producción ascendente (más antiguo primero).
     */
    /**
     * Lotes con stock disponible para armar el cambio de lote, INCLUYENDO el lote
     * originalmente declarado en la nota si aún conserva saldo parcial (para que
     * el usuario pueda combinarlo con otros lotes hasta completar el 100%).
     * Ordenados por fecha de producción ascendente (más antiguo primero).
     */
    public function lotesAlternativos(string $codigo, ?string $loteOriginal): array
    {
        return $this->saldoService->calcular(['codigo' => $codigo])
            ->groupBy('clote')
            ->map(function ($items, $clote) use ($loteOriginal) {
                $fecha = $this->fechaProduccion($clote);

                return [
                    'clote' => $clote ?: 'S/L',
                    'saldo_total' => $items->sum('saldo'),
                    'fecha_orden' => $fecha ?? Carbon::create(9999, 1, 1),
                    'fecha_produccion' => $fecha ? $fecha->format('d/m/Y') : 'S/F',
                    'es_lote_original' => $clote == $loteOriginal,   // <-- nuevo: para marcarlo distinto en la UI
                ];
            })
            ->filter(fn($l) => $l['saldo_total'] > 0)
            ->sortBy('fecha_orden')
            ->values()
            ->map(fn($l) => [
                'clote' => $l['clote'],
                'saldo_total' => $l['saldo_total'],
                'fecha_produccion' => $l['fecha_produccion'],
                'es_lote_original' => $l['es_lote_original'],
            ])
            ->all();
    }

    /**
     * Distribución automática FIFO: reparte la cantidad necesaria entre los
     * lotes alternativos disponibles, empezando por el de producción más antigua.
     */
    public function distribuirAutomatico(string $codigo, ?string $loteExcluir, int $cantidadNecesaria): array
    {
        // ANTES: ->filter(fn ($s) => $s['clote'] != $loteExcluir && $s['saldo'] > 0)
        $porLote = $this->saldoService->calcular(['codigo' => $codigo])
            ->filter(fn($s) => $s['saldo'] > 0)   // <-- ya no excluye el lote original
            ->groupBy('clote');

        $lotesOrdenados = $porLote->keys()
            ->map(fn($clote) => ['clote' => $clote, 'fecha' => $this->fechaProduccion($clote) ?? Carbon::create(9999, 1, 1)])
            ->sortBy('fecha')
            ->pluck('clote');

        $restante = $cantidadNecesaria;
        $propuesta = [];

        foreach ($lotesOrdenados as $clote) {
            if ($restante <= 0) break;

            $itemsLote = $porLote->get($clote);
            $disponibleLote = $itemsLote->sum('saldo');
            if ($disponibleLote <= 0) continue;

            $aTomar = min($restante, $disponibleLote);
            $pendienteEnLote = $aTomar;
            $pallets = [];

            foreach ($itemsLote as $item) {
                if ($pendienteEnLote <= 0) break;
                $tomar = min($pendienteEnLote, $item['saldo']);
                if ($tomar <= 0) continue;

                $pallets[] = [
                    'pallet' => $item['pallet'],
                    'almacen' => $item['almacen'],
                    'galpon' => $item['galpon'],
                    'ubicacion' => $item['ubicacion'],
                    'cantidad' => $tomar,
                    'saldo' => $item['saldo'],
                ];
                $pendienteEnLote -= $tomar;
            }

            $propuesta[] = [
                'clote' => $clote,
                'fecha_produccion' => optional($this->fechaProduccion($clote))->format('d/m/Y') ?? 'S/F',
                'subtotal' => $aTomar,
                'pallets' => $pallets,
            ];

            $restante -= $aTomar;
        }

        return [
            'completo' => $restante <= 0,
            'faltante' => max($restante, 0),
            'propuesta' => $propuesta,
        ];
    }

    public function validar(string $codigo, string $loteAplicado, int $cantidadNecesaria): array
    {
        $stockDisponible = $this->stockTotal($codigo, $loteAplicado);

        if ($stockDisponible < $cantidadNecesaria) {
            return [
                'valido' => false,
                'mensaje' => "El lote {$loteAplicado} no tiene stock suficiente. Disponible: {$stockDisponible}, requerido: {$cantidadNecesaria}.",
            ];
        }

        return ['valido' => true, 'mensaje' => "Stock disponible: {$stockDisponible} cajas."];
    }

    public function registrarExcepcion(string $tipoRegistro, string $idRegistro, string $codigo, ?string $descrip, ?string $descrip1, ?string $loteSolicitado, string $loteAplicado, int $cantidad, ?string $motivo = null): WmsExcepcionDespacho
    {
        return WmsExcepcionDespacho::create([
            'tipo_registro' => $tipoRegistro,
            'id_registro' => $idRegistro,
            'codigo' => $codigo,
            'descrip' => $descrip,
            'descrip1' => $descrip1,
            'lote_solicitado' => $loteSolicitado,
            'lote_aplicado' => $loteAplicado,
            'cantidad' => $cantidad,
            'motivo' => $motivo,
        ]);
    }
}
