<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class FlujoEfectivoController extends Controller
{
    private const CONN = 'contabilidad_flujo';

    public function index(Request $request)
    {
        $filtros = $this->filtros($request);

        return view('flujo_efectivo.index', [
            'filtros' => $filtros,
            'reporte' => null,
            'catalogoCuentas' => $this->cuentasEfectivo(),
        ]);
    }

    public function generar(Request $request)
    {
        $request->validate([
            'fecha_inicial' => ['required', 'date'],
            'fecha_final' => ['required', 'date', 'after_or_equal:fecha_inicial'],
            'periodicidad' => ['required', 'in:diario,semanal,mensual'],
            'incluir_in' => ['nullable', 'boolean'],
        ]);

        $filtros = $this->filtros($request);

        try {
            $reporte = $this->calcularReporte($filtros);
        } catch (\Throwable $e) {
            return back()->withInput()->withErrors([
                'reporte' => 'No se pudo generar el reporte: ' . $e->getMessage(),
            ]);
        }

        return view('flujo_efectivo.index', [
            'filtros' => $filtros,
            'reporte' => $reporte,
            'catalogoCuentas' => $this->cuentasEfectivo(),
        ]);
    }

    private function filtros(Request $request): array
    {
        return [
            'fecha_inicial' => $request->input('fecha_inicial', now()->startOfMonth()->format('Y-m-d')),
            'fecha_final' => $request->input('fecha_final', now()->format('Y-m-d')),
            'periodicidad' => $request->input('periodicidad', 'mensual'),
            'incluir_in' => $request->boolean('incluir_in'),
        ];
    }

    private function conectar(): void
    {
        Config::set('database.connections.' . self::CONN, [
            'driver' => 'mysql',
            'host' => env('DB_FABOCE_HOST', '10.0.1.204'),
            'port' => env('DB_FABOCE_PORT', 3306),
            'database' => 'sisconconsolidado2026',
            'username' => env('SIMEC_DB_USERNAME'),
            'password' => env('SIMEC_DB_PASSWORD'),
            'charset' => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix' => '',
            'strict' => false,
        ]);

        DB::purge(self::CONN);
        DB::reconnect(self::CONN);
    }

    private function cuentasEfectivo(): array
    {
        return [
            '1111' => 'Caja',
            '1112' => 'Caja chica',
            '1113' => 'Bancos',
            '1114' => 'Cuentas de ahorro',
            '1115' => 'Depósitos a plazo / inversiones',
            '1116' => 'Retenciones judiciales bancarias',
        ];
    }

    private function calcularReporte(array $filtros): array
    {
        $this->conectar();

        $conn = DB::connection(self::CONN);
        $prefijos = array_keys($this->cuentasEfectivo());

        $query = $conn->table('conta as c')
            ->select([
                'c.FECHA',
                'c.CBTE',
                'c.NCTA',
                'c.DETALLE',
                'c.DEBE',
                'c.HABER',
                'c.DEBEUS',
                'c.HABERUS',
                'c.PROYECTO',
                'c.FINANCIA',
                'c.ACTIVIDAD',
            ])
            ->whereBetween('c.FECHA', [$filtros['fecha_inicial'], $filtros['fecha_final']])
            ->where(function ($q) use ($prefijos) {
                foreach ($prefijos as $prefijo) {
                    $q->orWhere('c.NCTA', 'like', $prefijo . '%');
                }
            });

        if (!$filtros['incluir_in']) {
            $query->where('c.CBTE', 'not like', 'IN%');
        }

        $movimientos = $query
            ->orderBy('c.FECHA')
            ->orderBy('c.CBTE')
            ->orderBy('c.NCTA')
            ->get();

        $apertura = $conn->table('conta')
            ->where('CBTE', '2604T001')
            ->selectRaw('
                COALESCE(SUM(CASE WHEN NCTA LIKE "1111%" OR NCTA LIKE "1112%" OR NCTA LIKE "1113%" OR NCTA LIKE "1114%" THEN DEBE - HABER ELSE 0 END),0) AS disponible,
                COALESCE(SUM(CASE WHEN NCTA LIKE "1115%" THEN DEBE - HABER ELSE 0 END),0) AS inversiones,
                COALESCE(SUM(CASE WHEN NCTA LIKE "1116%" THEN DEBE - HABER ELSE 0 END),0) AS restringido
            ')
            ->first();

        $montoApertura = (float) (($apertura->disponible ?? 0) + ($apertura->inversiones ?? 0) + ($apertura->restringido ?? 0));

        $detallado = $this->clasificarMovimientos($movimientos);
        $periodos = $this->agruparPeriodos($detallado, $filtros['periodicidad']);
        $flujoNeto = (float) $detallado->sum('flujo_neto');

        return [
            'saldo_inicial' => $montoApertura,
            'saldo_final' => $montoApertura + $flujoNeto,
            'ingresos' => (float) $detallado->where('tipo_flujo', 'INGRESO')->sum('importe'),
            'egresos' => (float) $detallado->where('tipo_flujo', 'EGRESO')->sum('importe'),
            'transferencias' => (float) $detallado->where('tipo_flujo', 'TRANSFERENCIA')->sum('importe'),
            'sin_clasificar' => (float) $detallado->where('tipo_flujo', 'PENDIENTE')->sum('importe'),
            'movimientos' => $detallado,
            'periodos' => $periodos,
            'cuentas' => $this->resumenCuentas($detallado),
            'parametros' => $filtros,
            'apertura_detalle' => [
                'disponible' => (float) ($apertura->disponible ?? 0),
                'inversiones' => (float) ($apertura->inversiones ?? 0),
                'restringido' => (float) ($apertura->restringido ?? 0),
            ],
        ];
    }

    private function clasificarMovimientos($movimientos)
    {
        $cuentasEfectivo = $this->cuentasEfectivo();

        return $movimientos->map(function ($m) use ($cuentasEfectivo) {
            $debe = (float) ($m->DEBE ?? 0);
            $haber = (float) ($m->HABER ?? 0);
            $importe = $debe > 0 ? $debe : $haber;
            $esDebe = $debe > 0;

            $ncta = (string) $m->NCTA;
            $detalle = strtoupper((string) ($m->DETALLE ?? ''));

            $grupo = null;
            foreach ($cuentasEfectivo as $prefijo => $nombre) {
                if (str_starts_with($ncta, $prefijo)) {
                    $grupo = $prefijo;
                    break;
                }
            }

            $m->importe = $importe;
            $m->cuenta_efectivo = $grupo ? ($cuentasEfectivo[$grupo] ?? $grupo) : 'Sin cuenta de efectivo';

            $transferencia = str_contains($detalle, 'TRANSF')
                || str_contains($detalle, 'TRANSFER')
                || str_contains($detalle, 'DEP. EN TRANSITO')
                || str_contains($detalle, 'DEPOSITO EN TRANSITO')
                || str_contains($detalle, 'DEP EN TRANSITO');

            if ($transferencia) {
                $m->tipo_flujo = 'TRANSFERENCIA';
                $m->categoria_flujo = 'Transferencia interna / tránsito';
                $m->flujo_neto = 0;
                return $m;
            }

            if (!$grupo || $importe <= 0) {
                $m->tipo_flujo = 'PENDIENTE';
                $m->categoria_flujo = 'Pendiente de clasificación';
                $m->flujo_neto = 0;
                return $m;
            }

            if ($esDebe) {
                $m->tipo_flujo = 'INGRESO';
                $m->categoria_flujo = $this->categoriaIngreso($ncta);
                $m->flujo_neto = $importe;
            } else {
                $m->tipo_flujo = 'EGRESO';
                $m->categoria_flujo = $this->categoriaEgreso($ncta);
                $m->flujo_neto = -$importe;
            }

            return $m;
        })->values();
    }

    private function categoriaIngreso(string $ncta): string
    {
        return match (true) {
            str_starts_with($ncta, '1121') => 'Cobranza de clientes',
            str_starts_with($ncta, '212') => 'Financiamiento / préstamos',
            default => 'Otros ingresos',
        };
    }

    private function categoriaEgreso(string $ncta): string
    {
        return match (true) {
            str_starts_with($ncta, '2111') => 'Materia prima / proveedores',
            str_starts_with($ncta, '2113') => 'Suministros',
            str_starts_with($ncta, '2117') => 'Otros proveedores',
            str_starts_with($ncta, '2131') => 'Impuestos',
            str_starts_with($ncta, '214') || str_starts_with($ncta, '2215') => 'Personal',
            str_starts_with($ncta, '212') => 'Financiamiento / préstamos',
            str_starts_with($ncta, '6') => 'Otros gastos / resultados',
            default => 'Otros egresos',
        };
    }

    private function agruparPeriodos($movimientos, string $periodicidad)
    {
        return $movimientos
            ->filter(fn ($m) => in_array($m->tipo_flujo, ['INGRESO', 'EGRESO'], true))
            ->groupBy(function ($m) use ($periodicidad) {
                $fecha = \Carbon\Carbon::parse($m->FECHA);

                return match ($periodicidad) {
                    'diario' => $fecha->format('Y-m-d'),
                    'semanal' => $fecha->startOfWeek()->format('Y-m-d'),
                    default => $fecha->format('Y-m'),
                };
            })
            ->sortKeys()
            ->map(function ($items, $periodo) {
                $ingresos = (float) $items->where('tipo_flujo', 'INGRESO')->sum('importe');
                $egresos = (float) $items->where('tipo_flujo', 'EGRESO')->sum('importe');

                return [
                    'periodo' => $periodo,
                    'ingresos' => $ingresos,
                    'egresos' => $egresos,
                    'neto' => $ingresos - $egresos,
                ];
            })->values();
    }

    private function resumenCuentas($movimientos)
    {
        return $movimientos
            ->groupBy('cuenta_efectivo')
            ->map(function ($items, $cuenta) {
                return [
                    'cuenta' => $cuenta,
                    'ingresos' => (float) $items->where('tipo_flujo', 'INGRESO')->sum('importe'),
                    'egresos' => (float) $items->where('tipo_flujo', 'EGRESO')->sum('importe'),
                    'transferencias' => (float) $items->where('tipo_flujo', 'TRANSFERENCIA')->sum('importe'),
                ];
            })->values();
    }
}
