<?php

namespace App\Services;

use App\Models\WmsEntregaProduccion;
use App\Models\WmsHu;
use App\Models\WmsHuVerificacion;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class WmsVerificacionPalletService
{
    public function iniciar(WmsEntregaProduccion $entrega): WmsEntregaProduccion
    {
        return DB::transaction(function () use ($entrega) {
            $entrega = WmsEntregaProduccion::query()
                ->lockForUpdate()
                ->findOrFail($entrega->id);

            if (!in_array($entrega->estado, ['PENDIENTE_VERIFICACION', 'EN_VERIFICACION'], true)) {
                throw new RuntimeException(
                    "La liberación {$entrega->id} no está pendiente de verificación de pallets."
                );
            }

            $totalPallets = $entrega->hu()->count();
            if ($totalPallets === 0) {
                throw new RuntimeException('La liberación no tiene pallets generados.');
            }

            if ($entrega->estado === 'PENDIENTE_VERIFICACION') {
                $entrega->update([
                    'estado' => 'EN_VERIFICACION',
                    'update_id' => auth()->id() ?: $entrega->update_id,
                ]);
            }

            return $entrega->fresh();
        });
    }

    public function buscarPallet(WmsEntregaProduccion $entrega, string $numero): WmsHu
    {
        $numero = trim($numero);

        if ($numero === '') {
            throw new RuntimeException('Debe ingresar o escanear el número del pallet.');
        }

        $hu = WmsHu::query()
            ->with(['detalles', 'verificacion'])
            ->where('entrega_id', $entrega->id)
            ->where('numero', $numero)
            ->first();

        if (!$hu) {
            throw new RuntimeException('El pallet escaneado no pertenece a esta liberación.');
        }

        return $hu;
    }

    public function confirmar(
        WmsEntregaProduccion $entrega,
        WmsHu $hu,
        int $cantidadVerificada,
        ?string $observacion = null
    ): WmsHuVerificacion {
        if ($cantidadVerificada < 0) {
            throw new RuntimeException('La cantidad verificada no puede ser negativa.');
        }

        return DB::transaction(function () use ($entrega, $hu, $cantidadVerificada, $observacion) {
            $entrega = WmsEntregaProduccion::query()->lockForUpdate()->findOrFail($entrega->id);
            $hu = WmsHu::query()->lockForUpdate()->findOrFail($hu->id);

            if ($hu->entrega_id !== $entrega->id) {
                throw new RuntimeException('El pallet no pertenece a la liberación indicada.');
            }

            if (!in_array($entrega->estado, ['PENDIENTE_VERIFICACION', 'EN_VERIFICACION'], true)) {
                throw new RuntimeException('La liberación ya no está en proceso de verificación de pallets.');
            }

            if ($hu->verificacion()->exists()) {
                throw new RuntimeException("El pallet {$hu->numero} ya fue verificado.");
            }

            $esperada = (int) $hu->cantidad_total;
            $diferencia = $cantidadVerificada - $esperada;
            $resultado = $diferencia === 0 ? 'CONFIRMADO' : 'CONFIRMADO_CON_DIFERENCIA';

            $usuarioId = auth()->id() ?: null;

            $verificacion = WmsHuVerificacion::create([
                'hu_id' => $hu->id,
                'entrega_id' => $entrega->id,
                'cantidad_esperada' => $esperada,
                'cantidad_verificada' => $cantidadVerificada,
                'diferencia' => $diferencia,
                'resultado' => $resultado,
                'observacion' => $observacion,
                'verificado_id' => $usuarioId,
                'verificado_at' => now(),
                'created_id' => $usuarioId,
                'update_id' => $usuarioId,
            ]);

            $pendientes = WmsHu::query()
                ->where('entrega_id', $entrega->id)
                ->whereDoesntHave('verificacion')
                ->count();

            if ($pendientes === 0) {
                $entrega->update([
                    'estado' => 'VERIFICADA',
                    'verificado_at' => now(),
                    'verificado_id' => $usuarioId,
                    'update_id' => $usuarioId ?: $entrega->update_id,
                ]);
            } else {
                $entrega->update([
                    'estado' => 'EN_VERIFICACION',
                    'update_id' => $usuarioId ?: $entrega->update_id,
                ]);
            }

            return $verificacion->fresh(['hu', 'verificadoPor']);
        });
    }

    public function resumen(WmsEntregaProduccion $entrega): array
    {
        $pallets = $entrega->hu()->with('verificacion')->get();

        return [
            'total' => $pallets->count(),
            'verificados' => $pallets->filter(fn (WmsHu $hu) => $hu->verificacion !== null)->count(),
            'pendientes' => $pallets->filter(fn (WmsHu $hu) => $hu->verificacion === null)->count(),
            'con_diferencia' => $pallets->filter(fn (WmsHu $hu) => $hu->verificacion?->resultado === 'CONFIRMADO_CON_DIFERENCIA')->count(),
        ];
    }
}
