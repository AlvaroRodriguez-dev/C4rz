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
            $detalles = WmsEntregaDetalle::query()
                ->where('entrega_id', $entrega->id)
                ->lockForUpdate()
                ->get();

            $disponibles = collect($this->construirDisponible($detalles))->keyBy('id');

            if ($disponibles->isEmpty()) {
                throw new RuntimeException('La entrega no tiene detalle disponible para paletizar.');
            }

            // Acumulamos lo solicitado por detalle entre todos los pallets de esta operación.
            // Así evitamos que dos pallets consuman más cantidad de la que realmente queda pendiente.
            $solicitadoPorDetalle = [];

            foreach ($pallets as $index => $pallet) {
                $items = collect($pallet['items'] ?? []);

                if ($items->isEmpty()) {
                    throw new RuntimeException('El pallet #' . ($index + 1) . ' no tiene productos.');
                }

                $ids = $items->map(fn ($item) => (int) ($item['entrega_detalle_id'] ?? 0));
                if ($ids->duplicates()->isNotEmpty()) {
                    throw new RuntimeException('El pallet #' . ($index + 1) . ' contiene el mismo producto/lote más de una vez.');
                }

                $formatos = $items->map(function ($item) use ($disponibles) {
                    $detalle = $disponibles->get((int) ($item['entrega_detalle_id'] ?? 0));

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
                    $detalleId = (int) $item['entrega_detalle_id'];
                    $detalle = $disponibles->get($detalleId);
                    $cantidad = (int) $item['cantidad'];

                    if ($cantidad < 1) {
                        throw new RuntimeException('Las cantidades de paletización deben ser mayores a cero.');
                    }

                    $solicitadoPorDetalle[$detalleId] = ($solicitadoPorDetalle[$detalleId] ?? 0) + $cantidad;
                    $total += $cantidad;

                    $pendiente = (int) $detalle['cantidad_pendiente'];
                    if ($solicitadoPorDetalle[$detalleId] > $pendiente) {
                        throw new RuntimeException("La cantidad solicitada para {$detalle['codigo']} lote {$detalle['lote']} supera la cantidad pendiente ({$pendiente}).");
                    }
                }

                if ($total > (int) $capacidad) {
                    throw new RuntimeException("El pallet del formato {$formato} supera la capacidad de {$capacidad} cajas (intentado: {$total}).");
                }
            }

            // Los correlativos se generan para el almacén de la entrega.
            // Esto mantiene la numeración independiente por almacén:
            // 110-26xxxx, 210-26xxxx, etc.
            $almacen = $entrega->almacen;

            if (!$almacen) {
                throw new RuntimeException('La entrega no tiene un almacén válido para generar los números de pallet.');
            }

            $numeros = app(PalletCorrelativoService::class)->generarSiguientes(
                $almacen,
                count($pallets)
            );

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

            // La entrega cambia a PALETIZADA solamente cuando toda la cantidad
            // conciliada ya fue distribuida en HUs. Si todavía quedan cajas,
            // permanece CONCILIADA para permitir continuar en otra operación.
            $pendienteTotal = $detalles->sum(function (WmsEntregaDetalle $detalle) {
                return max(
                    (int) $detalle->cantidad_fisica - (int) $detalle->cantidad_paletizada,
                    0
                );
            });

            if ($pendienteTotal === 0) {
                $entrega->update([
                    'estado' => 'PALETIZADA',
                    'update_id' => $userId,
                ]);
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
