<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class MigrarContablesController extends Controller
{
    /**
     * Configura y retorna una conexión dinámica.
     */
    private function conectar(string $nombre, string $ip, string $baseDatos): void
    {
        Config::set("database.connections.{$nombre}", [
            'driver'    => 'mysql',
            'host'      => $ip,
            'port'      => env('DB_PORT', 3306),
            'database'  => $baseDatos,
            'username'  => env('SIMEC_DB_USERNAME'),
            'password'  => env('SIMEC_DB_PASSWORD'),
            'charset'   => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix'    => '',
            'strict'    => false,
        ]);
        DB::purge($nombre);
        DB::reconnect($nombre);
    }

    /**
     * Muestra el formulario inicial.
     */
    public function index()
    {
        return view('migrar_contables.index');
    }

    /**
     * Ejecuta el proceso de migración.
     */
    public function ejecutar(Request $request)
    {
        $request->validate([
            'ip_origen'      => 'required|string',
            'bd_origen'      => 'required|string',
            'ip_destino'     => 'required|string',
            'bd_destino'     => 'required|string',
            'fecha_inicial'  => 'required|date',
            'fecha_final'    => 'required|date|after_or_equal:fecha_inicial',
        ]);

        $ipOrigen     = $request->ip_origen;
        $bdOrigen     = $request->bd_origen;
        $ipDestino    = $request->ip_destino;
        $bdDestino    = $request->bd_destino;
        $fechaInicial = $request->fecha_inicial;
        $fechaFinal   = $request->fecha_final;
        $incluirCmpIn = $request->boolean('incluir_cmp_in');

        // Establecer conexiones
        $this->conectar('conn_origen',  $ipOrigen,  $bdOrigen);
        $this->conectar('conn_destino', $ipDestino, $bdDestino);

        $resumen = [];

        // ── ACTIVOS ─────────────────────────────────────────────────────────
        $queryActivos = DB::connection('conn_origen')
            ->table('activos')
            ->whereBetween('FCOMPRA', [$fechaInicial, $fechaFinal]);

        if (!$incluirCmpIn) {
            $queryActivos->where('CBTE', 'NOT LIKE', 'IN%');
        }

        // Omitir campo REGISTRO
        $activos = $queryActivos->get()->map(function ($row) {
            $arr = (array) $row;
            unset($arr['REGISTRO']);
            return $arr;
        })->toArray();

        $eliminadosActivos = DB::connection('conn_destino')
            ->table('activos')
            ->whereBetween('FCOMPRA', [$fechaInicial, $fechaFinal])
            ->delete();

        $insertadosActivos = 0;
        foreach (array_chunk($activos, 200) as $lote) {
            DB::connection('conn_destino')->table('activos')->insert($lote);
            $insertadosActivos += count($lote);
        }

        $resumen[] = [
            'tabla'      => 'activos',
            'eliminados' => $eliminadosActivos,
            'insertados' => $insertadosActivos,
        ];

        // ── GLOSA ────────────────────────────────────────────────────────────
        $queryGlosa = DB::connection('conn_origen')
            ->table('glosa')
            ->whereBetween('FECHA', [$fechaInicial, $fechaFinal]);

        if (!$incluirCmpIn) {
            $queryGlosa->where('NUMERO', 'NOT LIKE', 'IN%');
        }

        $glosas = $queryGlosa->get()->map(fn($r) => (array) $r)->toArray();

        $eliminadosGlosa = DB::connection('conn_destino')
            ->table('glosa')
            ->whereBetween('FECHA', [$fechaInicial, $fechaFinal])
            ->delete();

        $insertadosGlosa = 0;
        foreach (array_chunk($glosas, 200) as $lote) {
            DB::connection('conn_destino')->table('glosa')->insert($lote);
            $insertadosGlosa += count($lote);
        }

        $resumen[] = [
            'tabla'      => 'glosa',
            'eliminados' => $eliminadosGlosa,
            'insertados' => $insertadosGlosa,
        ];

        // ── CONTA ────────────────────────────────────────────────────────────
        $queryConta = DB::connection('conn_origen')
            ->table('conta')
            ->whereBetween('FECHA', [$fechaInicial, $fechaFinal]);

        if (!$incluirCmpIn) {
            $queryConta->where('CBTE', 'NOT LIKE', 'IN%');
        }

        $contas = $queryConta->get()->map(function ($r) {
            $arr = (array) $r;
            unset($arr['ID']);   // se omite para que destino lo genere como autoincrement
            return $arr;
        })->toArray();

        $eliminadosConta = DB::connection('conn_destino')
            ->table('conta')
            ->whereBetween('FECHA', [$fechaInicial, $fechaFinal])
            ->delete();

        $insertadosConta = 0;
        foreach (array_chunk($contas, 200) as $lote) {
            DB::connection('conn_destino')->table('conta')->insert($lote);
            $insertadosConta += count($lote);
        }

        $resumen[] = [
            'tabla'      => 'conta',
            'eliminados' => $eliminadosConta,
            'insertados' => $insertadosConta,
        ];

        return view('migrar_contables.resultado', compact(
            'resumen',
            'ipOrigen',
            'ipDestino',
            'bdOrigen',
            'bdDestino',
            'fechaInicial',
            'fechaFinal',
            'incluirCmpIn'
        ));
    }
}
