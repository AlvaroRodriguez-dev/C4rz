<?php

namespace App\Services;

use App\Models\ItSolicitud;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ItSolicitudService
{
    public function crear(array $data): ItSolicitud
    {
        return DB::transaction(function () use ($data) {
            $detalles = $data['detalles'];
            unset($data['detalles']);

            $data['numero'] = $this->siguienteNumero((int) now()->format('Y'));
            $data['estado'] = 'PENDIENTE';
            $data['fecha_solicitud'] ??= now()->toDateString();

            $solicitud = ItSolicitud::create($data);

            foreach ($detalles as $detalle) {
                $cantidad = (int) ($detalle['cantidad'] ?? 0);
                if ($cantidad < 1) {
                    throw new RuntimeException('La cantidad de cada detalle debe ser mayor a cero.');
                }

                if (($detalle['tipo_item'] ?? null) !== 'ACTIVO' || empty($detalle['tipo_activo_id'])
                    || !DB::table('it_tipos_activo')->where('id', $detalle['tipo_activo_id'])->whereNull('deleted_at')
                        ->whereNotIn('codigo', ['COMPONENTE', 'REPUESTO', 'ACCESORIO'])->exists()) {
                    throw new RuntimeException('ITAM en esta fase solo admite requerimientos de activos tecnológicos con tipo de activo definido.');
                }

                $solicitud->detalles()->create([
                    'tipo_item' => 'ACTIVO',
                    'tipo_activo_id' => $detalle['tipo_activo_id'],
                    'descripcion_solicitada' => $detalle['descripcion_solicitada'],
                    'cantidad' => $cantidad,
                    'especificaciones' => $detalle['especificaciones'] ?? null,
                    'unidad' => $detalle['unidad'] ?? 'UNIDAD',
                    'estado' => 'PENDIENTE',
                    'cantidad_aprobada' => null,
                    'cantidad_comprada' => 0,
                    'cantidad_recibida' => 0,
                    'cantidad_identificada' => 0,
                    'observaciones' => $detalle['observaciones'] ?? null,
                ]);
            }

            return $solicitud->load('detalles', 'ubicacion');
        });
    }

    private function siguienteNumero(int $anio): string
    {
        $ahora = now();

        DB::table('it_correlativos')->insertOrIgnore([
            'tipo' => 'SOL-TI',
            'anio' => $anio,
            'ultimo_numero' => 0,
            'created_at' => $ahora,
            'updated_at' => $ahora,
        ]);

        $contador = DB::table('it_correlativos')
            ->where('tipo', 'SOL-TI')
            ->where('anio', $anio)
            ->lockForUpdate()
            ->first();

        if (!$contador) {
            throw new RuntimeException('No fue posible reservar el correlativo de la solicitud ITAM.');
        }

        $siguiente = (int) $contador->ultimo_numero + 1;

        DB::table('it_correlativos')
            ->where('id', $contador->id)
            ->update(['ultimo_numero' => $siguiente, 'updated_at' => $ahora]);

        return sprintf('SOL-TI-%d-%06d', $anio, $siguiente);
    }
}
