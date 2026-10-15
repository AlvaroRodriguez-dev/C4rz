<?php

namespace App\Services;

use App\Models\WmsEntregaProduccion;
use App\Models\WmsHu;
use App\Models\WmsHuVerificacion;
use App\Models\WmsHuDiscrepancia;
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

            if (!in_array($entrega->estado, ['PENDIENTE_VERIFICACION', 'PENDIENTE_REVERIFICACION', 'EN_VERIFICACION'], true)) {
                throw new RuntimeException(
                    "La liberación {$entrega->id} no está pendiente de verificación de pallets."
                );
            }

            $totalPallets = $entrega->hu()->where('estado', '!=', 'SUSTITUIDO')->count();
            if ($totalPallets === 0) {
                throw new RuntimeException('La liberación no tiene pallets generados.');
            }

            if (in_array($entrega->estado, ['PENDIENTE_VERIFICACION', 'PENDIENTE_REVERIFICACION'], true)) {
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
            ->with(['detalles.entregaDetalle', 'verificacion', 'discrepancias'])
            ->where('entrega_id', $entrega->id)
            ->where('estado', '!=', 'SUSTITUIDO')
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
        ?string $observacion = null,
        array $discrepancias = []
    ): WmsHuVerificacion {
        if ($cantidadVerificada < 0) {
            throw new RuntimeException('La cantidad verificada no puede ser negativa.');
        }

        return DB::transaction(function () use ($entrega, $hu, $cantidadVerificada, $observacion, $discrepancias) {
            $entrega = WmsEntregaProduccion::query()->lockForUpdate()->findOrFail($entrega->id);
            $hu = WmsHu::query()->with('detalles.entregaDetalle')->lockForUpdate()->findOrFail($hu->id);

            if ((int) $hu->entrega_id !== (int) $entrega->id) {
                throw new RuntimeException('El pallet no pertenece a la liberación indicada.');
            }

            if (!in_array($entrega->estado, ['PENDIENTE_VERIFICACION', 'EN_VERIFICACION'], true)) {
                throw new RuntimeException('La liberación ya no está en proceso de verificación de pallets.');
            }

            $verificacionAnterior = WmsHuVerificacion::query()
                ->where('hu_id', $hu->id)
                ->orderByDesc('id')
                ->first();

            $requiereReverificacion = $verificacionAnterior
                && WmsHuDiscrepancia::query()
                    ->where('hu_id', $hu->id)
                    ->where('estado', 'CORREGIDA')
                    ->where('corregido_at', '>', $verificacionAnterior->verificado_at)
                    ->exists();

            if ($verificacionAnterior && !$requiereReverificacion) {
                throw new RuntimeException("El pallet {$hu->numero} ya fue verificado y no tiene una corrección pendiente de reverificación.");
            }

            $esperada = (int) $hu->cantidad_total;
            $diferencia = $cantidadVerificada - $esperada;
            $resultado = ($diferencia === 0 && empty($discrepancias)) ? 'CONFIRMADO' : 'CONFIRMADO_CON_DIFERENCIA';

            $usuarioId = auth()->id() ?: null;

            if ($diferencia !== 0) {
                WmsHuDiscrepancia::create([
                    'hu_id' => $hu->id,
                    'entrega_id' => $entrega->id,
                    'hu_detalle_id' => null,
                    'tipo' => 'CANTIDAD',
                    'valor_esperado' => (string) $esperada,
                    'valor_observado' => (string) $cantidadVerificada,
                    'observacion' => $observacion,
                    'estado' => 'PENDIENTE_CORRECCION',
                    'reportado_id' => $usuarioId,
                    'reportado_at' => now(),
                ]);
            }

            foreach ($discrepancias as $incidencia) {
                $detalle = $hu->detalles->firstWhere('id', (int) ($incidencia['hu_detalle_id'] ?? 0));
                if (!$detalle) {
                    throw new RuntimeException('El detalle seleccionado no pertenece al pallet verificado.');
                }

                $tipo = strtoupper((string) ($incidencia['tipo'] ?? ''));
                $partesLote = explode('-', (string) $detalle->lote);
                $sufijoLote = (string) end($partesLote);
                $esperado = match ($tipo) {
                    'TONO' => $detalle->entregaDetalle?->tono ?? (strlen($sufijoLote) >= 5 ? substr($sufijoLote, 0, 3) : ''),
                    'CALIBRE' => $detalle->entregaDetalle?->calibre ?? (strlen($sufijoLote) >= 5 ? substr($sufijoLote, 3, 2) : ''),
                    'CALIDAD_PRODUCTO' => trim((string) $detalle->codigo . ' / ' . (string) $detalle->calidad),
                    default => throw new RuntimeException('El tipo de discrepancia no es válido.'),
                };

                WmsHuDiscrepancia::create([
                    'hu_id' => $hu->id,
                    'entrega_id' => $entrega->id,
                    'hu_detalle_id' => $detalle->id,
                    'tipo' => $tipo,
                    'valor_esperado' => (string) $esperado,
                    'valor_observado' => trim((string) ($incidencia['valor_observado'] ?? '')),
                    'observacion' => $incidencia['observacion'] ?? null,
                    'estado' => 'PENDIENTE_CORRECCION',
                    'reportado_id' => $usuarioId,
                    'reportado_at' => now(),
                ]);
            }

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

            $palletsActivos = WmsHu::query()
                ->where('entrega_id', $entrega->id)
                ->where('estado', '!=', 'SUSTITUIDO')
                ->with(['verificacion', 'discrepancias'])
                ->get();

            $pendientes = $palletsActivos->filter(function (WmsHu $pallet) {
                $ultima = $pallet->verificacion;
                if (!$ultima) {
                    return true;
                }

                return $pallet->discrepancias->contains(fn (WmsHuDiscrepancia $d) =>
                    $d->estado === 'CORREGIDA'
                    && $d->corregido_at
                    && $d->corregido_at->gt($ultima->verificado_at)
                );
            })->count();

            if ($pendientes === 0) {
                $hayDiscrepancias = WmsHuDiscrepancia::query()
                    ->where('entrega_id', $entrega->id)
                    ->where('estado', 'PENDIENTE_CORRECCION')
                    ->exists();

                $entrega->update([
                    'estado' => $hayDiscrepancias ? 'OBSERVADA' : 'VERIFICADA',
                    'verificado_at' => $hayDiscrepancias ? null : now(),
                    'verificado_id' => $hayDiscrepancias ? null : $usuarioId,
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
        $pallets = $entrega->hu()->where('estado', '!=', 'SUSTITUIDO')->with(['verificacion', 'discrepancias'])->get();
        $requiereReverificacion = fn (WmsHu $hu) => $hu->verificacion
            && $hu->discrepancias->contains(fn (WmsHuDiscrepancia $d) =>
                $d->estado === 'CORREGIDA'
                && $d->corregido_at
                && $d->corregido_at->gt($hu->verificacion->verificado_at)
            );

        return [
            'total' => $pallets->count(),
            'verificados' => $pallets->filter(fn (WmsHu $hu) => $hu->verificacion !== null && !$requiereReverificacion($hu))->count(),
            'pendientes' => $pallets->filter(fn (WmsHu $hu) => $hu->verificacion === null || $requiereReverificacion($hu))->count(),
            'con_diferencia' => $pallets->filter(fn (WmsHu $hu) => $hu->verificacion?->resultado === 'CONFIRMADO_CON_DIFERENCIA' && !$requiereReverificacion($hu))->count(),
            'discrepancias_pendientes' => WmsHuDiscrepancia::query()->where('entrega_id', $entrega->id)->where('estado', 'PENDIENTE_CORRECCION')->count(),
        ];
    }
}
