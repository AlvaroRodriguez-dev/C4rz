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

    public function calcularPallets(WmsEntregaProduccion $entrega): array
    {
        if (!in_array($entrega->estado, ['PENDIENTE_PALLET', 'CONCILIADA', 'CON_DIFERENCIA'], true)) {
            throw new RuntimeException('Solo se puede calcular la paletización de una entrega pendiente de pallet, conciliada o con diferencia.');
        }

        $entrega->loadMissing('detalles');
        $usarDeclarado = $entrega->estado === 'PENDIENTE_PALLET';
        $disponibles = collect($this->construirDisponible($entrega->detalles, $usarDeclarado));

        return $this->construirDefiniciones(
            $disponibles->filter(fn (array $detalle) => (int) $detalle['cantidad_pendiente'] > 0)->values()
        );
    }

    /**
     * Genera automaticamente los pallets.
     *
     * Para una liberación nueva se utiliza la cantidad declarada.
     * El flujo anterior de paletización sigue utilizando la cantidad física
     * conciliada, manteniendo compatibilidad con los registros existentes.
     *
     * Reglas:
     * - no se mezclan formatos;
     * - dentro del mismo formato se pueden mezclar productos y lotes;
     * - se respeta la capacidad configurada por formato;
     * - el remanente se registra como SALDO.
     */
    public function guardar(WmsEntregaProduccion $entrega, array $pallets, int $userId): array
    {
        if (!in_array($entrega->estado, ['PENDIENTE_PALLET', 'CONCILIADA', 'CON_DIFERENCIA'], true)) {
            throw new RuntimeException('Solo se puede generar pallets de una entrega pendiente de pallet, conciliada o con diferencia.');
        }

        return DB::transaction(function () use ($entrega, $userId) {
            $detalles = WmsEntregaDetalle::query()
                ->where('entrega_id', $entrega->id)
                ->lockForUpdate()
                ->get();

            $usarDeclarado = $entrega->estado === 'PENDIENTE_PALLET';
            $disponibles = collect($this->construirDisponible($detalles, $usarDeclarado))->keyBy('id');

            $pendientes = $disponibles
                ->filter(fn (array $detalle) => (int) $detalle['cantidad_pendiente'] > 0)
                ->values();

            if ($pendientes->isEmpty()) {
                throw new RuntimeException('La entrega no tiene cantidades pendientes de paletizar.');
            }

            $definiciones = $this->construirDefiniciones($pendientes);
            $almacen = $entrega->almacen;

            if (!$almacen) {
                throw new RuntimeException('La entrega no tiene un almacén válido para generar los números de pallet.');
            }

            $numeros = app(PalletCorrelativoService::class)->generarSiguientes($almacen, count($definiciones));
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

            $pendienteTotal = $detalles->sum(function (WmsEntregaDetalle $detalle) use ($usarDeclarado) {
                $base = $usarDeclarado ? (int) $detalle->cantidad_declarada : (int) $detalle->cantidad_fisica;
                return max($base - (int) $detalle->cantidad_paletizada, 0);
            });

            if ($pendienteTotal === 0) {
                $entrega->update([
                    'estado' => $usarDeclarado ? 'PENDIENTE_VERIFICACION' : 'PALETIZADA',
                    'update_id' => $userId,
                ]);
            }

            return $creados;
        });
    }


    /**
     * Reconstruye la composición de una liberación corregida antes del ingreso al ERP.
     * Conserva el número de los HUs cuya composición física no cambió; los demás se
     * marcan como sustituidos y reciben nuevos números correlativos.
     */
    public function reconstruirDespuesCorreccion(WmsEntregaProduccion $entrega, int $userId): array
    {
        return DB::transaction(function () use ($entrega, $userId) {
            $entrega = WmsEntregaProduccion::query()->lockForUpdate()->findOrFail($entrega->id);

            if (!in_array($entrega->estado, ['OBSERVADA', 'PENDIENTE_CORRECCION'], true)) {
                throw new RuntimeException('La liberación no está en un estado que permita corregir pallets.');
            }

            if ($entrega->posteado_erp_at !== null) {
                throw new RuntimeException('No se puede reconstruir una liberación ya ingresada al SAS-ERP.');
            }

            $activos = WmsHu::query()
                ->where('entrega_id', $entrega->id)
                ->where('estado', '!=', 'SUSTITUIDO')
                ->with('detalles')
                ->lockForUpdate()
                ->get();

            if ($activos->contains(fn (WmsHu $hu) => $hu->ubicacion_id !== null || !in_array($hu->estado, ['PALETIZADO', 'PENDIENTE_VERIFICACION'], true))) {
                throw new RuntimeException('La liberación tiene pallets ubicados o con movimientos; no se puede reconstruir automáticamente.');
            }

            $detalles = WmsEntregaDetalle::query()
                ->where('entrega_id', $entrega->id)
                ->orderBy('orden')
                ->lockForUpdate()
                ->get();

            $disponibles = [];
            foreach ($detalles as $detalle) {
                $cantidad = (int) $detalle->cantidad_declarada;
                if ($cantidad <= 0) {
                    continue;
                }

                $formato = $detalle->formato ?: strtoupper(substr($detalle->codigo, 5, 4));
                $config = WmsConfigPallet::find($formato);
                if (!$config || (int) $config->cajas_x_pallet <= 0) {
                    throw new RuntimeException("El formato {$formato} no tiene capacidad de pallet configurada.");
                }

                $disponibles[] = [
                    'id' => $detalle->id,
                    'orden' => $detalle->orden,
                    'codigo' => $detalle->codigo,
                    'descripcion' => $detalle->descripcion,
                    'descripcion2' => $detalle->descripcion2,
                    'calidad' => $detalle->calidad,
                    'formato' => $formato,
                    'lote' => $detalle->lote,
                    'cantidad_fisica' => (int) $detalle->cantidad_fisica,
                    'cantidad_declarada' => $cantidad,
                    'cantidad_paletizada' => 0,
                    'cantidad_pendiente' => $cantidad,
                    'tono' => $detalle->tono,
                    'calibre' => $detalle->calibre,
                    'capacidad' => (int) $config->cajas_x_pallet,
                    'configurado' => true,
                ];
            }

            if (empty($disponibles)) {
                throw new RuntimeException('La corrección debe dejar al menos una línea con cantidad mayor que cero.');
            }

            $mapaDetalle = collect($disponibles)->keyBy('id');
            $definiciones = $this->construirDefiniciones(collect($disponibles));
            $claveDefinicion = function (array $definicion) use ($mapaDetalle): string {
                $items = collect($definicion['items'])->map(function (array $item) use ($mapaDetalle) {
                    $d = $mapaDetalle->get((int) $item['detalle_id']);
                    return [
                        'detalle_id' => (int) $item['detalle_id'],
                        'codigo' => $d['codigo'],
                        'lote' => $d['lote'],
                        'calidad' => $d['calidad'],
                        'formato' => $d['formato'],
                        'cantidad' => (int) $item['cantidad'],
                    ];
                })->sortBy(fn ($item) => implode('|', $item))->values()->all();

                return sha1(json_encode(['formato' => $definicion['formato'], 'items' => $items]));
            };
            $claveHu = function (WmsHu $hu): string {
                $items = $hu->detalles->map(fn (WmsHuDetalle $d) => [
                    'detalle_id' => (int) $d->entrega_detalle_id,
                    'codigo' => $d->codigo,
                    'lote' => $d->lote,
                    'calidad' => $d->calidad,
                    'formato' => $d->formato,
                    'cantidad' => (int) $d->cantidad,
                ])->sortBy(fn ($item) => implode('|', $item))->values()->all();

                return sha1(json_encode(['formato' => $hu->formato, 'items' => $items]));
            };

            $huPorClave = [];
            foreach ($activos as $hu) {
                $huPorClave[$claveHu($hu)][] = $hu;
            }

            $conservarIds = [];
            $nuevasDefiniciones = [];
            foreach ($definiciones as $definicion) {
                $clave = $claveDefinicion($definicion);
                if (!empty($huPorClave[$clave])) {
                    $existente = array_shift($huPorClave[$clave]);
                    $conservarIds[] = $existente->id;
                } else {
                    $nuevasDefiniciones[] = $definicion;
                }
            }

            $numeros = empty($nuevasDefiniciones)
                ? []
                : app(PalletCorrelativoService::class)->generarSiguientes($entrega->almacen, count($nuevasDefiniciones));

            foreach ($activos as $hu) {
                if (!in_array($hu->id, $conservarIds, true)) {
                    $hu->update(['estado' => 'SUSTITUIDO', 'update_id' => $userId]);
                }
            }

            foreach ($nuevasDefiniciones as $index => $definicion) {
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
                    $d = $mapaDetalle->get((int) $item['detalle_id']);
                    WmsHuDetalle::create([
                        'hu_id' => $hu->id,
                        'entrega_detalle_id' => $d['id'],
                        'codigo' => $d['codigo'],
                        'lote' => $d['lote'],
                        'descripcion' => $d['descripcion'],
                        'descripcion2' => $d['descripcion2'],
                        'formato' => $d['formato'],
                        'calidad' => $d['calidad'],
                        'cantidad' => (int) $item['cantidad'],
                    ]);
                }
            }

            $totales = WmsHuDetalle::query()
                ->join('wms_hu', 'wms_hu.id', '=', 'wms_hu_detalle.hu_id')
                ->where('wms_hu.entrega_id', $entrega->id)
                ->where('wms_hu.estado', '!=', 'SUSTITUIDO')
                ->whereNotNull('wms_hu_detalle.entrega_detalle_id')
                ->select('wms_hu_detalle.entrega_detalle_id', DB::raw('SUM(wms_hu_detalle.cantidad) as total'))
                ->groupBy('wms_hu_detalle.entrega_detalle_id')
                ->pluck('total', 'entrega_detalle_id');

            foreach ($detalles as $detalle) {
                $detalle->update([
                    'cantidad_paletizada' => (int) ($totales[$detalle->id] ?? 0),
                ]);
            }

            $entrega->update([
                'total_declarado' => $detalles->sum('cantidad_declarada'),
                'estado' => 'PENDIENTE_REVERIFICACION',
                'verificado_at' => null,
                'verificado_id' => null,
                'update_id' => $userId,
            ]);

            return [
                'conservados' => count($conservarIds),
                'sustituidos' => $activos->count() - count($conservarIds),
                'nuevos' => count($nuevasDefiniciones),
            ];
        });
    }

    /**
     * Divide las cantidades pendientes en pallets completos y saldos.
     * La agrupación se hace únicamente por formato, permitiendo mezclar
     * productos y lotes distintos del mismo formato dentro de un pallet.
     */
    private function construirDefiniciones($pendientes): array
    {
        $definiciones = [];

        $grupos = $pendientes->groupBy(function (array $detalle) {
            return $detalle['formato'];
        });

        foreach ($grupos as $items) {
            $primerDetalle = $items->first();
            $formato = $primerDetalle['formato'];
            $capacidad = (int) ($primerDetalle['capacidad'] ?? 0);

            if ($capacidad <= 0) {
                throw new RuntimeException("El formato {$formato} no tiene configuración de capacidad de pallet.");
            }

            $restantes = $items->map(function (array $detalle) {
                return [
                    'detalle_id' => $detalle['id'],
                    'cantidad' => (int) $detalle['cantidad_pendiente'],
                ];
            })->values()->all();

            while (collect($restantes)->sum('cantidad') > 0) {
                $restantePallet = $capacidad;
                $itemsPallet = [];

                foreach ($restantes as &$restante) {
                    if ($restante['cantidad'] <= 0 || $restantePallet <= 0) {
                        continue;
                    }

                    $tomar = min($restante['cantidad'], $restantePallet);
                    $itemsPallet[] = [
                        'detalle_id' => $restante['detalle_id'],
                        'cantidad' => $tomar,
                    ];
                    $restante['cantidad'] -= $tomar;
                    $restantePallet -= $tomar;
                }
                unset($restante);

                if (empty($itemsPallet) || $restantePallet === $capacidad) {
                    throw new RuntimeException('No fue posible distribuir toda la cantidad pendiente en pallets.');
                }

                $cantidadTotal = $capacidad - $restantePallet;

                $definiciones[] = [
                    'formato' => $formato,
                    'capacidad' => $capacidad,
                    'cantidad_total' => $cantidadTotal,
                    'tipo' => $cantidadTotal === $capacidad ? 'COMPLETO' : 'SALDO',
                    'items' => $itemsPallet,
                    'detalle_referencia' => $primerDetalle,
                ];
            }
        }

        return $definiciones;
    }

    private function construirDisponible($detalles, bool $usarDeclarado = false): array
    {
        $ids = $detalles->pluck('id');

        $paletizado = WmsHuDetalle::query()
            ->whereIn('entrega_detalle_id', $ids)
            ->select('entrega_detalle_id', DB::raw('SUM(cantidad) as total'))
            ->groupBy('entrega_detalle_id')
            ->pluck('total', 'entrega_detalle_id');

        return $detalles->map(function (WmsEntregaDetalle $detalle) use ($paletizado, $usarDeclarado) {
            $yaPaletizado = (int) ($paletizado[$detalle->id] ?? 0);
            $base = $usarDeclarado ? (int) $detalle->cantidad_declarada : (int) $detalle->cantidad_fisica;
            $pendiente = max($base - $yaPaletizado, 0);
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
                'cantidad_fisica' => (int) $detalle->cantidad_fisica,
                'cantidad_declarada' => (int) $detalle->cantidad_declarada,
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
