<?php

namespace App\Services\Itam;

use App\Models\ItActivo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ItActivoService
{
    public function registrar(array $data): ItActivo
    {
        return DB::transaction(function () use ($data) {
            $estadoDisponibleId = DB::table('it_estados')
                ->where('codigo', 'DISPONIBLE')
                ->whereNull('deleted_at')
                ->value('id');

            if (!$estadoDisponibleId) {
                throw ValidationException::withMessages([
                    'estado_id' => 'No existe el estado DISPONIBLE. Ejecute el seeder de catálogos ITAM.',
                ]);
            }

            $recepcionDetalle = null;

            if (($data['origen_documento'] ?? null) === 'ADQUISICION') {
                $recepcionDetalle = DB::table('it_recepcion_detalles')
                    ->where('id', $data['recepcion_detalle_id'] ?? 0)
                    ->lockForUpdate()
                    ->first();

                if (!$recepcionDetalle || (int) $recepcionDetalle->cantidad_pendiente < 1) {
                    throw ValidationException::withMessages([
                        'recepcion_detalle_id' => 'La línea de recepción no tiene unidades pendientes de identificación.',
                    ]);
                }

                if ($recepcionDetalle->orden_compra_detalle_id) {
                    $tipoComprado = DB::table('it_orden_compra_detalles')
                        ->where('id', $recepcionDetalle->orden_compra_detalle_id)
                        ->value('tipo_activo_id');

                    if ($tipoComprado && (int) $tipoComprado !== (int) $data['tipo_activo_id']) {
                        throw ValidationException::withMessages([
                            'tipo_activo_id' => 'El tipo de activo no coincide con el tipo definido en la orden de compra.',
                        ]);
                    }
                }

                $recepcion = DB::table('it_recepciones')
                    ->where('id', $recepcionDetalle->recepcion_id)
                    ->first();

                $data['origen_linea'] = $recepcionDetalle->id;
                $data['costo'] = $recepcionDetalle->costo_unitario;
                $data['fecha_adquisicion'] = $recepcion?->fecha;

                if ($recepcion?->orden_compra_id) {
                    $data['proveedor_id'] = DB::table('it_ordenes_compra')
                        ->where('id', $recepcion->orden_compra_id)
                        ->value('proveedor_id');
                }

                $data['recepcion_detalle_id'] = $recepcionDetalle->id;

                DB::table('it_recepcion_detalles')
                    ->where('id', $recepcionDetalle->id)
                    ->update([
                        'cantidad_identificada' => DB::raw('cantidad_identificada + 1'),
                        'cantidad_pendiente' => DB::raw('cantidad_pendiente - 1'),
                        'updated_at' => now(),
                    ]);
            } else {
                $data['recepcion_detalle_id'] = null;
                $data['origen_linea'] = null;
            }

            $activo = ItActivo::create([
                ...$data,
                'codigo_ti' => $this->generarCodigo(),
                'estado_id' => $estadoDisponibleId,
                'custodio_id' => null,
                'usuario_asignado_id' => null,
                'fecha_alta' => now()->toDateString(),
            ]);

            return $activo->load(['tipoActivo', 'marca', 'modelo', 'estado', 'ubicacion']);
        }, 3);
    }

    private function generarCodigo(): string
    {
        do {
            $codigo = 'IT-' . now()->format('Y') . '-' . Str::upper(Str::random(8));
        } while (ItActivo::withTrashed()->where('codigo_ti', $codigo)->exists());

        return $codigo;
    }
}
