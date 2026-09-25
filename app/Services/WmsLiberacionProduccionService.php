<?php

namespace App\Services;

use App\Models\WmsEntregaDetalle;
use App\Models\WmsEntregaProduccion;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class WmsLiberacionProduccionService
{
    public function __construct(
        private WmsContextService $context,
    ) {
    }

    public function crear(array $data): WmsEntregaProduccion
    {
        return DB::transaction(function () use ($data) {
            /*
             * El almacén operativo se determina exclusivamente
             * por el usuario autenticado. No se acepta desde el frontend.
             */
            $almacen = $this->context->almacen();

            $fecha = Carbon::parse($data['fecha_entrega']);

            $usuarioId = auth()->id() ?: null;

            /*
             * RG-CB-36 es el registro operativo inicial del WMS.
             * El documento SAS/ERP se generará en una etapa posterior,
             * después de conciliación, paletización y ubicación.
             */
            $entrega = WmsEntregaProduccion::create([
                'documento_id' => null,
                'folio_fisico' => $data['folio_fisico'] ?? null,
                'almacen_id' => $almacen->id,
                'planta' => $data['planta'],
                'formato' => $data['formato'],
                'fecha_entrega' => $fecha->toDateString(),
                'fecha_recepcion' => null,
                'origen' => $data['planta'],
                'turno_hora' => $data['turno_hora'] ?? null,
                'total_declarado' => 0,
                'total_fisico' => 0,
                'estado' => 'PENDIENTE_VERIFICACION',
                'rdocum_sas' => null,
                'posteado_erp_at' => null,
                'observaciones' => $data['observaciones'] ?? null,
                'error_integracion' => null,
                'created_id' => $usuarioId,
                'update_id' => $usuarioId,
            ]);

            $total = 0;
            $orden = 1;

            foreach ($data['lineas'] as $linea) {
                $codigo = strtoupper(trim($linea['codigo']));
                $calidad = strtoupper(trim($linea['calidad']));
                $cantidad = (int) $linea['cantidad'];

                $calidades = [
                    '1' => 'EXTRA',
                    '2' => 'COMERCIAL',
                    '3' => 'ECONOMICO',
                ];

                if (($calidades[substr($codigo, 4, 1)] ?? null) !== $calidad) {
                    throw new RuntimeException(
                        'El producto ' . $codigo . ' no corresponde a la calidad ' . $calidad . '.'
                    );
                }

                $formatoCodigo = substr($codigo, 5, 4);

                if (strtoupper($formatoCodigo) !== strtoupper($data['formato'])) {
                    throw new RuntimeException(
                        'El producto ' . $codigo . ' no corresponde al formato ' . $data['formato'] . '.'
                    );
                }

                $lote = $this->generarLote(
                    $fecha,
                    $calidad,
                    $linea['tono'] ?? null,
                    $linea['calibre'] ?? null
                );

                WmsEntregaDetalle::create([
                    'entrega_id' => $entrega->id,
                    'orden' => $orden++,
                    'codigo' => $codigo,
                    'descripcion' => $linea['descripcion'] ?? null,
                    'descripcion2' => null,
                    'calidad' => $calidad,
                    'modelo' => $linea['modelo'] ?? substr($codigo, -4),
                    'formato' => $formatoCodigo,
                    'lote' => $lote,
                    'cantidad_declarada' => $cantidad,
                    'cantidad_fisica' => null,
                    'cantidad_paletizada' => 0,
                    'tono' => $calidad === 'EXTRA' ? ($linea['tono'] ?? null) : null,
                    'calibre' => $calidad === 'EXTRA' ? ($linea['calibre'] ?? null) : null,
                    'estado' => 'PENDIENTE',
                    'observacion' => null,
                ]);

                $total += $cantidad;
            }

            $entrega->update([
                'total_declarado' => $total,
            ]);

            return $entrega->fresh([
                'almacen',
                'detalles',
            ]);
        });
    }

    private function generarLote(
        Carbon $fecha,
        string $calidad,
        ?string $tono,
        ?string $calibre
    ): string {
        $fechaCodigo = $fecha->format('ymd');

        if ($calidad !== 'EXTRA') {
            return $fechaCodigo . '-NNNNN';
        }

        if ($tono === null || $calibre === null) {
            throw new RuntimeException(
                'Las líneas de calidad EXTRA requieren tono y calibre.'
            );
        }

        return sprintf(
            '%s-%03d%02d',
            $fechaCodigo,
            (int) $tono,
            (int) $calibre
        );
    }
}
