<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class MigrarInvController extends Controller
{
    // ── Conexión dinámica ────────────────────────────────────────────────────
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

    // ── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Inserta registros en lotes en inv_destino, omitiendo el campo REGISTRO.
     */
    private function insertarLotes(string $tabla, array $registros): int
    {
        $limpios = array_map(function ($row) {
            $arr = (array) $row;
            unset($arr['REGISTRO']);
            unset($arr['ORDEN']);   // campo autonumérico, se regenera en destino
            return $arr;
        }, $registros);

        $insertados = 0;
        foreach (array_chunk($limpios, 200) as $lote) {
            DB::connection('inv_destino')->table($tabla)->insert($lote);
            $insertados += count($lote);
        }
        return $insertados;
    }

    /**
     * Elimina registros en destino por rango de fechas.
     */
    private function eliminarDestino(string $tabla, string $campoFecha, string $fi, string $ff): int
    {
        return DB::connection('inv_destino')
            ->table($tabla)
            ->whereBetween($campoFecha, [$fi, $ff])
            ->delete();
    }

    // ── Formulario ───────────────────────────────────────────────────────────
    public function index()
    {
        return view('migrar_inv.index');
    }

    // ── Proceso ──────────────────────────────────────────────────────────────
    public function ejecutar(Request $request)
    {
        $request->validate([
            'ip_origen'     => 'required|string',
            'bd_origen'     => 'required|string',
            'ip_destino'    => 'required|string',
            'bd_destino'    => 'required|string',
            'fecha_inicial' => 'required|date',
            'fecha_final'   => 'required|date|after_or_equal:fecha_inicial',
            'tipo_base'     => 'required|in:SUMINISTROS,PRODUCTO_TERMINADO',
            'proveedor'     => 'nullable|string',
            'caja'          => 'nullable|string|max:1',
        ]);

        $fi        = $request->fecha_inicial;
        $ff        = $request->fecha_final;
        $tipo      = $request->tipo_base;
        $excluirIn = $request->boolean('excluir_in');
        $proveedor = strtoupper(trim($request->proveedor ?? ''));
        $caja      = $request->caja ?? '';

        $this->conectar('inv_origen',  $request->ip_origen,  $request->bd_origen);
        $this->conectar('inv_destino', $request->ip_destino, $request->bd_destino);

        $resumen = $tipo === 'SUMINISTROS'
            ? $this->migrarSuministros($fi, $ff, $excluirIn)
            : $this->migrarProductoTerminado($fi, $ff, $proveedor, $caja);

        return view('migrar_inv.resultado', compact(
            'resumen',
            'tipo',
            'fi',
            'ff',
            'excluirIn',
            'proveedor',
            'caja'
        ))->with([
            'ipOrigen'  => $request->ip_origen,
            'bdOrigen'  => $request->bd_origen,
            'ipDestino' => $request->ip_destino,
            'bdDestino' => $request->bd_destino,
        ]);
    }

    // ── SUMINISTROS ──────────────────────────────────────────────────────────
    private function migrarSuministros(string $fi, string $ff, bool $excluirIn): array
    {
        $resumen = [];

        // ── recep (cabecera) ─────────────────────────────────────────────────
        $qRecep = DB::connection('inv_origen')->table('recep')
            ->whereBetween('RFECHA', [$fi, $ff]);
        if ($excluirIn) {
            $qRecep->where('RDOCUM', 'NOT LIKE', 'IN%');
        }
        $recep       = $qRecep->get()->toArray();
        $documsRecep = array_column(array_map('get_object_vars', $recep), 'RDOCUM');

        // ORDEN: primero detalle, luego cabecera (por si hay FK)
        // ── recep1 (detalle) — DELETE antes que cabecera ─────────────────────
        $elimR1 = DB::connection('inv_destino')->table('recep1')
            ->whereIn('RDOCUM', function ($sub) use ($fi, $ff) {
                $sub->select('RDOCUM')->from('recep')
                    ->whereBetween('RFECHA', [$fi, $ff]);
            })->delete();

        $elim = $this->eliminarDestino('recep', 'RFECHA', $fi, $ff);

        // INSERT cabecera
        $ins = $this->insertarLotes('recep', $recep);
        $resumen[] = ['tabla' => 'recep (cabecera)', 'eliminados' => $elim, 'insertados' => $ins];

        // INSERT detalle
        $recep1 = [];
        if (!empty($documsRecep)) {
            $recep1 = DB::connection('inv_origen')->table('recep1')
                ->whereIn('RDOCUM', $documsRecep)
                ->get()->toArray();
        }
        $insR1 = $this->insertarLotes('recep1', $recep1);
        $resumen[] = ['tabla' => 'recep1 (detalle)', 'eliminados' => $elimR1, 'insertados' => $insR1];

        // ── entregas (cabecera) ──────────────────────────────────────────────
        $qEntregas = DB::connection('inv_origen')->table('entregas')
            ->whereBetween('EFECHA', [$fi, $ff]);
        if ($excluirIn) {
            $qEntregas->where('EDOCUM', 'NOT LIKE', 'IN%');
        }
        $entregas       = $qEntregas->get()->toArray();
        $documsEntregas = array_column(array_map('get_object_vars', $entregas), 'EDOCUM');

        // DELETE detalle antes que cabecera
        $elimE1 = DB::connection('inv_destino')->table('entregas1')
            ->whereIn('EDOCUM', function ($sub) use ($fi, $ff) {
                $sub->select('EDOCUM')->from('entregas')
                    ->whereBetween('EFECHA', [$fi, $ff]);
            })->delete();

        $elimE = $this->eliminarDestino('entregas', 'EFECHA', $fi, $ff);

        // INSERT cabecera
        $insE = $this->insertarLotes('entregas', $entregas);
        $resumen[] = ['tabla' => 'entregas (cabecera)', 'eliminados' => $elimE, 'insertados' => $insE];

        // INSERT detalle
        $entregas1 = [];
        if (!empty($documsEntregas)) {
            $entregas1 = DB::connection('inv_origen')->table('entregas1')
                ->whereIn('EDOCUM', $documsEntregas)
                ->get()->toArray();
        }
        $insE1 = $this->insertarLotes('entregas1', $entregas1);
        $resumen[] = ['tabla' => 'entregas1 (detalle)', 'eliminados' => $elimE1, 'insertados' => $insE1];

        return $resumen;
    }

    // ── PRODUCTO TERMINADO ───────────────────────────────────────────────────
    private function migrarProductoTerminado(string $fi, string $ff, string $proveedor, string $caja): array
    {
        $resumen = [];

        // ── recep (cabecera) ─────────────────────────────────────────────────
        $qRecep = DB::connection('inv_origen')->table('recep')
            ->whereBetween('RFECHA', [$fi, $ff]);
        if ($proveedor !== '') {
            $qRecep->where('PROCODIGO', 'LIKE', $proveedor . '%');
        }
        $recep       = $qRecep->get()->toArray();
        $documsRecep = array_column(array_map('get_object_vars', $recep), 'RDOCUM');

        // DELETE detalle antes que cabecera
        $elimR1 = DB::connection('inv_destino')->table('recep1')
            ->whereIn('RDOCUM', function ($sub) use ($fi, $ff) {
                $sub->select('RDOCUM')->from('recep')
                    ->whereBetween('RFECHA', [$fi, $ff]);
            })->delete();

        $elimR = $this->eliminarDestino('recep', 'RFECHA', $fi, $ff);

        // INSERT cabecera
        $insR = $this->insertarLotes('recep', $recep);
        $resumen[] = ['tabla' => 'recep (cabecera)', 'eliminados' => $elimR, 'insertados' => $insR];

        // INSERT detalle
        $recep1 = [];
        if (!empty($documsRecep)) {
            $qRecep1 = DB::connection('inv_origen')->table('recep1')
                ->whereIn('RDOCUM', $documsRecep);
            if ($proveedor !== '') {
                $qRecep1->where('PROCODIGO', 'LIKE', $proveedor . '%');
            }
            $recep1 = $qRecep1->get()->toArray();
        }
        $insR1 = $this->insertarLotes('recep1', $recep1);
        $resumen[] = ['tabla' => 'recep1 (detalle)', 'eliminados' => $elimR1, 'insertados' => $insR1];

        // ── ventas (cabecera) ────────────────────────────────────────────────
        $qVentas = DB::connection('inv_origen')->table('ventas')
            ->whereBetween('VFECHA', [$fi, $ff]);
        if ($caja !== '') {
            $qVentas->where('VCAJA', 'NOT LIKE', '%' . $caja);
        }
        $ventas       = $qVentas->get()->toArray();
        $documsVentas = array_column(array_map('get_object_vars', $ventas), 'VDOCUMA');

        // DELETE detalle antes que cabecera
        $elimV1 = DB::connection('inv_destino')->table('ventas1')
            ->whereIn('VDOCUMA', function ($sub) use ($fi, $ff) {
                $sub->select('VDOCUMA')->from('ventas')
                    ->whereBetween('VFECHA', [$fi, $ff]);
            })->delete();

        $elimV = $this->eliminarDestino('ventas', 'VFECHA', $fi, $ff);

        // INSERT cabecera
        $insV = $this->insertarLotes('ventas', $ventas);
        $resumen[] = ['tabla' => 'ventas (cabecera)', 'eliminados' => $elimV, 'insertados' => $insV];

        // INSERT detalle
        $ventas1 = [];
        if (!empty($documsVentas)) {
            $qVentas1 = DB::connection('inv_origen')->table('ventas1')
                ->whereIn('VDOCUMA', $documsVentas);
            if ($caja !== '') {
                $qVentas1->where('VCAJA', 'NOT LIKE', '%' . $caja);
            }
            $ventas1 = $qVentas1->get()->toArray();
        }
        $insV1 = $this->insertarLotes('ventas1', $ventas1);
        $resumen[] = ['tabla' => 'ventas1 (detalle)', 'eliminados' => $elimV1, 'insertados' => $insV1];

        return $resumen;
    }
}
