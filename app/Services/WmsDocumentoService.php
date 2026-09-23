<?php

namespace App\Services;

use App\Models\WmsAlmacen;
use App\Models\WmsDocumento;
use App\Models\WmsTipoDocumento;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class WmsDocumentoService
{
    public function generar(
        WmsAlmacen $almacen,
        int $tipoDocumentoId,
        ?Carbon $fecha = null
    ): WmsDocumento {
        return DB::transaction(function () use ($almacen, $tipoDocumentoId, $fecha) {
            $fecha ??= now();

            $tipo = WmsTipoDocumento::query()
                ->whereKey($tipoDocumentoId)
                ->whereNull('deleted_at')
                ->first();

            if (!$tipo) {
                throw new RuntimeException(
                    "El tipo de documento WMS {$tipoDocumentoId} no existe o está eliminado."
                );
            }

            $prefijo = trim((string) $almacen->prefijo_documento);
            $talonario = trim((string) $tipo->talonario);

            if ($prefijo === '') {
                throw new RuntimeException(
                    "El almacén {$almacen->codigo} no tiene prefijo de documento configurado."
                );
            }

            if ($talonario === '') {
                throw new RuntimeException(
                    "El tipo de documento {$tipo->id} no tiene talonario configurado."
                );
            }

            $anio = (int) $fecha->format('Y');
            $mes = (int) $fecha->format('m');

            DB::table('wms_documento_correlativos')->updateOrInsert(
                [
                    'almacen_id' => $almacen->id,
                    'id_tipo_registro' => $tipo->id,
                    'talonario' => $talonario,
                    'anio' => $anio,
                    'mes' => $mes,
                ],
                [
                    'updated_at' => now(),
                ]
            );

            $correlativo = DB::table('wms_documento_correlativos')
                ->where('almacen_id', $almacen->id)
                ->where('id_tipo_registro', $tipo->id)
                ->where('talonario', $talonario)
                ->where('anio', $anio)
                ->where('mes', $mes)
                ->lockForUpdate()
                ->first();

            if (!$correlativo) {
                throw new RuntimeException('No fue posible inicializar el correlativo del documento WMS.');
            }

            $siguiente = ((int) $correlativo->ultimo_correlativo) + 1;

            DB::table('wms_documento_correlativos')
                ->where('id', $correlativo->id)
                ->update([
                    'ultimo_correlativo' => $siguiente,
                    'updated_at' => now(),
                ]);

            $codigo = sprintf(
                '%s%s%s%s%02d%03d',
                $prefijo,
                $talonario,
                $tipo->codigo,
                $fecha->format('Y'),
                $mes,
                $siguiente
            );

            $usuarioId = auth()->id() ?? 0;

            return WmsDocumento::create([
                'id_tipo_registro' => $tipo->id,
                'almacen_id' => $almacen->id,
                'id_documento' => $codigo,
                'agencia' => (string) $almacen->codigo,
                'agecodigo' => (string) $almacen->codigo,
                'puntoVenta' => null,
                'recibo' => 0,
                'tipo_precio' => 0,
                'titulo' => $tipo->descripcion,
                'inicial' => $prefijo,
                'database' => DB::getDatabaseName(),
                'codigoSucursal' => is_numeric($almacen->codigo) ? (int) $almacen->codigo : 0,
                'codigoPuntoVenta' => 0,
                'created_id' => $usuarioId,
                'updated_id' => $usuarioId,
                'deleted_id' => 0,
                'tipo_documento' => $tipo->descripcion,
            ]);
        });
    }
}
