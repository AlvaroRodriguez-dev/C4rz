<?php

namespace App\Http\Controllers\Wms;

use App\Http\Controllers\Controller;
use App\Models\WmsIngreso;
use App\Models\WmsReubicacion;
use App\Models\WmsSalida;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class WmsKardexController extends Controller
{
    public function index()
    {
        return view('wms.kardex.index');
    }

    public function lotes(string $codigo)
    {
        $lotes = WmsIngreso::where('codigo', $codigo)
            ->whereNotNull('clote')
            ->distinct()
            ->orderBy('clote')
            ->pluck('clote');

        return response()->json(['results' => $lotes->map(fn($l) => ['id' => $l, 'text' => $l])]);
    }

    public function galpones(Request $request, string $codigo)
    {
        $galpones = WmsIngreso::where('codigo', $codigo)
            ->when($request->get('clote'), fn($q, $v) => $q->where('clote', $v))
            ->distinct()
            ->orderBy('galpon')
            ->pluck('galpon');

        return response()->json(['results' => $galpones->map(fn($g) => ['id' => $g, 'text' => $g])]);
    }

    public function ubicaciones(Request $request, string $codigo)
    {
        $ubicaciones = WmsIngreso::where('codigo', $codigo)
            ->when($request->get('clote'), fn($q, $v) => $q->where('clote', $v))
            ->when($request->get('galpon'), fn($q, $v) => $q->where('galpon', $v))
            ->distinct()
            ->orderBy('ubicacion')
            ->pluck('ubicacion');

        return response()->json(['results' => $ubicaciones->map(fn($u) => ['id' => $u, 'text' => $u])]);
    }

    /**
     * AJAX - Select2: TODOS los pallets donde hubo ingreso del producto, incluyendo
     * los que hoy tienen saldo 0 (para poder auditar su historial completo).
     */
    public function pallets(string $codigo)
    {
        $pallets = WmsIngreso::where('codigo', $codigo)
            ->distinct()
            ->orderBy('pallet')
            ->pluck('pallet');

        return response()->json(['results' => $pallets->map(fn($pl) => ['id' => $pl, 'text' => $pl])]);
    }

    public function reporte(Request $request)
    {
        $codigo = $request->get('codigo');
        $clote = $request->get('clote');
        $galpon = $request->get('galpon');
        $ubicacion = $request->get('ubicacion');
        $pallet = $request->get('pallet');   // <-- nuevo
        $desde = Carbon::parse($request->get('fecha_inicio'))->startOfDay();
        $hasta = Carbon::parse($request->get('fecha_fin'))->endOfDay();

        if (!$codigo || !$request->get('fecha_inicio') || !$request->get('fecha_fin')) {
            return response()->json(['errors' => ['general' => ['Producto y periodo son obligatorios.']]], 422);
        }

        $movimientos = $this->obtenerMovimientos($codigo, $clote, $galpon, $ubicacion, $pallet);

        $anteriores = $movimientos->filter(fn($m) => $m['fecha']->lt($desde));
        $saldoInicial = $anteriores->sum('cantidad');

        $delPeriodo = $movimientos
            ->filter(fn($m) => $m['fecha']->between($desde, $hasta))
            ->sortBy('fecha')
            ->values();

        $saldoCorriente = $saldoInicial;
        $filas = [];

        $filas[] = [
            'fecha' => $desde->format('d/m/Y'),
            'tipo' => 'SALDO INICIAL',
            'documento' => '—',
            'detalle_ubicacion' => '—',
            'clote' => '—',
            'usuario' => '—',
            'entrada' => null,
            'salida' => null,
            'saldo' => $saldoInicial,
        ];

        foreach ($delPeriodo as $m) {
            $saldoCorriente += $m['cantidad'];

            $filas[] = [
                'fecha' => $m['fecha']->format('d/m/Y H:i'),
                'tipo' => $m['tipo'],
                'documento' => $m['documento'],
                'detalle_ubicacion' => $m['detalle_ubicacion'],
                'clote' => $m['clote'] ?: 'S/L',
                'usuario' => $m['usuario'],
                'entrada' => $m['cantidad'] > 0 ? $m['cantidad'] : null,
                'salida' => $m['cantidad'] < 0 ? abs($m['cantidad']) : null,
                'saldo' => $saldoCorriente,
                'contable' => $m['contable'],   // <-- nuevo: se propaga a la fila para pintarla distinto en la vista
            ];
        }

        return response()->json([
            'filas' => $filas,
            'saldo_final' => $saldoCorriente,
            // Solo cuentan movimientos reales (contable = true): ingresos y salidas, NUNCA reubicaciones
            'total_entradas' => $delPeriodo->where('contable', true)->where('cantidad', '>', 0)->sum('cantidad'),
            'total_salidas' => abs($delPeriodo->where('contable', true)->where('cantidad', '<', 0)->sum('cantidad')),
        ]);
    }

    /**
     * Reúne todos los movimientos, con signo (+/-) y fecha.
     * Ahora acepta filtro opcional por PALLET.
     */
    private function obtenerMovimientos(string $codigo, ?string $clote, ?string $galpon, ?string $ubicacion, ?string $pallet): Collection
    {
        $movimientos = collect();

        // --- INGRESOS (siempre suman, cuentan como movimiento real) ---
        WmsIngreso::with('creador:id,name')
            ->where('codigo', $codigo)
            ->when($clote, fn($q, $v) => $q->where('clote', $v))
            ->when($galpon, fn($q, $v) => $q->where('galpon', $v))
            ->when($ubicacion, fn($q, $v) => $q->where('ubicacion', $v))
            ->when($pallet, fn($q, $v) => $q->where('pallet', $v))
            ->get()
            ->each(function (WmsIngreso $r) use (&$movimientos) {
                $esAjuste = $r->tipo_ingreso === 'ajuste';

                $movimientos->push([
                    'fecha' => $r->created_at,
                    'tipo' => $esAjuste ? 'INGRESO (AJUSTE)' : 'INGRESO',
                    'documento' => $esAjuste
                        ? "Ajuste: {$r->rdocum} · Pallet {$r->pallet}" . ($r->motivo ? " · Motivo: {$r->motivo}" : '')
                        : "Nota: {$r->rdocum} · Pallet {$r->pallet}",
                    'detalle_ubicacion' => "Origen: Galpón {$r->galpon} · {$r->ubicacion}",
                    'clote' => $r->clote,
                    'usuario' => $r->creador->name ?? 'N/D',
                    'cantidad' => (int) $r->cantidad,
                    'contable' => true,   // <-- nuevo: cuenta como Entrada real
                ]);
            });

        // --- SALIDAS (siempre restan, cuentan como movimiento real) ---
        WmsSalida::with('creador:id,name')
            ->where('codigo', $codigo)
            ->when($clote, fn($q, $v) => $q->where('clote', $v))
            ->when($galpon, fn($q, $v) => $q->where('galpon', $v))
            ->when($ubicacion, fn($q, $v) => $q->where('ubicacion', $v))
            ->when($pallet, fn($q, $v) => $q->where('pallet', $v))
            ->get()
            ->each(function (WmsSalida $r) use (&$movimientos) {
                $destino = "Nota Tipo {$r->tipo_registro} #{$r->id_registro}" . ($r->glosa ? " · {$r->glosa}" : '');

                $movimientos->push([
                    'fecha' => $r->created_at,
                    'tipo' => 'SALIDA',
                    'documento' => "{$destino} · Pallet {$r->pallet}",
                    'detalle_ubicacion' => "Origen: Galpón {$r->galpon} · {$r->ubicacion} → Destino: {$destino}",
                    'clote' => $r->clote,
                    'usuario' => $r->creador->name ?? 'N/D',
                    'cantidad' => -1 * (int) $r->cantidad,
                    'contable' => true,   // <-- nuevo: cuenta como Salida real
                ]);
            });

        // --- REUBICACIONES: pierna de SALIDA del origen (movimiento interno, NO es salida real) ---
        WmsReubicacion::with('creador:id,name')
            ->where('codigo', $codigo)
            ->when($clote, fn($q, $v) => $q->where('clote', $v))
            ->when($galpon, fn($q, $v) => $q->where('galpon_origen', $v))
            ->when($ubicacion, fn($q, $v) => $q->where('ubicacion_origen', $v))
            ->when($pallet, fn($q, $v) => $q->where('pallet_origen', $v))
            ->get()
            ->each(function (WmsReubicacion $r) use (&$movimientos) {
                $movimientos->push([
                    'fecha' => $r->created_at,
                    'tipo' => 'REUBICACIÓN (salida)',
                    'documento' => "Pallet {$r->pallet_origen} → Pallet {$r->pallet_destino}",
                    'detalle_ubicacion' => "Origen: Galpón {$r->galpon_origen} · {$r->ubicacion_origen} → Destino: Galpón {$r->galpon_destino} · {$r->ubicacion_destino}",
                    'clote' => $r->clote,
                    'usuario' => $r->creador->name ?? 'N/D',
                    'cantidad' => -1 * (int) $r->cantidad,
                    'contable' => false,   // <-- nuevo: NO cuenta como Salida real, es el mismo stock
                ]);
            });

        // --- REUBICACIONES: pierna de ENTRADA al destino (movimiento interno, NO es ingreso real) ---
        WmsReubicacion::with('creador:id,name')
            ->where('codigo', $codigo)
            ->when($clote, fn($q, $v) => $q->where('clote', $v))
            ->when($galpon, fn($q, $v) => $q->where('galpon_destino', $v))
            ->when($ubicacion, fn($q, $v) => $q->where('ubicacion_destino', $v))
            ->when($pallet, fn($q, $v) => $q->where('pallet_destino', $v))
            ->get()
            ->each(function (WmsReubicacion $r) use (&$movimientos) {
                $movimientos->push([
                    'fecha' => $r->created_at,
                    'tipo' => 'REUBICACIÓN (entrada)',
                    'documento' => "Pallet {$r->pallet_origen} → Pallet {$r->pallet_destino}",
                    'detalle_ubicacion' => "Origen: Galpón {$r->galpon_origen} · {$r->ubicacion_origen} → Destino: Galpón {$r->galpon_destino} · {$r->ubicacion_destino}",
                    'clote' => $r->clote,
                    'usuario' => $r->creador->name ?? 'N/D',
                    'cantidad' => (int) $r->cantidad,
                    'contable' => false,   // <-- nuevo: NO cuenta como Entrada real, es el mismo stock
                ]);
            });

        return $movimientos->sortBy('fecha')->values();
    }
}
