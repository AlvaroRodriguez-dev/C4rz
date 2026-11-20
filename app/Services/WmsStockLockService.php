<?php

namespace App\Services;

use App\Models\WmsIngreso;
use Illuminate\Support\Facades\DB;
use LogicException;

class WmsStockLockService
{
    /**
     * Serializa las reservas de una misma nota de despacho, incluso cuando
     * todavía no existe ninguna orden de trabajo para esa nota.
     *
     * Debe invocarse dentro de la transacción que valida y crea la reserva,
     * antes de consultar cantidades ya despachadas o reservadas.
     */
    public function bloquearNota(string $tipoRegistro, string $idRegistro): void
    {
        if (DB::transactionLevel() < 1) {
            throw new LogicException(
                'WmsStockLockService::bloquearNota debe ejecutarse dentro de una transacción.'
            );
        }

        $tipoRegistro = trim($tipoRegistro);
        $idRegistro = trim($idRegistro);

        if ($tipoRegistro === '' || $idRegistro === '') {
            throw new LogicException(
                'WmsStockLockService::bloquearNota requiere tipo e identificador de nota.'
            );
        }

        DB::table('wms_reservation_locks')->insertOrIgnore([
            'tipo_registro' => $tipoRegistro,
            'id_registro' => $idRegistro,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('wms_reservation_locks')
            ->where('tipo_registro', $tipoRegistro)
            ->where('id_registro', $idRegistro)
            ->lockForUpdate()
            ->first(['id']);
    }

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
