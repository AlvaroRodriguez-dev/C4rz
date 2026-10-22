<?php

namespace App\Services;

use App\Models\WmsIngreso;
use Illuminate\Support\Facades\DB;
use LogicException;

/**
 * Serializa operaciones de stock sobre un pallet usando sus filas de ingreso
 * como ancla de bloqueo. Todo flujo que modifique el saldo de un pallet debe
 * adquirir estos bloqueos dentro de la misma transacción antes de recalcularlo.
 */
class WmsStockLockService
{
    /**
     * Bloquea los pallets en orden determinista para reducir el riesgo de deadlocks.
     *
     * @param array<int, string> $pallets
     */
    public function bloquearPallets(array $pallets): void
    {
        $pallets = collect($pallets)
            ->map(fn ($pallet) => trim((string) $pallet))
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();

        if ($pallets === []) {
            return;
        }

        // lockForUpdate fuera de una transacción no protege la validación posterior.
        // Fallar explícitamente evita que una futura ruta use el servicio de forma insegura.
        if (DB::transactionLevel() < 1) {
            throw new LogicException(
                'WmsStockLockService::bloquearPallets debe ejecutarse dentro de una transacción.'
            );
        }

        // Los pallets con saldo necesariamente tienen al menos un ingreso.
        // Bloquear todas sus filas evita que dos operaciones WMS cooperantes
        // validen y muevan simultáneamente el mismo pallet.
        WmsIngreso::query()
            ->whereIn('pallet', $pallets)
            ->orderBy('id')
            ->lockForUpdate()
            ->get(['id']);
    }

    /**
     * Cantidad reservada en órdenes de trabajo que aún están pendientes.
     */
    public function cantidadReservada(string $pallet): int
    {
        return (int) DB::table('wms_orden_trabajo_detalle as detalle')
            ->join('wms_ordenes_trabajo as orden', 'orden.id', '=', 'detalle.orden_trabajo_id')
            ->where('detalle.pallet', $pallet)
            ->where('orden.estado', 'pendiente')
            ->whereNull('orden.deleted_at')
            ->sum('detalle.cantidad');
    }
}
