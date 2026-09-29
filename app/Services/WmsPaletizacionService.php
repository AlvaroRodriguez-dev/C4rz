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

    /**
     * Genera automaticamente los pallets a partir de la cantidad conciliada.
     *
     * Las reglas de palletizacion son las mismas del ingreso WMS:
     * - un pallet no mezcla formatos;
     * - un pallet no mezcla producto/lote/tono/calibre;
     * - se respeta la capacidad configurada por formato;
     * - el remanente se registra como SALDO.
     *
     * Los HUs generados quedan vinculados a la misma entrega/documento y
     * posteriormente seran utilizados para preparar el ingreso WMS.
     */
    public function guardar(WmsEntregaProduccion $entrega, array $pallets, int $userId): array
    {
        if (!in_array($entrega->estado, ['CONCILIADA', 'CON_DIFERENCIA'], true)) {
            throw new RuntimeException('Solo se puede paletizar una entrega en estado CONCILIADA o CON_DIFERENCIA.');
        }

        return DB::transaction(function () use ($entrega, $userId) {
            $detalles = WmsEntregaDetalle::query()
                ->where('entrega_id', $entrega->id)
                ->lockForUpdate()
                ->get();

            $disponibles = collect($this->construirDisponible($detalles))
                ->keyBy('id');

            $pendientes = $disponibles
                ->filter(fn (array $detalle) => (int) $detalle['cantidad_pendiente'] > 0)
                ->values();

            if ($pendientes->isEmpty()) {
                throw new RuntimeException('La entrega no tiene cantidades pendientes de paletizar.');
            }

            $definiciones = [];

            // Cada grupo representa una combinacion homogenea de producto,
            // formato y lote (el lote conserva tono y calibre).
            $grupos = $pendientes->groupBy(function (array $detalle) {
                return implode('|', [
                    $detalle['codigo'],
                    $detalle['formato'],
                    $detalle['lote'] ?? '',
                    $detalle['tono'] ?? '',
                    $detalle['calibre'] ?? '',
                ]);
            });

            foreach ($grupos as $items) {
                $primerDetalle = $items->first();
                $formato = $primerDetalle['formato'];
                $capacidad = (int) ($primerDetalle['capacidad'] ?? 0);

                if ($capacidad <= 0) {
                    throw new RuntimeException("El formato {$formato} no tiene configuración de capacidad de pallet.");
                }

                $restanteGrupo = (int) $items->sum('cantidad_pendiente');
                $posicion = 0;

                while ($restanteGrupo > 0) {
                    $cantidadPallet = min($capacidad, $restanteGrupo);
                    $itemsPallet = [];
                    $restantePallet = $cantidadPallet;

                    while ($restantePallet > 0 && $posicion < $items->count()) {
                        $detalle = $items->values()->get($posicion);
                        $disponibleDetalle = (int) $detalle['cantidad_pendiente'];

                        if ($disponibleDetalle <= 0) {
                            $posicion++;
                            continue;
                        }

                        $tomar = min($disponibleDetalle, $restantePallet);
                        $itemsPallet[] = [
                            'detalle_id' => $detalle['id'],
                            'cantidad' => $tomar,
                        ];

                        $items->values()->get($posicion)['cantidad_pendiente'] = $disponibleDetalle - $tomar;
                        $restantePallet -= $tomar;
                        $restanteGrupo -= $tomar;

                        if ($tomar === $disponibleDetalle) {
                            $posicion++;
                        } else {
                            // El mismo detalle puede continuar en el siguiente pallet.
                            break;
                        }
                    }

                    if ($restantePallet > 0) {
                        throw new RuntimeException('No fue posible distribuir toda la cantidad pendiente en pallets.');
                    }

                    $definiciones[] = [
                        'formato' => $formato,
                        'capacidad' => $capacidad,
                        'cantidad_total' => $cantidadPallet,
                        'items' => $itemsPallet,
                    ];
                }
            }

            $almacen = $entrega->almacen;

            if (!$almacen) {
                throw new RuntimeException('La entrega no tiene un almacén válido para generar los números de pallet.');
            }

            $numeros = app(PalletCorrelativoService::class)->generarSiguientes(
                $almacen,
                count($definiciones)
            );

            $creados = [];

            foreach ($definiciones as $index => $definicion) {
                $hu = WmsHu::create([
                    'numero' => $numeros[$index],
                    'entrega_id' => $entrega->id,
                    'almacen_id' => $entrega->almacen_id,
                    'ubicacion_id' => null,
                    'formato' => $definicion['formato'],
                    'capacidad_estandar' => $definicion['capacidad'],
                    'cantidad_total' => $definicion['cantidad_total'],
                    'tipo' => $definicion['cantidad_total'] === $definicion['capacidad'] ? 'COMPLETO' : 'SALDO',
                    'estado' => 'PALETIZADO',
                    'created_id' => $userId,
                    'update_id' => $userId,
                ]);

                foreach ($definicion['items'] as $item) {
                    $detalle = $disponibles->get((int) $item['detalle_id']);
                    $cantidad = (int) $item['cantidad'];

                    if (!$detalle || $cantidad <= 0) {
                        throw new RuntimeException('Existe un detalle inválido al generar los HUs.');
                    }

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

                    $detalleModelo = $detalles->firstWhere('id', $detalle['id']);
                    if ($detalleModelo) {
                        $detalleModelo->increment('cantidad_paletizada', $cantidad);
                    }
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
