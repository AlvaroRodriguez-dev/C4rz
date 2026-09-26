<?php

namespace App\Services;

use App\Models\WmsConfigPallet;
use App\Models\WmsEntregaDetalle;
use App\Models\WmsEntregaProduccion;
use App\Models\WmsHu;
use App\Models\WmsHuDetalle;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class WmsPaletizacionService
{
    public function detalleDisponible(WmsEntregaProduccion $entrega): array
    {
        $entrega->loadMissing('detalles');

        return $this->construirDisponible($entrega->detalles);
    }

    public function guardar(WmsEntregaProduccion $entrega, array $pallets, int $userId): array
    {
        if ($entrega->estado !== 'CONCILIADA') {
            throw new RuntimeException('Solo se puede paletizar una entrega en estado CONCILIADA.');
        }

        if (empty($pallets)) {
            throw new RuntimeException('Debe existir al menos un pallet para guardar la paletización.');
        }

        return DB::transaction(function () use ($entrega, $pallets, $userId) {
            // Bloqueamos los detalles fuente durante toda la operación para evitar
            // que dos usuarios paleticen simultáneamente la misma cantidad física.
            $detalles = WmsEntregaDetalle::query()
                ->where('entrega_id', $entrega->id)
                ->lockForUpdate()
                ->get();

            $disponibles = collect($this->construirDisponible($detalles))->keyBy('id');

            if ($disponibles->isEmpty()) {
                throw new RuntimeException('La entrega no tiene detalle disponible para paletizar.');
            }

            foreach ($pallets as $index => $pallet) {
                $items = collect($pallet['items'] ?? []);

                if ($items->isEmpty()) {
                    throw new RuntimeException('El pallet #' . ($index + 1) . ' no tiene productos.');
                }

                $formatos = $items->map(function ($item) use ($disponibles) {
                    $detalle = $disponibles->get((int) $item['entrega_detalle_id']);

                    if (!$detalle) {
                        throw new RuntimeException('Existe un detalle de producción que no pertenece a esta entrega.');
                    }

                    return $detalle['formato'];
                })->unique()->values();

                if ($formatos->count() !== 1) {
                    throw new RuntimeException('Un pallet no puede contener productos de distinto formato.');
                }

                $formato = $formatos->first();
                $capacidad = $disponibles->firstWhere('formato', $formato)['capacidad'] ?? null;

                if (!$capacidad) {
                    throw new RuntimeException("El formato {$formato} no tiene configuración de capacidad de pallet.");
                }

                $total = 0;

                foreach ($items as $item) {
                    $detalle = $disponibles->get((int) $item['entrega_detalle_id']);
                    $cantidad = (int) $item['cantidad'];

                    if ($cantidad < 1) {
                        throw new RuntimeException('Las cantidades de paletización deben ser mayores a cero.');
                    }

                    $pendiente = (int) $detalle['cantidad_pendiente'];
                    if ($cantidad > $pendiente) {
                        throw new RuntimeException("La cantidad solicitada para {$detalle['codigo']} lote {$detalle['lote']} supera la cantidad pendiente ({$pendiente}).");
                    }

                    $total += $cantidad;
                }

                if ($total > $capacidad) {
                    throw new RuntimeException("El pallet del formato {$formato} supera la capacidad de {$capacidad} cajas (intentado: {$total}).");
                }
            }

            $numeros = app(PalletCorrelativoService::class)->generarSiguientes(count($pallets));
            $creados = [];

            foreach ($pallets as $index => $pallet) {
                $items = collect($pallet['items']);
                $primerDetalle = $disponibles->get((int) $items->first()['entrega_detalle_id']);
                $formato = $primerDetalle['formato'];
                $capacidad = $primerDetalle['capacidad'];
                $cantidadTotal = $items->sum(fn ($item) => (int) $item['cantidad']);

                $hu = WmsHu::create([
                    'numero' => $numeros[$index],
                    'entrega_id' => $entrega->id,
                    'almacen_id' => $entrega->almacen_id,
                    'ubicacion_id' => null,
                    'formato' => $formato,
                    'capacidad_estandar' => $capacidad,
                    'cantidad_total' => $cantidadTotal,
                    'tipo' => $cantidadTotal === (int) $capacidad ? 'COMPLETO' : 'SALDO',
                    'estado' => 'PALETIZADO',
                    'created_id' => $userId,
                    'update_id' => $userId,
                ]);

                foreach ($items as $item) {
                    $detalle = $disponibles->get((int) $item['entrega_detalle_id']);
                    $cantidad = (int) $item['cantidad'];

                    WmsHuDetalle::create([
                        'hu_id' => $hu->id,
                        'entrega_detalle_id' => $detalle['id'],
                        'codigo' => $detalle['codigo'],
                        'lote' => $detalle['lote'],
                        'descripcion' => $detalle['descripcion'],
                        'descripcion2' => $detalle['descripcion2'],
                        'formato' => $detalle['formato'],
                        'calidad' => $detalle['calidad'],
                        'cantidad' => $cantidad,
                    ]);

                    $detalles->firstWhere('id', $detalle['id'])?->increment('cantidad_paletizada', $cantidad);
                    $disponibles[$detalle['id']]['cantidad_paletizada'] += $cantidad;
                    $disponibles[$detalle['id']]['cantidad_pendiente'] -= $cantidad;
                }

                $creados[] = [
                    'id' => $hu->id,
                    'numero' => $hu->numero,
                    'formato' => $hu->formato,
                    'cantidad_total' => $hu->cantidad_total,
                    'tipo' => $hu->tipo,
                    'estado' => $hu->estado,
                ];
            }

            return $creados;
        });
    }

    private function construirDisponible($detalles): array
    {
        $ids = $detalles->pluck('id');

        $paletizado = WmsHuDetalle::query()
            ->whereIn('entrega_detalle_id', $ids)
            ->select('entrega_detalle_id', DB::raw('SUM(cantidad) as total'))
            ->groupBy('entrega_detalle_id')
            ->pluck('total', 'entrega_detalle_id');

        return $detalles->map(function (WmsEntregaDetalle $detalle) use ($paletizado) {
            $yaPaletizado = (int) ($paletizado[$detalle->id] ?? 0);
            $fisico = (int) $detalle->cantidad_fisica;
            $pendiente = max($fisico - $yaPaletizado, 0);
            $formato = $detalle->formato ?: strtoupper(substr($detalle->codigo, 5, 4));
            $config = WmsConfigPallet::find($formato);

            return [
                'id' => $detalle->id,
                'orden' => $detalle->orden,
                'codigo' => $detalle->codigo,
                'descripcion' => $detalle->descripcion,
                'descripcion2' => $detalle->descripcion2,
                'calidad' => $detalle->calidad,
                'formato' => $formato,
                'lote' => $detalle->lote,
                'cantidad_fisica' => $fisico,
                'cantidad_paletizada' => $yaPaletizado,
                'cantidad_pendiente' => $pendiente,
                'tono' => $detalle->tono,
                'calibre' => $detalle->calibre,
                'capacidad' => $config?->cajas_x_pallet,
                'configurado' => (bool) $config,
            ];
        })->values()->all();
    }
}
