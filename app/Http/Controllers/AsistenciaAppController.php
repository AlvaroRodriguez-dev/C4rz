<?php

namespace App\Http\Controllers;

use App\Models\AsistenciaApp;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\RrhhAgencia;

class AsistenciaAppController extends Controller
{
    // ── Formulario de registro ───────────────────────────────────────
    public function index()
    {
        $user    = Auth::user();
        $schema  = env('PG_RRHH_SCHEMA', '2026');

        // Verificar que el usuario tiene LICENSE asignado
        if (!$user->license) {
            return view('asistencia.registro', [
                'personal'    => null,
                'sinLicense'  => true,
                'registrosHoy' => [],
            ]);
        }

        // Obtener datos del personal desde PostgreSQL
        $personal = DB::connection('pgsql_rrhh')
            ->table(DB::raw('"' . $schema . '"."rrhh_personal"'))
            ->where('LICENSE', $user->license)
            ->whereNull('DELETED_AT')
            ->first();

        // Registros de hoy
        $registrosHoy = AsistenciaApp::where('user_id', $user->id)
            ->whereDate('fecha_servidor', today())
            ->orderBy('fecha_servidor')
            ->get();

        return view('asistencia.registro', compact('personal', 'registrosHoy'));
    }

    // ── Guardar registro de asistencia ───────────────────────────────
    public function registrar(Request $request)
    {
        $request->validate([
            'tipo'          => 'required|in:INGRESO,SALIDA',
            'foto'          => 'required|string',
            'fecha_cliente' => 'nullable|string',
            'latitud'       => 'required|numeric',
            'longitud'      => 'required|numeric',
            'direccion'     => 'nullable|string|max:255',
        ]);

        $user   = Auth::user();
        $schema = env('PG_RRHH_SCHEMA', '2026');

        // ── Verificar LICENSE ────────────────────────────────────────────
        if (!$user->license) {
            return response()->json([
                'success' => false,
                'message' => 'Tu usuario no tiene un número de empleado asignado.',
            ], 422);
        }

        // ── Validar límite de registros del día ──────────────────────────
        $registrosHoy = AsistenciaApp::where('user_id', $user->id)
            ->whereDate('fecha_servidor', today())
            ->get();

        $ingresos = $registrosHoy->where('tipo', 'INGRESO')->count();
        $salidas  = $registrosHoy->where('tipo', 'SALIDA')->count();

        if ($request->tipo === 'INGRESO' && $ingresos >= 2) {
            return response()->json([
                'success' => false,
                'message' => 'Ya tienes 2 registros de INGRESO para hoy.',
            ], 422);
        }

        if ($request->tipo === 'SALIDA' && $salidas >= 2) {
            return response()->json([
                'success' => false,
                'message' => 'Ya tienes 2 registros de SALIDA para hoy.',
            ], 422);
        }

        // ── Validar cercanía a agencias asignadas ────────────────────────
        $agenciasAsignadas = $user->rrhhAgencias()->where('activo', true)->get();

        if ($agenciasAsignadas->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes agencias asignadas. Contacta al administrador.',
            ], 422);
        }

        $agenciaEncontrada = null;
        $distanciaMinima   = PHP_INT_MAX;

        foreach ($agenciasAsignadas as $agencia) {
            $distancia = $agencia->distanciaMetros(
                (float) $request->latitud,
                (float) $request->longitud
            );

            if ($distancia < $distanciaMinima) {
                $distanciaMinima = $distancia;
            }

            if ($agencia->dentroDeRango((float) $request->latitud, (float) $request->longitud)) {
                $agenciaEncontrada = $agencia;
                break;
            }
        }

        if (!$agenciaEncontrada) {
            $distanciaTexto = $distanciaMinima >= 1000
                ? number_format($distanciaMinima / 1000, 1) . ' km'
                : number_format($distanciaMinima, 0) . ' m';

            return response()->json([
                'success'   => false,
                'fuera_rango' => true,
                'message'   => "Estás fuera de rango. Tu ubicación más cercana a una agencia " .
                    "asignada es de {$distanciaTexto}. " .
                    "Debes estar dentro del área autorizada para registrar.",
            ], 422);
        }

        // ── Obtener datos del personal ───────────────────────────────────
        $personal = DB::connection('pgsql_rrhh')
            ->table(DB::raw('"' . $schema . '"."rrhh_personal"'))
            ->where('LICENSE', $user->license)
            ->whereNull('DELETED_AT')
            ->first();

        if (!$personal) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontraron datos del empleado en el sistema de RRHH.',
            ], 422);
        }

        // ── Procesar foto (miniatura) ────────────────────────────────────
        try {
            $base64 = $request->foto;
            if (str_contains($base64, ',')) {
                $base64 = explode(',', $base64)[1];
            }

            $imgData  = base64_decode($base64);
            $filename = 'asistencia/' . date('Y/m/d') . '/' .
                $user->id . '_' . time() . '_' .
                strtolower($request->tipo) . '.jpg';

            $imgSrc    = imagecreatefromstring($imgData);
            if ($imgSrc === false) throw new \Exception('No se pudo procesar la imagen.');

            $anchoOrig = imagesx($imgSrc);
            $altoOrig  = imagesy($imgSrc);
            $tamano    = 200;
            $lado      = min($anchoOrig, $altoOrig);
            $srcX      = (int)(($anchoOrig - $lado) / 2);
            $srcY      = (int)(($altoOrig  - $lado) / 2);

            $miniatura = imagecreatetruecolor($tamano, $tamano);
            imagecopyresampled(
                $miniatura,
                $imgSrc,
                0,
                0,
                $srcX,
                $srcY,
                $tamano,
                $tamano,
                $lado,
                $lado
            );

            ob_start();
            imagejpeg($miniatura, null, 85);
            $miniaturaData = ob_get_clean();

            imagedestroy($imgSrc);
            imagedestroy($miniatura);

            Storage::disk('public')->put($filename, $miniaturaData);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar la fotografía: ' . $e->getMessage(),
            ], 500);
        }

        // ── Parsear fecha cliente ────────────────────────────────────────
        $fechaCliente = null;
        if ($request->fecha_cliente) {
            try {
                $fechaCliente = \Carbon\Carbon::parse($request->fecha_cliente);
            } catch (\Throwable) {
            }
        }

        // ── Guardar registro ─────────────────────────────────────────────
        AsistenciaApp::create([
            'user_id'        => $user->id,
            'license'        => $user->license,
            'name'           => $personal->NAME,
            'lastname'       => $personal->LASTNAME,
            'tipo'           => $request->tipo,
            'foto'           => $filename,
            'fecha_servidor' => now(),
            'fecha_cliente'  => $fechaCliente,
            'latitud'        => $request->latitud,
            'longitud'       => $request->longitud,
            'direccion'      => $request->direccion,
        ]);

        // ── Registros actualizados del día ───────────────────────────────
        $registrosActualizados = AsistenciaApp::where('user_id', $user->id)
            ->whereDate('fecha_servidor', today())
            ->orderBy('fecha_servidor')
            ->get()
            ->map(fn($r) => [
                'tipo' => $r->tipo,
                'hora' => \Carbon\Carbon::parse($r->fecha_servidor)->format('H:i:s'),
                'foto' => asset('storage/' . $r->foto),
            ]);

        return response()->json([
            'success'       => true,
            'message'       => $request->tipo . ' registrado correctamente en ' .
                $agenciaEncontrada->nombre . '.',
            'tipo'          => $request->tipo,
            'hora'          => now()->format('H:i:s'),
            'agencia'       => $agenciaEncontrada->nombre,
            'registrosHoy'  => $registrosActualizados,
        ]);
    }

    // ── Mis marcajes (vista del empleado) ────────────────────────────
    public function misMarcajes(Request $request)
    {
        $user = Auth::user();

        $marcajes = AsistenciaApp::where('user_id', $user->id)
            ->whereMonth('fecha_servidor', $request->mes  ?? date('m'))
            ->whereYear('fecha_servidor',  $request->anio ?? date('Y'))
            ->orderBy('fecha_servidor', 'desc')
            ->get(); // ← sin map(), devolver modelos Eloquent directamente

        return view('asistencia.mis_marcajes', compact('marcajes'));
    }
}
