<?php

namespace App\Services;

use App\Models\WmsEntregaDetalle;
use App\Models\WmsEntregaProduccion;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class WmsLiberacionProduccionService
{
    private const TIPO_DOCUMENTO_LIBERACION_PRODUCCION = 10;

    public function __construct(
        private WmsContextService $context,
        private WmsDocumentoService $documentos,
    ) {
    }

    public function crear(array $data): WmsEntregaProduccion
    {
        return DB::transaction(function () use ($data) {
            $almacen = $this->context->almacen();
            $fecha = Carbon::parse($data['fecha_entrega']);
            $usuarioId = auth()->id() ?: null;

            $codigos = collect($data['lineas'])
                ->pluck('codigo')
                ->map(fn ($codigo) => strtoupper(trim((string) $codigo)))
                ->unique()
                ->values();

            $stocks = DB::connection('sisinvconsolidado2026')
                ->table('stock')
                ->whereIn('CODIGO', $codigos->all())
                ->select('CODIGO', 'DESCRIP', 'DESCRIP1')
                ->get()
                ->keyBy(fn ($stock) => strtoupper(trim($stock->CODIGO)));

            if ($stocks->count() !== $codigos->count()) {
                $faltantes = $codigos
                    ->reject(fn ($codigo) => $stocks->has($codigo))
                    ->implode(', ');

                throw new RuntimeException(
                    'Uno o más productos no existen en el maestro de stock: ' . $faltantes
                );
            }

            // El documento WMS se genera al emitir el RG-CB-36.
            // La nota/rdocum oficial del SAS se generará posteriormente,
            // después de conciliación, paletización y ubicación.
            $documento = $this->documentos->generar(
                $almacen,
                self::TIPO_DOCUMENTO_LIBERACION_PRODUCCION,
                $fecha
            );

            $entrega = WmsEntregaProduccion::create([
                // Seguimiento documental: se almacena el código WMS (ej. A010202609004),
                // no el id interno autoincremental de wms_documentos.
                'documento_id' => $documento->id_documento,
                'folio_fisico' => $data['folio_fisico'] ?? null,
                'almacen_id' => $almacen->id,
                'planta' => $almacen->nombre,
                'formato' => $data['formato'],
                'fecha_entrega' => $fecha->toDateString(),
                'fecha_recepcion' => null,
                'origen' => 'RG-CB-36',
                'turno_hora' => null,
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
                $stock = $stocks->get($codigo);

                $calidades = [
                    '1' => 'EXTRA',
                    '2' => 'COMERCIAL',
                    '3' => 'ECONOMICO',
                    'X' => 'OTRO',
                ];
                $calidad = $calidades[strtoupper(substr($codigo, 4, 1))] ?? 'OTRO';
                $cantidad = (int) $linea['cantidad'];

                // El carácter 5 determina la calidad y los 4 siguientes (6-9)
                // determinan el formato del producto.
                $formatoCodigo = substr($codigo, 5, 4);

                if (strtoupper($formatoCodigo) !== strtoupper($data['formato'])) {
                    throw new RuntimeException(
                        'El producto ' . $codigo . ' no corresponde al formato ' . $data['formato'] . '.'
                    );
                }

                $tono = $linea['tono'] ?? null;
                $calibre = $linea['calibre'] ?? null;

                $lote = $this->generarLote(
                    $fecha,
                    $calidad,
                    $tono,
                    $calibre
                );

                WmsEntregaDetalle::create([
                    'entrega_id' => $entrega->id,
                    'orden' => $orden++,
                    'codigo' => $codigo,
                    'descripcion' => trim((string) $stock->DESCRIP . ' ' . (string) $stock->DESCRIP1),
                    'descripcion2' => null,
                    'calidad' => $calidad,
                    'modelo' => substr($codigo, -4),
                    'formato' => $formatoCodigo,
                    'lote' => $lote,
                    'cantidad_declarada' => $cantidad,
                    'cantidad_fisica' => null,
                    'cantidad_paletizada' => 0,
                    'tono' => $calidad === 'EXTRA' ? $tono : null,
                    'calibre' => $calidad === 'EXTRA' ? $calibre : null,
                    'estado' => 'PENDIENTE',
                    'observacion' => null,
                ]);

                $total += $cantidad;
            }

            $entrega->update([
                'total_declarado' => $total,
            ]);

            return $entrega->fresh([
                'documento',
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
