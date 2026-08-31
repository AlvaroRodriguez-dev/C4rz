<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class VerificarBdController extends Controller
{
    /**
     * Mostrar formulario inicial
     */
    public function index()
    {
        return view('verificar-bd.index');
    }

    /**
     * Procesar formulario: listar bases que coincidan
     */
    public function listar(Request $request)
    {
        $request->validate([
            'servidor_ip' => 'required|string',
            'filtro'      => 'required|string',
        ]);

        $ip     = $request->input('servidor_ip');
        $filtro = $request->input('filtro');

        // Configurar conexión dinámica al servidor indicado
        Config::set('database.connections.simec_dinamica.host', $ip);
        Config::set('database.connections.simec_dinamica.database', '');

        // Forzar reconexión (limpiar conexión cacheada)
        DB::purge('inventario_dinamica');

        try {
            $resultados = DB::connection('simec_dinamica')
                ->select("SHOW DATABASES LIKE '%" . $filtro . "%'");
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->withErrors(['conexion' => 'No se pudo conectar al servidor: ' . $e->getMessage()]);
        }

        // Extraer nombres de bases (la key varía según versión, usamos array_values)
        $bases = [];
        foreach ($resultados as $row) {
            $bases[] = array_values((array) $row)[0];
        }

        return view('verificar-bd.resultado', [
            'servidor_ip' => $ip,
            'filtro'      => $filtro,
            'bases'       => $bases,
        ]);
    }

    /**
     * Verificar último registro mayorizado en tabla glosa
     */
    /**
     * Verificar meses con registros pendientes de mayorizar en tabla glosa
     */
    public function verificarGlosa(Request $request)
    {
        $request->validate([
            'servidor_ip' => 'required|string',
            'base'        => 'required|string',
        ]);

        $ip   = $request->input('servidor_ip');
        $base = $request->input('base');

        Config::set('database.connections.simec_dinamica.host', $ip);
        Config::set('database.connections.simec_dinamica.database', $base);
        DB::purge('simec_dinamica');

        try {
            // Agrupar por año-mes, contando cuántos registros NO están mayorizados (M)
            $resultados = DB::connection('simec_dinamica')
                ->table('glosa')
                ->select(
                    DB::raw('YEAR(FECHA) as anio'),
                    DB::raw('MONTH(FECHA) as mes'),
                    DB::raw("SUM(CASE WHEN MAYORIZADO != 'M' OR MAYORIZADO IS NULL THEN 1 ELSE 0 END) as pendientes")
                )
                ->whereNotNull('FECHA')
                ->groupBy('anio', 'mes')
                ->orderBy('anio')
                ->orderBy('mes')
                ->get();

            if ($resultados->isEmpty()) {
                return response()->json([
                    'ok'      => false,
                    'mensaje' => 'NO MAYORIZADO',
                ]);
            }

            $meses = [
                1 => 'ENERO',
                2 => 'FEBRERO',
                3 => 'MARZO',
                4 => 'ABRIL',
                5 => 'MAYO',
                6 => 'JUNIO',
                7 => 'JULIO',
                8 => 'AGOSTO',
                9 => 'SEPTIEMBRE',
                10 => 'OCTUBRE',
                11 => 'NOVIEMBRE',
                12 => 'DICIEMBRE',
            ];


            //-------------------------
            $pendientes = [];
            foreach ($resultados as $row) {
                if ($row->pendientes > 0) {
                    $nombreMes = $meses[(int) $row->mes] ?? $row->mes;
                    $pendientes[] = [
                        'texto' => $nombreMes . ' ' . $row->anio . ' SIN MAYORIZAR (' . $row->pendientes . ')',
                        'anio'  => $row->anio,
                        'mes'   => $row->mes,
                    ];
                }
            }

            if (empty($pendientes)) {
                return response()->json([
                    'ok'      => true,
                    'mensaje' => 'MAYORIZADO COMPLETO',
                ]);
            }

            return response()->json([
                'ok'     => false,
                'tipo'   => 'glosa',
                'items'  => $pendientes,
            ]);
            //---------

        } catch (\Exception $e) {
            return response()->json([
                'ok'      => false,
                'mensaje' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Verificar meses con registros sin bloquear en tabla recep
     */
    public function verificarIngresos(Request $request)
    {
        $request->validate([
            'servidor_ip' => 'required|string',
            'base'        => 'required|string',
        ]);

        $ip   = $request->input('servidor_ip');
        $base = $request->input('base');

        Config::set('database.connections.simec_dinamica.host', $ip);
        Config::set('database.connections.simec_dinamica.database', $base);
        DB::purge('simec_dinamica');

        try {
            $resultados = DB::connection('simec_dinamica')
                ->table('recep')
                ->select(
                    DB::raw('YEAR(RFECHA) as anio'),
                    DB::raw('MONTH(RFECHA) as mes'),
                    DB::raw("SUM(CASE WHEN RANULADA NOT IN ('*','A') OR RANULADA IS NULL THEN 1 ELSE 0 END) as pendientes")
                )
                ->whereNotNull('RFECHA')
                ->groupBy('anio', 'mes')
                ->orderBy('anio')
                ->orderBy('mes')
                ->get();

            if ($resultados->isEmpty()) {
                return response()->json([
                    'ok'      => false,
                    'mensaje' => 'BD EN BLANCO',
                ]);
            }

            $meses = [
                1 => 'ENERO',
                2 => 'FEBRERO',
                3 => 'MARZO',
                4 => 'ABRIL',
                5 => 'MAYO',
                6 => 'JUNIO',
                7 => 'JULIO',
                8 => 'AGOSTO',
                9 => 'SEPTIEMBRE',
                10 => 'OCTUBRE',
                11 => 'NOVIEMBRE',
                12 => 'DICIEMBRE',
            ];

            $pendientes = [];
            foreach ($resultados as $row) {
                if ($row->pendientes > 0) {
                    $nombreMes = $meses[(int) $row->mes] ?? $row->mes;
                    $pendientes[] = $nombreMes . ' ' . $row->anio . ' SIN BLOQUEAR (' . $row->pendientes . ') <br>';
                }
            }

            if (empty($pendientes)) {
                return response()->json([
                    'ok'      => true,
                    'mensaje' => 'BLOQUEO COMPLETO',
                ]);
            }

            return response()->json([
                'ok'      => false,
                'mensaje' => implode(', ', $pendientes),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'ok'      => false,
                'mensaje' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Verificar meses con registros sin bloquear/anular en una tabla genérica
     * Aplica para: recep, entregas, trasp, ventas, cobranza
     */
    public function verificarMovimiento(Request $request)
    {
        $request->validate([
            'servidor_ip' => 'required|string',
            'base'        => 'required|string',
            'tabla'       => 'required|string|in:recep,entregas,trasp,ventas,cobranza',
            'campo_fecha' => 'required|string',
            'campo_anulada' => 'required|string',
        ]);

        $ip            = $request->input('servidor_ip');
        $base          = $request->input('base');
        $tabla         = $request->input('tabla');
        $campoFecha    = $request->input('campo_fecha');
        $campoAnulada  = $request->input('campo_anulada');

        Config::set('database.connections.simec_dinamica.host', $ip);
        Config::set('database.connections.simec_dinamica.database', $base);
        DB::purge('simec_dinamica');

        try {
            $resultados = DB::connection('simec_dinamica')
                ->table($tabla)
                ->select(
                    DB::raw("YEAR($campoFecha) as anio"),
                    DB::raw("MONTH($campoFecha) as mes"),
                    DB::raw("SUM(CASE WHEN $campoAnulada NOT IN ('*','A') OR $campoAnulada IS NULL THEN 1 ELSE 0 END) as pendientes")
                )
                ->whereNotNull($campoFecha)
                ->groupBy('anio', 'mes')
                ->orderBy('anio')
                ->orderBy('mes')
                ->get();

            if ($resultados->isEmpty()) {
                return response()->json([
                    'ok'      => false,
                    'mensaje' => 'SIN DATOS',
                ]);
            }

            $meses = [
                1 => 'ENERO',
                2 => 'FEBRERO',
                3 => 'MARZO',
                4 => 'ABRIL',
                5 => 'MAYO',
                6 => 'JUNIO',
                7 => 'JULIO',
                8 => 'AGOSTO',
                9 => 'SEPTIEMBRE',
                10 => 'OCTUBRE',
                11 => 'NOVIEMBRE',
                12 => 'DICIEMBRE',
            ];

            //--------------------------------
            $pendientes = [];
            foreach ($resultados as $row) {
                if ($row->pendientes > 0) {
                    $nombreMes = $meses[(int) $row->mes] ?? $row->mes;
                    $pendientes[] = [
                        'texto' => $nombreMes . ' ' . $row->anio . ' SIN BLOQUEAR (' . $row->pendientes . ')',
                        'anio'  => $row->anio,
                        'mes'   => $row->mes,
                    ];
                }
            }

            if (empty($pendientes)) {
                return response()->json([
                    'ok'      => true,
                    'mensaje' => 'BLOQUEADO COMPLETO',
                ]);
            }

            return response()->json([
                'ok'     => false,
                'tipo'   => $tabla,
                'items'  => $pendientes,
            ]);
            //---------------------------------
        } catch (\Exception $e) {
            return response()->json([
                'ok'      => false,
                'mensaje' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mostrar detalle de registros pendientes de un mes/año específico
     */
    public function detalle(Request $request)
    {
        $request->validate([
            'servidor_ip' => 'required|string',
            'base'        => 'required|string',
            'tipo'        => 'required|string|in:glosa,recep,entregas,trasp,ventas,cobranza',
            'anio'        => 'required|integer',
            'mes'         => 'required|integer|min:1|max:12',
        ]);

        $ip    = $request->input('servidor_ip');
        $base  = $request->input('base');
        $tipo  = $request->input('tipo');
        $anio  = $request->input('anio');
        $mes   = $request->input('mes');

        // Configuración por tipo: tabla, campo fecha, campo estado, valor "ok", columnas a mostrar
        $config = [
            'glosa' => [
                'tabla'       => 'glosa',
                'campo_fecha' => 'FECHA',
                'campo_estado' => 'MAYORIZADO',
                'valores_ok'  => ['M'],
                'columnas'    => ['FECHA', 'NUMERO', 'GLOSA', 'MAYORIZADO', 'USUARIO'],
                'titulo'      => 'GLOSA',
            ],
            'recep' => [
                'tabla'       => 'recep',
                'campo_fecha' => 'RFECHA',
                'campo_estado' => 'RANULADA',
                'valores_ok'  => ['*', 'A'],
                'columnas'    => ['RDOCUM', 'RFECHA', 'RGLOSA', 'RANULADA', 'RUSUARIO'],
                'titulo'      => 'INGRESOS',
            ],
            'entregas' => [
                'tabla'       => 'entregas',
                'campo_fecha' => 'EFECHA',
                'campo_estado' => 'EANULADA',
                'valores_ok'  => ['*', 'A'],
                'columnas'    => ['EDOCUM', 'EFECHA', 'EGLOSA', 'EANULADA', 'EUSUARIO'],
                'titulo'      => 'ENTREGAS',
            ],
            'trasp' => [
                'tabla'       => 'trasp',
                'campo_fecha' => 'TFECHA',
                'campo_estado' => 'TANULADA',
                'valores_ok'  => ['*', 'A'],
                'columnas'    => ['TDOCUM', 'TFECHA', 'TGLOSA', 'TANULADA', 'TUSUARIO'],
                'titulo'      => 'TRASPASOS',
            ],
            'ventas' => [
                'tabla'       => 'ventas',
                'campo_fecha' => 'VFECHA',
                'campo_estado' => 'VANULADA',
                'valores_ok'  => ['*', 'A'],
                'columnas'    => ['VDOCUM', 'VFECHA', 'VGLOSA', 'VANULADA', 'VUSUARIO'],
                'titulo'      => 'VENTAS',
            ],
            'cobranza' => [
                'tabla'       => 'cobranza',
                'campo_fecha' => 'FECHA',
                'campo_estado' => 'ANULADA',
                'valores_ok'  => ['*', 'A'],
                'columnas'    => ['DOCUM', 'FECHA', 'GLOSA', 'ANULADA', 'USUARIO'],
                'titulo'      => 'COBRANZA',
            ],
        ];

        $cfg = $config[$tipo];

        Config::set('database.connections.simec_dinamica.host', $ip);
        Config::set('database.connections.simec_dinamica.database', $base);
        DB::purge('simec_dinamica');

        try {
            $query = DB::connection('simec_dinamica')
                ->table($cfg['tabla'])
                ->select($cfg['columnas'])
                ->whereYear($cfg['campo_fecha'], $anio)
                ->whereMonth($cfg['campo_fecha'], $mes);

            // Filtrar solo registros PENDIENTES (estado distinto a los valores OK)
            if ($cfg['campo_estado'] === 'MAYORIZADO') {
                $query->where(function ($q) {
                    $q->where('MAYORIZADO', '!=', 'M')
                        ->orWhereNull('MAYORIZADO');
                });
            } else {
                $valoresOk = $cfg['valores_ok'];
                $query->where(function ($q) use ($cfg, $valoresOk) {
                    $q->whereNotIn($cfg['campo_estado'], $valoresOk)
                        ->orWhereNull($cfg['campo_estado']);
                });
            }

            $registros = $query->orderBy($cfg['campo_fecha'])->get();
        } catch (\Exception $e) {
            $registros = collect();
            $error = $e->getMessage();
        }

        $meses = [
            1 => 'ENERO',
            2 => 'FEBRERO',
            3 => 'MARZO',
            4 => 'ABRIL',
            5 => 'MAYO',
            6 => 'JUNIO',
            7 => 'JULIO',
            8 => 'AGOSTO',
            9 => 'SEPTIEMBRE',
            10 => 'OCTUBRE',
            11 => 'NOVIEMBRE',
            12 => 'DICIEMBRE',
        ];

        // Determinar campo ID por tipo (para el botón de acción)
        $campoId = [
            'glosa'    => 'NUMERO',
            'recep'    => 'RDOCUM',
            'entregas' => 'EDOCUM',
            'trasp'    => 'TDOCUM',
            'ventas'   => 'VDOCUM',
            'cobranza' => 'DOCUM',
        ][$tipo];

        return view('verificar-bd.detalle', [
            'registros'   => $registros,
            'columnas'    => $cfg['columnas'],
            'titulo'      => $cfg['titulo'],
            'base'        => $base,
            'tipo'        => $tipo,
            'servidor_ip' => $ip,
            'nombreMes'   => $meses[(int) $mes] ?? $mes,
            'anio'        => $anio,
            'mes'         => $mes,
            'campoId'     => $campoId,
            'error'       => $error ?? null,
        ]);
    }

    /**
     * Mayorizar o bloquear un registro individual
     */
    public function actualizarRegistro(Request $request)
    {
        $request->validate([
            'servidor_ip'   => 'required|string',
            'base'          => 'required|string',
            'tipo'          => 'required|string|in:glosa,recep,entregas,trasp,ventas,cobranza',
            'campo_id'      => 'required|string',
            'valor_id'      => 'required',
            'anio'          => 'required|integer',
            'mes'           => 'required|integer',
        ]);

        $ip       = $request->input('servidor_ip');
        $base     = $request->input('base');
        $tipo     = $request->input('tipo');
        $campoId  = $request->input('campo_id');
        $valorId  = $request->input('valor_id');
        $anio     = $request->input('anio');
        $mes      = $request->input('mes');

        $config = [
            'glosa'    => ['tabla' => 'glosa',    'campo_estado' => 'MAYORIZADO', 'valor_ok' => 'M'],
            'recep'    => ['tabla' => 'recep',    'campo_estado' => 'RANULADA',   'valor_ok' => '*'],
            'entregas' => ['tabla' => 'entregas', 'campo_estado' => 'EANULADA',   'valor_ok' => '*'],
            'trasp'    => ['tabla' => 'trasp',    'campo_estado' => 'TANULADA',   'valor_ok' => '*'],
            'ventas'   => ['tabla' => 'ventas',   'campo_estado' => 'VANULADA',   'valor_ok' => '*'],
            'cobranza' => ['tabla' => 'cobranza', 'campo_estado' => 'ANULADA',    'valor_ok' => '*'],
        ];

        $cfg = $config[$tipo];

        Config::set('database.connections.simec_dinamica.host', $ip);
        Config::set('database.connections.simec_dinamica.database', $base);
        DB::purge('inventario_dinamica');

        try {
            DB::connection('simec_dinamica')
                ->table($cfg['tabla'])
                ->where($campoId, $valorId)
                ->update([$cfg['campo_estado'] => $cfg['valor_ok']]);

            return redirect()->route('verificar-bd.detalle', [
                'servidor_ip' => $ip,
                'base'        => $base,
                'tipo'        => $tipo,
                'anio'        => $anio,
                'mes'         => $mes,
            ])->with('success', 'Registro actualizado correctamente.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error al actualizar: ' . $e->getMessage());
        }
    }

    /**
     * Mayorizar o bloquear TODOS los registros pendientes de un periodo
     */
    public function actualizarTodos(Request $request)
    {
        $request->validate([
            'servidor_ip' => 'required|string',
            'base'        => 'required|string',
            'tipo'        => 'required|string|in:glosa,recep,entregas,trasp,ventas,cobranza',
            'anio'        => 'required|integer',
            'mes'         => 'required|integer',
        ]);

        $ip   = $request->input('servidor_ip');
        $base = $request->input('base');
        $tipo = $request->input('tipo');
        $anio = $request->input('anio');
        $mes  = $request->input('mes');

        $config = [
            'glosa'    => ['tabla' => 'glosa',    'campo_fecha' => 'FECHA',  'campo_estado' => 'MAYORIZADO', 'valores_ok' => ['M'],      'valor_set' => 'M'],
            'recep'    => ['tabla' => 'recep',    'campo_fecha' => 'RFECHA', 'campo_estado' => 'RANULADA',   'valores_ok' => ['*', 'A'], 'valor_set' => '*'],
            'entregas' => ['tabla' => 'entregas', 'campo_fecha' => 'EFECHA', 'campo_estado' => 'EANULADA',   'valores_ok' => ['*', 'A'], 'valor_set' => '*'],
            'trasp'    => ['tabla' => 'trasp',    'campo_fecha' => 'TFECHA', 'campo_estado' => 'TANULADA',   'valores_ok' => ['*', 'A'], 'valor_set' => '*'],
            'ventas'   => ['tabla' => 'ventas',   'campo_fecha' => 'VFECHA', 'campo_estado' => 'VANULADA',   'valores_ok' => ['*', 'A'], 'valor_set' => '*'],
            'cobranza' => ['tabla' => 'cobranza', 'campo_fecha' => 'FECHA',  'campo_estado' => 'ANULADA',    'valores_ok' => ['*', 'A'], 'valor_set' => '*'],
        ];

        $cfg = $config[$tipo];

        Config::set('database.connections.simec_dinamica.host', $ip);
        Config::set('database.connections.simec_dinamica.database', $base);
        DB::purge('simec_dinamica');

        try {
            $valoresOk = $cfg['valores_ok'];

            $query = DB::connection('simec_dinamica')
                ->table($cfg['tabla'])
                ->whereYear($cfg['campo_fecha'], $anio)
                ->whereMonth($cfg['campo_fecha'], $mes);

            if ($cfg['campo_estado'] === 'MAYORIZADO') {
                $query->where(function ($q) {
                    $q->where('MAYORIZADO', '!=', 'M')
                        ->orWhereNull('MAYORIZADO');
                });
            } else {
                $query->where(function ($q) use ($cfg, $valoresOk) {
                    $q->whereNotIn($cfg['campo_estado'], $valoresOk)
                        ->orWhereNull($cfg['campo_estado']);
                });
            }

            $total = $query->count();
            $query->update([$cfg['campo_estado'] => $cfg['valor_set']]);

            return redirect()->route('verificar-bd.detalle', [
                'servidor_ip' => $ip,
                'base'        => $base,
                'tipo'        => $tipo,
                'anio'        => $anio,
                'mes'         => $mes,
            ])->with('success', "Se actualizaron {$total} registros correctamente.");
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error al actualizar: ' . $e->getMessage());
        }
    }

}
