<?php

namespace App\Services;

use App\Models\ItEvaluacion;
use App\Models\ItSolicitud;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ItEvaluacionService
{
    public const RESULTADOS = [
        'REASIGNACION',
        'REPARACION',
        'STOCK',
        'COMPRA',
        'MEJORA',
        'REEMPLAZO',
        'OTRO',
    ];

    public function registrar(ItSolicitud $solicitud, array $data): ItEvaluacion
    {
        return DB::transaction(function () use ($solicitud, $data) {
            if (!in_array($solicitud->estado, ['PENDIENTE', 'EN_EVALUACION'], true)) {
                throw ValidationException::withMessages([
                    'solicitud' => 'La solicitud no está disponible para una nueva evaluación técnica.',
                ]);
            }

            $detallesSolicitud = $solicitud->detalles()->get()->keyBy('id');
            $detallesEvaluacion = collect($data['detalles'] ?? []);

            if ($detallesSolicitud->isEmpty()) {
                throw ValidationException::withMessages([
                    'detalles' => 'La solicitud no tiene requerimientos para evaluar.',
                ]);
            }

            if ($detallesEvaluacion->isEmpty()) {
                throw ValidationException::withMessages([
                    'detalles' => 'Debe registrar al menos una decisión técnica.',
                ]);
            }

            $cantidadesPorDetalle = [];

            foreach ($detallesEvaluacion as $detalle) {
                $solicitudDetalleId = (int) ($detalle['solicitud_detalle_id'] ?? 0);
                $solicitudDetalle = $detallesSolicitud->get($solicitudDetalleId);

                if (!$solicitudDetalle) {
                    throw ValidationException::withMessages([
                        'detalles' => 'Uno de los requerimientos seleccionados no pertenece a la solicitud.',
                    ]);
                }

                $resultado = $detalle['resultado'] ?? '';
                if (!in_array($resultado, self::RESULTADOS, true)) {
                    throw ValidationException::withMessages([
                        'detalles' => 'Existe un resultado de evaluación no válido.',
                    ]);
                }

                $cantidad = (int) ($detalle['cantidad'] ?? 0);
                if ($cantidad < 1) {
                    throw ValidationException::withMessages([
                        'detalles' => "La cantidad evaluada para {$solicitudDetalle->descripcion_solicitada} debe ser mayor a cero.",
                    ]);
                }

                $cantidadesPorDetalle[$solicitudDetalleId] =
                    ($cantidadesPorDetalle[$solicitudDetalleId] ?? 0) + $cantidad;
            }

            foreach ($detallesSolicitud as $solicitudDetalleId => $solicitudDetalle) {
                $evaluada = $cantidadesPorDetalle[$solicitudDetalleId] ?? 0;
                $solicitada = (int) $solicitudDetalle->cantidad;

                if ($evaluada !== $solicitada) {
                    throw ValidationException::withMessages([
                        'detalles' => "El requerimiento '{$solicitudDetalle->descripcion_solicitada}' debe quedar completamente resuelto: solicitada {$solicitada}, evaluada {$evaluada}. Puede dividir la cantidad entre diferentes resultados.",
                    ]);
                }
            }

            $resultados = $detallesEvaluacion
                ->pluck('resultado')
                ->unique()
                ->values();

            $resultadoGeneral = $resultados->count() === 1
                ? $resultados->first()
                : 'MIXTA';

            $evaluacion = ItEvaluacion::create([
                'solicitud_id' => $solicitud->id,
                'evaluador_id' => auth()->id(),
                'fecha_evaluacion' => now(),
                'resultado' => $resultadoGeneral,
                'justificacion' => $data['justificacion'] ?? null,
                'observaciones' => $data['observaciones'] ?? null,
            ]);

            foreach ($detallesEvaluacion as $detalle) {
                $evaluacion->detalles()->create([
                    'solicitud_detalle_id' => $detalle['solicitud_detalle_id'],
                    'resultado' => $detalle['resultado'],
                    'cantidad' => (int) $detalle['cantidad'],
                    'observaciones' => $detalle['observaciones'] ?? null,
                ]);
            }

            $solicitud->update([
                'estado' => 'EN_EVALUACION',
                'responsable_id' => auth()->id(),
            ]);

            return $evaluacion->load(['detalles.solicitudDetalle', 'evaluador']);
        });
    }
}
