<?php

namespace App\Services;

use App\Models\WmsEntregaDetalle;
use App\Models\WmsEntregaProduccion;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class WmsConciliacionService
{
    private const ESTADO_PENDIENTE_VERIFICACION = 'PENDIENTE_VERIFICACION';
    private const ESTADO_EN_VERIFICACION = 'EN_VERIFICACION';
    private const ESTADO_CONCILIADA = 'CONCILIADA';
    private const ESTADO_CON_DIFERENCIA = 'CON_DIFERENCIA';

    private const DETALLE_PENDIENTE = 'PENDIENTE';
    private const DETALLE_CONCILIADO = 'CONCILIADO';
    private const DETALLE_CON_DIFERENCIA = 'CON_DIFERENCIA';

    public function iniciarVerificacion(WmsEntregaProduccion $entrega): WmsEntregaProduccion
    {
        return DB::transaction(function () use ($entrega) {
            $entrega = WmsEntregaProduccion::query()
                ->lockForUpdate()
                ->findOrFail($entrega->id);

            if ($entrega->estado !== self::ESTADO_PENDIENTE_VERIFICACION) {
                throw new RuntimeException(
                    "La entrega {$entrega->id} no puede iniciar verificación desde el estado {$entrega->estado}."
                );
            }

            $entrega->update([
                'estado' => self::ESTADO_EN_VERIFICACION,
                'update_id' => auth()->id() ?: $entrega->update_id,
            ]);

            return $entrega->fresh(['detalles']);
        });
    }

    public function registrarCantidadFisica(
        WmsEntregaDetalle $detalle,
        int $cantidadFisica
    ): WmsEntregaDetalle {
        if ($cantidadFisica < 0) {
            throw new RuntimeException('La cantidad física no puede ser negativa.');
        }

        return DB::transaction(function () use ($detalle, $cantidadFisica) {
            $detalle = WmsEntregaDetalle::query()
                ->lockForUpdate()
                ->findOrFail($detalle->id);

            $entrega = WmsEntregaProduccion::query()->findOrFail($detalle->entrega_id);

            if ($entrega->estado !== self::ESTADO_EN_VERIFICACION) {
                throw new RuntimeException(
                    "La entrega {$entrega->id} no está en proceso de verificación."
                );
            }

            if (in_array($detalle->estado, [
                self::DETALLE_CONCILIADO,
                self::DETALLE_CON_DIFERENCIA,
            ], true)) {
                throw new RuntimeException(
                    "La línea {$detalle->id} ya fue conciliada y no puede modificarse."
                );
            }

            $detalle->update([
                'cantidad_fisica' => $cantidadFisica,
            ]);

            return $detalle->fresh();
        });
    }

    public function conciliar(WmsEntregaProduccion $entrega): WmsEntregaProduccion
    {
        return DB::transaction(function () use ($entrega) {
            $entrega = WmsEntregaProduccion::query()
                ->lockForUpdate()
                ->findOrFail($entrega->id);

            if ($entrega->estado !== self::ESTADO_EN_VERIFICACION) {
                throw new RuntimeException(
                    "La entrega {$entrega->id} no puede conciliarse desde el estado {$entrega->estado}."
                );
            }

            $detalles = WmsEntregaDetalle::query()
                ->where('entrega_id', $entrega->id)
                ->lockForUpdate()
                ->orderBy('orden')
                ->get();

            if ($detalles->isEmpty()) {
                throw new RuntimeException(
                    "La entrega {$entrega->id} no tiene detalles para conciliar."
                );
            }

            $pendientes = $detalles->filter(
                fn (WmsEntregaDetalle $detalle) => $detalle->cantidad_fisica === null
            );

            if ($pendientes->isNotEmpty()) {
                $ids = $pendientes->pluck('id')->implode(', ');

                throw new RuntimeException(
                    "No se puede conciliar la entrega {$entrega->id}. "
                    . "Faltan cantidades físicas en las líneas: {$ids}."
                );
            }

            $totalFisico = 0;
            $conDiferencia = false;

            foreach ($detalles as $detalle) {
                $cantidadDeclarada = (int) $detalle->cantidad_declarada;
                $cantidadFisica = (int) $detalle->cantidad_fisica;
                $diferencia = $cantidadFisica - $cantidadDeclarada;

                $totalFisico += $cantidadFisica;

                if ($diferencia === 0) {
                    $detalle->update([
                        'estado' => self::DETALLE_CONCILIADO,
                    ]);
                } else {
                    $conDiferencia = true;

                    $detalle->update([
                        'estado' => self::DETALLE_CON_DIFERENCIA,
                    ]);
                }
            }

            $usuarioId = auth()->id() ?: null;

            $entrega->update([
                'total_fisico' => $totalFisico,
                'estado' => $conDiferencia
                    ? self::ESTADO_CON_DIFERENCIA
                    : self::ESTADO_CONCILIADA,
                'fecha_recepcion' => now()->toDateString(),
                'verificado_at' => now(),
                'verificado_id' => $usuarioId,
                'update_id' => $usuarioId ?: $entrega->update_id,
            ]);

            return $entrega->fresh(['detalles', 'documento', 'almacen', 'verificadoPor']);
        });
    }
}
