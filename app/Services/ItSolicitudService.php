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

                $solicitud->detalles()->create([
                    'tipo_item' => $detalle['tipo_item'],
                    'tipo_activo_id' => $detalle['tipo_activo_id'] ?? null,
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
        $ultimo = ItSolicitud::where('numero', 'like', "SOL-TI-{$anio}-%")
            ->orderByDesc('id')
            ->lockForUpdate()
            ->value('numero');

        $correlativo = $ultimo ? ((int) substr($ultimo, -6)) + 1 : 1;

        return sprintf('SOL-TI-%d-%06d', $anio, $correlativo);
    }
}
