<?php

namespace App\\Services;

use App\\Models\\WmsIngreso;
use Illuminate\\Support\\Facades\\DB;
use LogicException;

class WmsStockLockService
{
    /**
     * Serializa las operaciones por identificador de pallet, incluso cuando
     * todavía no existe ninguna fila de ingreso para ese pallet.
     *
     * Todos los flujos que modifiquen stock deben invocar este método dentro
     * de la misma transacción antes de leer el saldo y escribir movimientos.
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

        if (DB::transactionLevel() < 1) {
            throw new LogicException(
                'WmsStockLockService::bloquearPallets debe ejecutarse dentro de una transacción.'
            );
        }

        // La fila de wms_stock_locks existe aunque el pallet aún no tenga
        // ingresos. La clave única permite que dos transacciones concurrentes
        // compitan por la misma fila de anclaje sin depender de stock existente.
        foreach ($pallets as $pallet) {
            DB::table('wms_stock_locks')->insertOrIgnore([
                'pallet' => $pallet,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('wms_stock_locks')
            ->whereIn('pallet', $pallets)
            ->orderBy('pallet')
            ->lockForUpdate()
            ->get(['pallet']);

        // También bloquea las filas físicas existentes del pallet. Mantener
        // este bloqueo ayuda a proteger los flujos que ya dependen de ingresos.
        WmsIngreso::query()
            ->whereIn('pallet', $pallets)
            ->orderBy('id')
            ->lockForUpdate()
            ->get(['id']);
    }

    /** Cantidad reservada en órdenes de trabajo que aún están pendientes. */
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
