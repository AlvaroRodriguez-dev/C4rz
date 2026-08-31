<?php

namespace App\Http\Controllers;

use App\Models\Biometrico;
use App\Models\BioUsuario;
use App\Models\BioAsistencia;
use App\Models\BioAsistenciaImportada;
use Illuminate\Http\Request;
use App\Lib\ZKTecoClient;
use Illuminate\Support\Facades\DB;

class BiometricoController extends Controller
{
    // ── Mapa de métodos de verificación (campo state) ────────────────
    const VERIFICATION_MAP = [
        0  => ['label' => 'Contraseña', 'color' => 'gray'],
        1  => ['label' => 'Huella',     'color' => 'blue'],
        2  => ['label' => 'Anormal',    'color' => 'red'],
        3  => ['label' => 'PIN',    'color' => 'red'],
        4  => ['label' => 'Tarjeta RF', 'color' => 'yellow'],
        15 => ['label' => 'Rostro',     'color' => 'green'],
        25 => ['label' => 'Palma',      'color' => 'purple'],
    ];

    // ── Mapa de tipos de marcaje online (campo type) ─────────────────
    const TYPE_MAP = [
        0   => 'Entrada',
        1   => 'Salida',
        2   => 'HE Entrada',
        3   => 'HE Salida',
        4   => 'Break Entrada',
        5   => 'Break Salida',
        255 => 'Sin tipo',
    ];

    // ── Mapa de estado USB (col 4 del archivo) ───────────────────────
    const USB_STATE_MAP = [
        0 => 'Entrada',
        1 => 'Salida',
        2 => 'Salida Descanso',
        3 => 'Entrada Descanso',
    ];

    // ── Mapa de verificación USB (col 5 del archivo) ─────────────────
    const USB_VERIFY_MAP = [
        1  => ['label' => 'Huella',     'color' => 'blue'],
        2  => ['label' => 'Contraseña', 'color' => 'gray'],
        3  => ['label' => 'Tarjeta RF', 'color' => 'yellow'],
        15 => ['label' => 'Rostro',     'color' => 'green'],
        25 => ['label' => 'Palma',     'color' => 'green'],
    ];

    // ════════════════════════════════════════════════════════════════
    // HELPERS PRIVADOS
    // ════════════════════════════════════════════════════════════════

    private function limpiarEncoding(mixed $valor): mixed
    {
        if (is_string($valor)) {
            if (!mb_check_encoding($valor, 'UTF-8')) {
                return mb_convert_encoding($valor, 'UTF-8', 'ISO-8859-1,CP1252');
            }
            return trim($valor);
        }
        return $valor;
    }

    private function normalizarUsuario(mixed $u): ?array
    {
        if (!is_array($u)) return null;

        if (array_key_exists('uid', $u)) {
            return [
                'uid'      => $u['uid']      ?? null,
                'user_id'  => $u['userid']   ?? $u['user_id'] ?? null,
                'name'     => $u['name']     ?? null,
                'role'     => $u['role']     ?? 0,
                'password' => $u['password'] ?? null,
                'card_no'  => $u['cardno']   ?? $u['card_no'] ?? null,
            ];
        }

        if (array_key_exists(0, $u)) {
            return [
                'uid'      => $u[0] ?? null,
                'user_id'  => $u[1] ?? null,
                'name'     => $u[2] ?? null,
                'role'     => $u[3] ?? 0,
                'password' => $u[4] ?? null,
                'card_no'  => $u[5] ?? null,
            ];
        }

        return null;
    }

    private function normalizarAsistencia(mixed $r): ?array
    {
        if (!is_array($r)) return null;

        if (array_key_exists('uid', $r)) {
            return [
                'uid'       => $r['uid']       ?? null,
                'user_id'   => $r['id']        ?? $r['user_id'] ?? $r['userid'] ?? null,
                'state'     => $r['state']     ?? null,
                'timestamp' => $r['timestamp'] ?? null,
                'type'      => $r['type']      ?? null,
            ];
        }

        if (array_key_exists(0, $r)) {
            return [
                'uid'       => $r[0] ?? null,
                'user_id'   => $r[1] ?? null,
                'state'     => $r[2] ?? null,
                'timestamp' => $r[3] ?? null,
                'type'      => $r[4] ?? null,
            ];
        }

        return null;
    }

    private function conectar(Biometrico $bio): ZKTecoClient
    {
        $zk = new ZKTecoClient(
            $bio->ip,
            $bio->puerto  ?? 4370,
            ($bio->timeout ?? 10) * 4
        );

        if (!$zk->connect()) {
            throw new \RuntimeException(
                "No se pudo conectar al biométrico: {$bio->agencia} ({$bio->ip}:{$bio->puerto})"
            );
        }

        return $zk;
    }

    // ════════════════════════════════════════════════════════════════
    // RECUPERAR DATOS
    // ════════════════════════════════════════════════════════════════

    public function recuperar()
    {
        $biometricos = Biometrico::orderBy('agencia')->get();
        return view('biometricos.recuperar', compact('biometricos'));
    }

    public function recuperarUsuarios(Request $request)
    {
        $request->validate(['biometrico_id' => 'required|exists:biometricos,id']);
        $bio = Biometrico::findOrFail($request->biometrico_id);

        try {
            $zk = $this->conectar($bio);

            $zk->disableDevice();
            $rawUsuarios = $zk->getUser();
            $zk->enableDevice();
            $zk->disconnect();

            if (empty($rawUsuarios)) {
                return response()->json([
                    'success'      => true,
                    'message'      => 'Conexión exitosa pero no hay usuarios en el dispositivo.',
                    'total'        => 0,
                    'insertados'   => 0,
                    'actualizados' => 0,
                    'ultima_sinc'  => null,
                ]);
            }

            $insertados   = 0;
            $actualizados = 0;

            foreach ($rawUsuarios as $raw) {
                $u = $this->normalizarUsuario($raw);
                if (!$u || is_null($u['uid'])) continue;

                $datos = [
                    'biometrico_id' => $bio->id,
                    'uid'           => $u['uid'],
                    'user_id'       => $this->limpiarEncoding($u['user_id']),
                    'name'          => $this->limpiarEncoding($u['name']),
                    'role'          => $u['role'],
                    'password'      => $this->limpiarEncoding($u['password']),
                    'card_no'       => $this->limpiarEncoding($u['card_no']),
                ];

                $existe = BioUsuario::where('biometrico_id', $bio->id)
                    ->where('uid', $u['uid'])
                    ->first();

                if ($existe) {
                    $existe->update($datos);
                    $actualizados++;
                } else {
                    BioUsuario::create($datos);
                    $insertados++;
                }
            }

            $ahora = now();
            $bio->update(['ultima_sinc_usuarios' => $ahora]);

            return response()->json([
                'success'      => true,
                'message'      => "Usuarios recuperados correctamente.",
                'insertados'   => $insertados,
                'actualizados' => $actualizados,
                'total'        => count($rawUsuarios),
                'ultima_sinc'  => $ahora->format('d/m/Y H:i:s'),
            ]);
        } catch (\Throwable $e) {
            try {
                $zk->enableDevice();
            } catch (\Throwable) {
            }
            try {
                $zk->disconnect();
            } catch (\Throwable) {
            }
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function recuperarRegistros(Request $request)
    {
        $request->validate(['biometrico_id' => 'required|exists:biometricos,id']);
        set_time_limit(300);
        $bio = Biometrico::findOrFail($request->biometrico_id);

        try {
            $zk           = $this->conectar($bio);
            $rawRegistros = [];
            $excepcion    = null;

            for ($intento = 1; $intento <= 3; $intento++) {
                try {
                    $zk->disableDevice();
                    $rawRegistros = $zk->getAttendance();
                    $zk->enableDevice();
                    break;
                } catch (\Throwable $e) {
                    $excepcion = $e;
                    try {
                        $zk->enableDevice();
                    } catch (\Throwable) {
                    }
                    if ($intento < 3) {
                        sleep($intento * 2);
                        try {
                            $zk->disconnect();
                        } catch (\Throwable) {
                        }
                        sleep(1);
                        $zk = $this->conectar($bio);
                    }
                }
            }

            try {
                $zk->disconnect();
            } catch (\Throwable) {
            }

            if (empty($rawRegistros) && $excepcion) throw $excepcion;

            if (empty($rawRegistros)) {
                return response()->json([
                    'success'    => true,
                    'message'    => 'Conexión exitosa pero no hay registros en el dispositivo.',
                    'total'      => 0,
                    'insertados' => 0,
                    'duplicados' => 0,
                    'ultima_sinc' => null,
                ]);
            }

            $insertados = 0;
            $duplicados = 0;
            $lote       = [];

            foreach ($rawRegistros as $raw) {
                $r = $this->normalizarAsistencia($raw);
                if (!$r || is_null($r['uid'])) continue;

                // Unique: biometrico + user_id + timestamp
                $existe = BioAsistencia::where('biometrico_id', $bio->id)
                    ->where('user_id', $r['user_id'])
                    ->where('timestamp', $r['timestamp'])
                    ->exists();
                if ($existe) {
                    $duplicados++;
                    continue;
                }

                $lote[] = [
                    'biometrico_id' => $bio->id,
                    'uid'           => $r['uid'],
                    'user_id'       => $this->limpiarEncoding($r['user_id']),
                    'state'         => $r['state'],
                    'timestamp'     => $this->limpiarEncoding($r['timestamp']),
                    'type'          => $r['type'],
                    'fuente'        => 'online',
                    'archivo_origen' => null,
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ];

                if (count($lote) >= 200) {
                    BioAsistencia::insert($lote);
                    $insertados += count($lote);
                    $lote = [];
                }
            }

            if (!empty($lote)) {
                BioAsistencia::insert($lote);
                $insertados += count($lote);
            }

            $ahora = now();
            $bio->update(['ultima_sinc_registros' => $ahora]);

            return response()->json([
                'success'    => true,
                'message'    => "Registros recuperados correctamente.",
                'insertados' => $insertados,
                'duplicados' => $duplicados,
                'total'      => count($rawRegistros),
                'ultima_sinc' => $ahora->format('d/m/Y H:i:s'),
            ]);
        } catch (\Throwable $e) {
            try {
                $zk->enableDevice();
            } catch (\Throwable) {
            }
            try {
                $zk->disconnect();
            } catch (\Throwable) {
            }
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    // ════════════════════════════════════════════════════════════════
    // IMPORTAR DESDE USB
    // ════════════════════════════════════════════════════════════════

    public function importar()
    {
        $biometricos = Biometrico::orderBy('agencia')->get();
        return view('biometricos.importar', compact('biometricos'));
    }

    public function procesarImportacion(Request $request)
    {
        $request->validate([
            'biometrico_id' => 'required|exists:biometricos,id',
            'archivo'       => 'required|file|mimes:txt,dat,log|max:20480',
        ]);

        set_time_limit(300);

        $bio     = Biometrico::findOrFail($request->biometrico_id);
        $archivo = $request->file('archivo');
        $nombre  = $archivo->getClientOriginalName();

        try {
            $contenido = file_get_contents($archivo->getRealPath());
            if (!mb_check_encoding($contenido, 'UTF-8')) {
                $contenido = mb_convert_encoding($contenido, 'UTF-8', 'ISO-8859-1,CP1252');
            }

            $lineas      = explode("\n", str_replace("\r\n", "\n", $contenido));
            $insertados  = 0;
            $errores     = 0;

            // ── Detalle de duplicados y descartados ──────────────────────
            $detalleDuplicadosArchivo = []; // duplicados dentro del mismo archivo
            $detalleDuplicadosBD      = []; // ya existían en bio_asistencia
            $detalleDescartados       = []; // fechas 1970 u otros descartados
            $detalleErrores           = []; // líneas con formato inválido

            $procesados = [];
            $respaldo   = [];
            $lote       = [];
            $tamanoLote = 200;
            $numLinea   = 0;

            foreach ($lineas as $linea) {
                $numLinea++;
                $linea = trim($linea);
                if (empty($linea)) continue;

                $cols = preg_split('/\t+/', $linea);
                if (count($cols) < 5) {
                    $errores++;
                    $detalleErrores[] = [
                        'linea'   => $numLinea,
                        'contenido' => substr($linea, 0, 80),
                        'motivo'  => 'Formato inválido (' . count($cols) . ' columnas)',
                    ];
                    continue;
                }

                $userId       = trim($cols[0]);
                $timestamp    = trim($cols[1]);
                $deviceId     = isset($cols[2]) ? (int) trim($cols[2]) : 1;
                $state        = isset($cols[3]) ? (int) trim($cols[3]) : null;
                $verifyMethod = isset($cols[4]) ? (int) trim($cols[4]) : null;
                $workCode     = isset($cols[5]) ? (int) trim($cols[5]) : 0;

                // Descartar fechas 1970
                if (str_starts_with($timestamp, '1970-')) {
                    $detalleDescartados[] = [
                        'linea'   => $numLinea,
                        'user_id' => $userId,
                        'timestamp' => $timestamp,
                        'motivo'  => 'Fecha 1970 (época Unix)',
                    ];
                    continue;
                }

                // Validar formato fecha
                try {
                    \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $timestamp);
                } catch (\Throwable) {
                    $errores++;
                    $detalleErrores[] = [
                        'linea'     => $numLinea,
                        'user_id'   => $userId,
                        'timestamp' => $timestamp,
                        'motivo'    => 'Formato de fecha inválido',
                    ];
                    continue;
                }

                // Deduplicar dentro del mismo archivo
                $claveInterna = $bio->id . '-' . $userId . '-' . $timestamp;
                if (isset($procesados[$claveInterna])) {
                    $detalleDuplicadosArchivo[] = [
                        'linea'     => $numLinea,
                        'user_id'   => $userId,
                        'timestamp' => $timestamp,
                        'motivo'    => 'Duplicado en el archivo',
                    ];
                    continue;
                }
                $procesados[$claveInterna] = true;

                $now = now();

                $respaldo[] = [
                    'biometrico_id'  => $bio->id,
                    'user_id'        => $userId,
                    'timestamp'      => $timestamp,
                    'device_id'      => $deviceId,
                    'state'          => $state,
                    'verify_method'  => $verifyMethod,
                    'work_code'      => $workCode,
                    'archivo_origen' => $nombre,
                    'created_at'     => $now,
                    'updated_at'     => $now,
                ];

                $lote[] = [
                    'biometrico_id'  => $bio->id,
                    'uid'            => null,
                    'user_id'        => $userId,
                    'state'          => $verifyMethod,
                    'timestamp'      => $timestamp,
                    'type'           => $state,
                    'fuente'         => 'usb',
                    'archivo_origen' => $nombre,
                    'created_at'     => $now,
                    'updated_at'     => $now,
                    // guardamos para el detalle, no va a BD
                    '_user_id_raw'   => $userId,
                    '_timestamp_raw' => $timestamp,
                    '_linea'         => $numLinea,
                ];

                if (count($lote) >= $tamanoLote) {
                    $resultado          = $this->insertarLoteIgnorando($lote, $detalleDuplicadosBD);
                    $insertados        += $resultado['insertados'];
                    $lote               = [];
                }

                if (count($respaldo) >= $tamanoLote) {
                    $this->insertarRespaldoUsb($respaldo);
                    $respaldo = [];
                }
            }

            // Restos
            if (!empty($lote)) {
                $resultado   = $this->insertarLoteIgnorando($lote, $detalleDuplicadosBD);
                $insertados += $resultado['insertados'];
            }

            if (!empty($respaldo)) {
                $this->insertarRespaldoUsb($respaldo);
            }

            return response()->json([
                'success'      => true,
                'message'      => "Archivo procesado correctamente.",
                'archivo'      => $nombre,
                'total_lineas' => $numLinea,
                'insertados'   => $insertados,

                // Resumen de cada categoría
                'duplicados_archivo' => count($detalleDuplicadosArchivo),
                'duplicados_bd'      => count($detalleDuplicadosBD),
                'descartados'        => count($detalleDescartados),
                'errores'            => $errores,

                // Detalle para depuración
                'detalle' => [
                    'duplicados_archivo' => $detalleDuplicadosArchivo,
                    'duplicados_bd'      => $detalleDuplicadosBD,
                    'descartados'        => $detalleDescartados,
                    'errores'            => $detalleErrores,
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar el archivo: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * INSERT IGNORE con rastreo de duplicados contra BD.
     */
    private function insertarLoteIgnorando(array $lote, array &$detalleDuplicadosBD): array
    {
        if (empty($lote)) return ['insertados' => 0];

        // Extraer metadatos de depuración y limpiar el lote antes de insertar
        $metadatos = [];
        $loteDB    = [];

        foreach ($lote as $fila) {
            $metadatos[] = [
                'user_id'   => $fila['_user_id_raw'],
                'timestamp' => $fila['_timestamp_raw'],
                'linea'     => $fila['_linea'],
            ];
            unset($fila['_user_id_raw'], $fila['_timestamp_raw'], $fila['_linea']);
            $loteDB[] = $fila;
        }

        $columnas     = array_keys($loteDB[0]);
        $placeholders = [];
        $valores      = [];

        foreach ($loteDB as $fila) {
            $grupo = [];
            foreach ($columnas as $col) {
                $grupo[]   = '?';
                $valores[] = $fila[$col];
            }
            $placeholders[] = '(' . implode(', ', $grupo) . ')';
        }

        $sql = 'INSERT IGNORE INTO bio_asistencia ('
            . implode(', ', array_map(fn($c) => "`$c`", $columnas))
            . ') VALUES '
            . implode(', ', $placeholders);

        $afectadas  = DB::affectingStatement($sql, $valores);
        $ignoradas  = count($loteDB) - $afectadas;

        // Si hubo ignoradas, identificar cuáles verificando contra BD
        if ($ignoradas > 0) {
            foreach ($metadatos as $meta) {
                $existe = BioAsistencia::where('biometrico_id', $loteDB[0]['biometrico_id'])
                    ->where('user_id',  $meta['user_id'])
                    ->where('timestamp', $meta['timestamp'])
                    ->exists();
                if ($existe) {
                    $detalleDuplicadosBD[] = [
                        'linea'     => $meta['linea'],
                        'user_id'   => $meta['user_id'],
                        'timestamp' => $meta['timestamp'],
                        'motivo'    => 'Ya existe en base de datos',
                    ];
                }
            }
        }

        return ['insertados' => $afectadas];
    }

    /**
     * Inserta en bio_asistencia_importada ignorando duplicados
     * (mismo archivo puede subirse dos veces por error del usuario)
     */
    private function insertarRespaldoUsb(array $lote): void
    {
        foreach ($lote as $registro) {
            BioAsistenciaImportada::updateOrInsert(
                [
                    'biometrico_id' => $registro['biometrico_id'],
                    'user_id'       => $registro['user_id'],
                    'timestamp'     => $registro['timestamp'],
                ],
                $registro
            );
        }
    }

    // ════════════════════════════════════════════════════════════════
    // REPORTE DE ASISTENCIA
    // ════════════════════════════════════════════════════════════════

    public function reporte()
    {
        $biometricos = Biometrico::orderBy('agencia')->get();
        return view('biometricos.reporte', compact('biometricos'));
    }

    public function usuariosPorBiometrico(Request $request)
    {
        $request->validate(['biometrico_id' => 'required|exists:biometricos,id']);
        $usuarios = BioUsuario::where('biometrico_id', $request->biometrico_id)
            ->orderBy('name')
            ->get(['id', 'user_id', 'name']);
        return response()->json($usuarios);
    }

    public function generarReporte(Request $request)
    {
        $request->validate([
            'biometrico_id' => 'required|exists:biometricos,id',
            'fecha_ini'     => 'required|date',
            'fecha_fin'     => 'required|date|after_or_equal:fecha_ini',
            'usuario_id'    => 'nullable|exists:bio_usuarios,id',
        ]);

        $query = BioAsistencia::where('biometrico_id', $request->biometrico_id)
            ->whereBetween('timestamp', [
                $request->fecha_ini . ' 00:00:00',
                $request->fecha_fin . ' 23:59:59',
            ]);

        if ($request->usuario_id) {
            $usuario = BioUsuario::find($request->usuario_id);
            if ($usuario) $query->where('user_id', $usuario->user_id);
        }

        $registros = $query->orderBy('timestamp')->get();

        // ── Agrupar por fecha y user_id ──────────────────────────────
        $agrupado = [];
        foreach ($registros as $r) {
            $fecha  = date('Y-m-d', strtotime($r->timestamp));
            $userId = $r->user_id;

            // Determinar mapas según fuente
            if ($r->fuente === 'usb') {
                $verif = self::USB_VERIFY_MAP[$r->state]
                    ?? ['label' => 'Desconocido', 'color' => 'gray'];
                $tipo  = self::USB_STATE_MAP[$r->type]
                    ?? 'Estado ' . $r->type;
            } else {
                $verif = self::VERIFICATION_MAP[$r->state]
                    ?? ['label' => 'Desconocido', 'color' => 'gray'];
                $tipo  = self::TYPE_MAP[$r->type]
                    ?? 'Tipo ' . $r->type;
            }

            $agrupado[$fecha][$userId][] = [
                'hora'         => date('H:i:s', strtotime($r->timestamp)),
                'tipo'         => $tipo,
                'verificacion' => $verif['label'],
                'color'        => $verif['color'],
            ];
        }

        // Ordenar marcajes por hora dentro de cada día/usuario
        foreach ($agrupado as &$porUsuario) {
            foreach ($porUsuario as &$marcajes) {
                usort($marcajes, fn($a, $b) => strcmp($a['hora'], $b['hora']));
            }
        }
        ksort($agrupado);

        // ── Nombres de usuarios ──────────────────────────────────────
        $userIds = [];
        foreach ($agrupado as $porUsuario) {
            foreach (array_keys($porUsuario) as $uid) {
                $userIds[] = $uid;
            }
        }
        $userIds  = array_unique($userIds);
        $usuarios = BioUsuario::where('biometrico_id', $request->biometrico_id)
            ->whereIn('user_id', $userIds)
            ->pluck('name', 'user_id');

        $bio = Biometrico::find($request->biometrico_id);

        return view(
            'biometricos.reporte_resultado',
            compact('agrupado', 'usuarios', 'bio', 'request')
        );
    }
}
