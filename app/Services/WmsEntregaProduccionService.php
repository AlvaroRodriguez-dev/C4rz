<?php

namespace App\Services;

use App\Models\WmsAlmacen;
use App\Models\WmsEntregaDetalle;
use App\Models\WmsEntregaProduccion;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class WmsEntregaProduccionService
{
    private const TIPO_DOCUMENTO_PRODUCCION = 10;
    private const PROCODIGO_PRODUCCION = 'IP01';

    public function crearDesdeSas(
        WmsAlmacen $almacen,
        string $rdocum
    ): WmsEntregaProduccion {
        $rdocum = trim($rdocum);

        if ($rdocum === '') {
            throw new RuntimeException('El documento SAS de origen es obligatorio.');
        }

        return DB::transaction(function () use ($almacen, $rdocum) {
            $existente = WmsEntregaProduccion::query()
                ->where('rdocum_sas', $rdocum)
                ->first();

            if ($existente) {
                throw new RuntimeException(
                    "El documento SAS {$rdocum} ya fue registrado en WMS."
                );
            }

            $fuente = app(WmsProduccionFuenteService::class);
            $origen = $fuente->detalleDocumento($almacen, $rdocum);

            $cabecera = $origen['cabecera'] ?? null;
            $detalles = $origen['detalles'] ?? collect();

            if (!$cabecera) {
                throw new RuntimeException(
                    "No se encontró el documento SAS {$rdocum} para el almacén {$almacen->codigo}."
                );
            }

            if ((string) $cabecera->PROCODIGO !== self::PROCODIGO_PRODUCCION) {
                throw new RuntimeException(
                    "El documento SAS {$rdocum} no corresponde a una liberación de producción."
                );
            }

            if ((int) $cabecera->AGECODIGO !== (int) $almacen->codigo) {
                throw new RuntimeException(
                    "El documento SAS {$rdocum} no corresponde al almacén {$almacen->codigo}."
                );
            }

            if ($detalles->isEmpty()) {
                throw new RuntimeException(
                    "El documento SAS {$rdocum} no tiene detalles para registrar en WMS."
                );
            }

            foreach ($detalles as $detalle) {
                if (trim((string) $detalle->CODIGO) === '') {
                    throw new RuntimeException(
                        "El documento SAS {$rdocum} contiene una línea sin código de producto."
                    );
                }

                if (trim((string) $detalle->CLOTE) === '') {
                    throw new RuntimeException(
                        "El documento SAS {$rdocum} contiene una línea sin lote."
                    );
                }

                if ((int) $detalle->RCANTIDAD < 0) {
                    throw new RuntimeException(
                        "El documento SAS {$rdocum} contiene una cantidad negativa."
                    );
                }
            }

            $usuarioId = auth()->id() ?? 0;
            $fechaEntrega = $cabecera->RFECHA;

            $documento = app(WmsDocumentoService::class)->generar(
                $almacen,
                self::TIPO_DOCUMENTO_PRODUCCION,
                \Illuminate\Support\Carbon::parse($fechaEntrega)
            );

            $totalDeclarado = (int) $detalles->sum(
                fn ($detalle) => (int) $detalle->RCANTIDAD
            );

            $entrega = WmsEntregaProduccion::create([
                'documento_id' => $documento->id,
                'folio_fisico' => null,
                'almacen_id' => $almacen->id,
                'fecha_entrega' => $fechaEntrega,
                'fecha_recepcion' => null,
                'origen' => $cabecera->RNOMBRE,
                'turno_hora' => null,
                'total_declarado' => $totalDeclarado,
                'total_fisico' => 0,
                'estado' => 'PENDIENTE_VERIFICACION',
                'rdocum_sas' => $cabecera->RDOCUM,
                'posteado_erp_at' => null,
                'observaciones' => null,
                'error_integracion' => null,
                'created_id' => $usuarioId ?: null,
                'update_id' => $usuarioId ?: null,
            ]);

            $orden = 1;

            foreach ($detalles as $detalle) {
                WmsEntregaDetalle::create([
                    'entrega_id' => $entrega->id,
                    'orden' => $orden++,
                    'codigo' => trim((string) $detalle->CODIGO),
                    'descripcion' => $detalle->DESCRIP !== null
                        ? trim((string) $detalle->DESCRIP)
                        : null,
                    'descripcion2' => null,
                    'calidad' => $detalle->DESCRIP1 !== null
                        ? trim((string) $detalle->DESCRIP1)
                        : null,
                    'modelo' => null,
                    'formato' => null,
                    'lote' => trim((string) $detalle->CLOTE),
                    'cantidad_declarada' => (int) $detalle->RCANTIDAD,
                    'cantidad_paletizada' => 0,
                    'tono' => null,
                    'calibre' => null,
                    'estado' => 'PENDIENTE',
                    'observacion' => null,
                ]);
            }

            return $entrega->load(['documento.tipo', 'almacen', 'detalles']);
        });
    }
}
