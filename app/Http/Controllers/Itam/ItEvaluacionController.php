<?php

namespace App\Http\Controllers\Itam;

use App\Http\Controllers\Controller;
use App\Models\ItEvaluacion;
use App\Models\ItSolicitud;
use App\Services\ItEvaluacionService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ItEvaluacionController extends Controller
{
    public function create(ItSolicitud $solicitud)
    {
        $solicitud->load(['detalles.tipoActivo', 'ubicacion']);

        if (!in_array($solicitud->estado, ['PENDIENTE', 'EN_EVALUACION'], true)) {
            return redirect()
                ->route('itam.solicitudes.show', $solicitud)
                ->with('error', 'La solicitud no está disponible para evaluación técnica.');
        }

        $evaluacionActual = ItEvaluacion::with(['detalles.solicitudDetalle', 'evaluador'])
            ->where('solicitud_id', $solicitud->id)
            ->latest('id')
            ->first();

        return view('itam.evaluaciones.create', compact('solicitud', 'evaluacionActual'));
    }

    public function store(
        Request $request,
        ItSolicitud $solicitud,
        ItEvaluacionService $service
    ) {
        $data = $request->validate([
            'justificacion' => ['nullable', 'string'],
            'observaciones' => ['nullable', 'string'],
            'detalles' => ['required', 'array', 'min:1'],
            'detalles.*.solicitud_detalle_id' => [
                'required',
                'integer',
                Rule::exists('it_solicitud_detalles', 'id')->where(
                    fn ($query) => $query->where('solicitud_id', $solicitud->id)
                ),
            ],
            'detalles.*.resultado' => [
                'required',
                Rule::in(ItEvaluacionService::RESULTADOS),
            ],
            'detalles.*.cantidad' => ['required', 'integer', 'min:1'],
            'detalles.*.observaciones' => ['nullable', 'string'],
        ]);

        $evaluacion = $service->registrar($solicitud, $data);

        return redirect()
            ->route('itam.solicitudes.show', $solicitud)
            ->with('success', "Evaluación técnica #{$evaluacion->id} registrada correctamente.");
    }
}
