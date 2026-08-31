<?php

namespace App\Http\Controllers;

use App\Models\Biometrico;
use App\Models\BioUsuario;
use App\Models\BioAsistencia;
use App\Models\BioNovedad;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class NovedadController extends Controller
{
    // ── Formulario principal ─────────────────────────────────────────
    public function index()
    {
        $biometricos = Biometrico::orderBy('agencia')->get();

        // Todos los usuarios de bio_usuarios agrupados con su biométrico
        $usuarios = BioUsuario::with('biometrico')
            ->orderBy('name')
            ->get(['id', 'user_id', 'name', 'biometrico_id']);

        return view('biometricos.novedades', compact('biometricos', 'usuarios'));
    }

    // ── Generar reporte de novedades ─────────────────────────────────
    public function generar(Request $request)
    {
        $request->validate([
            'usuario_id'    => 'required|exists:bio_usuarios,id',
            'mes'           => 'required|integer|min:1|max:12',
            'anio'          => 'required|integer|min:2020|max:2099',
            'biometrico_id' => 'nullable|exists:biometricos,id',
        ]);

        $usuario    = BioUsuario::with('biometrico')->findOrFail($request->usuario_id);
        $mes        = (int) $request->mes;
        $anio       = (int) $request->anio;
        $diasEnMes  = Carbon::createFromDate($anio, $mes, 1)->daysInMonth;
        $fechaInicio = Carbon::createFromDate($anio, $mes, 1)->startOfDay();
        $fechaFin    = Carbon::createFromDate($anio, $mes, $diasEnMes)->endOfDay();

        // ── Registros de asistencia del mes ──────────────────────────
        $queryAsistencia = BioAsistencia::where('user_id', $usuario->user_id)
            ->whereBetween('timestamp', [$fechaInicio, $fechaFin]);

        if ($request->biometrico_id) {
            $queryAsistencia->where('biometrico_id', $request->biometrico_id);
        }

        $asistencias = $queryAsistencia->orderBy('timestamp')->get();

        // ── Agrupar marcajes por fecha ────────────────────────────────
        $marcajesPorDia = [];
        foreach ($asistencias as $a) {
            $dia = (int) date('j', strtotime($a->timestamp));
            $marcajesPorDia[$dia][] = [
                'hora'      => date('H:i:s', strtotime($a->timestamp)),
                'biometrico' => $a->biometrico->agencia ?? '—',
            ];
        }

        // ── Novedades ya registradas para este usuario/mes ───────────
        $novedades = BioNovedad::where('user_id', $usuario->user_id)
            ->whereYear('fecha', $anio)
            ->whereMonth('fecha', $mes)
            ->get()
            ->keyBy(fn($n) => (int) $n->fecha->day);

        // ── Construir días del mes ────────────────────────────────────
        $dias = [];
        for ($dia = 1; $dia <= $diasEnMes; $dia++) {
            $fecha   = Carbon::createFromDate($anio, $mes, $dia);
            $marcajes = $marcajesPorDia[$dia] ?? [];

            $dias[] = [
                'dia'         => $dia,
                'fecha'       => $fecha->format('Y-m-d'),
                'dia_semana'  => $fecha->translatedFormat('D'),
                'es_fin_semana' => $fecha->isWeekend(),
                'marcajes'    => $marcajes,
                'total'       => count($marcajes),
                'ticket_id'   => $novedades[$dia]->ticket_id ?? null,
                'novedad_id'  => $novedades[$dia]->id ?? null,
            ];
        }

        return view('biometricos.novedades_resultado', compact(
            'dias',
            'usuario',
            'mes',
            'anio',
            'request'
        ));
    }

    // ── Buscar boleta en PostgreSQL ──────────────────────────────────
    public function buscarBoleta(Request $request)
    {
        $request->validate([
            'user_id'    => 'required|string',
            'fecha'      => 'required|date',
            'biometrico_id' => 'nullable|exists:biometricos,id',
        ]);

        try {
            // Usar el esquema del año correspondiente
            $anio   = date('Y', strtotime($request->fecha));
            $schema = $anio;

            $boleta = DB::connection('pgsql_rrhh')
                ->table(DB::raw('"' . $schema . '"."rrhh_boleta1"'))
                ->where('LICENSE', $request->user_id)
                ->where('DATEI', '=', $request->fecha)

                ->whereNull('DELETED_AT')
                ->select('TICKET_ID', 'DATEI', 'DATEF', 'HOURI1', 'HOURF1', 'TOTALH', 'TOTALD')
                ->first();

            if (!$boleta) {
                return response()->json([
                    'success'   => false,
                    'message'   => 'No se encontró boleta.',
                ]);
            }

            // ── Registrar/actualizar novedad ─────────────────────────
            BioNovedad::updateOrInsert(
                [
                    'user_id' => $request->user_id,
                    'fecha'   => $request->fecha,
                ],
                [
                    'biometrico_id' => $request->biometrico_id,
                    'ticket_id'     => $boleta->TICKET_ID,
                    'updated_at'    => now(),
                    'created_at'    => now(),
                ]
            );

            return response()->json([
                'success'   => true,
                'ticket_id' => $boleta->TICKET_ID,
                'datei'     => $boleta->DATEI,
                'datef'     => $boleta->DATEF,
                'houri1'    => $boleta->HOURI1,
                'hourf1'    => $boleta->HOURF1,
                'totalh'    => $boleta->TOTALH,
                'totald'    => $boleta->TOTALD,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al consultar PostgreSQL: ' . $e->getMessage(),
            ], 500);
        }
    }

    // ── Ver detalle de boleta en modal ───────────────────────────────
    public function verBoleta(Request $request)
    {
        $request->validate([
            'ticket_id' => 'required|string',
        ]);

        try {
            $schema = env('PG_RRHH_SCHEMA', '2026');
            $db     = DB::connection('pgsql_rrhh');

            // ── Boleta principal ─────────────────────────────────────
            $boleta = $db->table(DB::raw('"' . $schema . '"."rrhh_boleta"'))
                ->where('ID', $request->ticket_id)
                ->whereNull('DELETED_AT')
                ->first();

            if (!$boleta) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró la boleta con ID: ' . $request->ticket_id,
                ]);
            }

            // ── Detalle de boleta (rrhh_boleta1) ────────────────────
            $detalle = $db->table(DB::raw('"' . $schema . '"."rrhh_boleta1"'))
                ->where('TICKET_ID', $request->ticket_id)
                ->whereNull('DELETED_AT')
                ->get();

            // ── Personal ─────────────────────────────────────────────
            $personal = $db->table(DB::raw('"' . $schema . '"."rrhh_personal"'))
                ->where('LICENSE', $boleta->LICENSE)
                ->whereNull('DELETED_AT')
                ->first();

            // ── Sección ──────────────────────────────────────────────
            $seccion = null;
            if ($personal && $personal->SECTION_ID) {
                $seccion = $db->table(DB::raw('"' . $schema . '"."rrhh_seccion"'))
                    ->where('ID', $personal->SECTION_ID)
                    ->whereNull('DELETED_AT')
                    ->first();
            }

            return response()->json([
                'success'  => true,
                'boleta'   => $boleta,
                'detalle'  => $detalle,
                'personal' => $personal,
                'seccion'  => $seccion,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al consultar la boleta: ' . $e->getMessage(),
            ], 500);
        }
    }

    
}
