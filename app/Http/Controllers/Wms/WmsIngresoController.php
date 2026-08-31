<?php

namespace App\Http\Controllers\Wms;

use App\Http\Controllers\Controller;
use App\Models\WmsConfigPallet;
use App\Models\WmsIngreso;
use App\Services\PalletCorrelativoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class WmsIngresoController extends Controller
{
    public function __construct(private PalletCorrelativoService $palletService)
    {
    }

    public function create()
    {
        return view('wms.ingresos.create');
    }

    public function buscarNotas(Request $request)
    {
        //$q = trim((string) $request->get('q'));
        $q = trim((string) $request->query('q'));

        $notas = DB::connection('sisinvconsolidado2026')
            ->table('recep')
            ->where('PROCODIGO', 'like', 'IP%')
            ->where('AGECODIGO', 110)
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('RDOCUM', 'like', "%{$q}%")
                        ->orWhere('RNOMBRE', 'like', "%{$q}%");
                });
            })
            ->select('RDOCUM', 'RFECHA', 'RNOMBRE')
            ->orderByDesc('RFECHA')
            ->limit(30)
            ->get();

        $resultados = $notas->map(function ($nota) {
            $fecha = \Carbon\Carbon::parse($nota->RFECHA)->format('d/m/Y');

            return [
                'id' => $nota->RDOCUM,
                'text' => "{$nota->RDOCUM} · {$fecha} · {$nota->RNOMBRE}",
                'rfecha' => \Carbon\Carbon::parse($nota->RFECHA)->format('Y-m-d'),
            ];
        });

        return response()->json(['results' => $resultados]);
    }


    /**
     * Devuelve los GRUPOS generados por la nota (uno por eventual pallet),
     * SIN asignar ningún número de pallet real ni de vista previa.
     */
    public function detalleNota(string $rdocum)
    {
        $items = DB::connection('sisinvconsolidado2026')
            ->table('recep1 as r1')
            ->join('stock as s', 's.CODIGO', '=', 'r1.CODIGO')
            ->where('r1.RDOCUM', $rdocum)
            ->select('r1.CODIGO', 'r1.CLOTE', 'r1.RCANTIDAD', 's.DESCRIP', 's.DESCRIP1')
            ->get();

        if ($items->isEmpty()) {
            return response()->json(['rows' => [], 'warning' => null]);
        }

        $totalCajasNota = (int) $items->sum('RCANTIDAD');
        $configs = WmsConfigPallet::pluck('cajas_x_pallet', 'codigo');

        $yaRegistrado = WmsIngreso::where('rdocum', $rdocum)
            ->select('codigo', 'clote', DB::raw('SUM(cantidad) as total'))
            ->groupBy('codigo', 'clote')
            ->get()
            ->keyBy(fn ($r) => "{$r->codigo}|{$r->clote}");

        $rows = [];
        $itemsInfo = [];
        $sinConfig = [];
        $rowId = 0;

        foreach ($items as $item) {
            $codigo = $item->CODIGO;
            $clote = $item->CLOTE;
            $configCodigo = strtoupper(substr($codigo, 5, 4));
            $cajasXPallet = $configs[$configCodigo] ?? null;
            $descripcion = trim($item->DESCRIP . ' ' . $item->DESCRIP1);
            $cantidadTotal = (int) $item->RCANTIDAD;

            $key = "{$codigo}|{$clote}";
            $procesado = (int) ($yaRegistrado[$key]->total ?? 0);
            $pendiente = $cantidadTotal - $procesado;

            $itemsInfo[] = [
                'codigo' => $codigo, 'clote' => $clote,
                'cantidad_original' => $cantidadTotal, 'cantidad_procesada' => $procesado,
                'cantidad_pendiente' => max($pendiente, 0), 'completo' => $pendiente <= 0,
            ];

            if ($pendiente <= 0) {
                continue;
            }

            if (!$cajasXPallet) {
                $sinConfig[] = $configCodigo;

                $rows[] = [
                    'id' => 'g' . (++$rowId),
                    'codigo' => $codigo, 'descripcion' => $descripcion,
                    'descrip' => $item->DESCRIP, 'descrip1' => $item->DESCRIP1,
                    'clote' => $clote, 'cantidad' => $pendiente,
                    'sin_config' => true, 'config_codigo' => $configCodigo, 'limite' => null,
                ];
                continue;
            }

            $palletsCompletos = intdiv($pendiente, $cajasXPallet);
            $saldo = $pendiente % $cajasXPallet;

            for ($i = 1; $i <= $palletsCompletos; $i++) {
                $rows[] = [
                    'id' => 'g' . (++$rowId),
                    'codigo' => $codigo, 'descripcion' => $descripcion,
                    'descrip' => $item->DESCRIP, 'descrip1' => $item->DESCRIP1,
                    'clote' => $clote, 'cantidad' => $cajasXPallet,
                    'sin_config' => false, 'config_codigo' => $configCodigo, 'limite' => $cajasXPallet,
                ];
            }

            if ($saldo > 0) {
                $rows[] = [
                    'id' => 'g' . (++$rowId),
                    'codigo' => $codigo, 'descripcion' => $descripcion,
                    'descrip' => $item->DESCRIP, 'descrip1' => $item->DESCRIP1,
                    'clote' => $clote, 'cantidad' => $saldo,
                    'sin_config' => false, 'config_codigo' => $configCodigo, 'limite' => $cajasXPallet,
                ];
            }
        }

        $warning = null;
        if (!empty($sinConfig)) {
            $warning = 'Los siguientes códigos de formato no tienen configuración registrada: '
                . implode(', ', array_unique($sinConfig));
        }

        return response()->json([
            'rows' => $rows,
            'items_info' => $itemsInfo,
            'warning' => $warning,
            'total_cajas_nota' => $totalCajasNota,
        ]);
    }

    /**
     * Recibe GRUPOS ya fusionados por el usuario (uno o varios items por grupo).
     * Genera UN número de pallet real por grupo, solo aquí.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'rdocum' => ['required', 'string', 'max:20'],
            'rfecha' => ['required', 'date'],
            'grupos' => ['required', 'array', 'min:1'],
            'grupos.*.galpon' => ['required', 'string', 'max:20'],
            'grupos.*.ubicacion' => ['required', 'string', 'max:20'],
            'grupos.*.items' => ['required', 'array', 'min:1'],
            'grupos.*.items.*.codigo' => ['required', 'string', 'max:30'],
            'grupos.*.items.*.clote' => ['nullable', 'string', 'max:30'],
            'grupos.*.items.*.descrip' => ['nullable', 'string', 'max:60'],
            'grupos.*.items.*.descrip1' => ['nullable', 'string', 'max:60'],
            'grupos.*.items.*.cantidad' => ['required', 'integer', 'min:1'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();

        // --- Control de duplicados ---
        $yaRegistrado = WmsIngreso::where('rdocum', $data['rdocum'])
            ->select('codigo', 'clote', DB::raw('SUM(cantidad) as total'))
            ->groupBy('codigo', 'clote')
            ->get()
            ->keyBy(fn ($r) => "{$r->codigo}|{$r->clote}");

        foreach ($data['grupos'] as $grupo) {
            foreach ($grupo['items'] as $item) {
                $key = "{$item['codigo']}|" . ($item['clote'] ?? '');
                if (isset($yaRegistrado[$key])) {
                    return response()->json([
                        'errors' => ['general' => ["El producto {$item['codigo']} (lote {$item['clote']}) de esta nota ya fue registrado anteriormente."]],
                    ], 422);
                }
            }
        }

        // --- Blindaje: formato único y límite de cajas por GRUPO (pallet final) ---
        $configs = WmsConfigPallet::pluck('cajas_x_pallet', 'codigo');

        foreach ($data['grupos'] as $grupo) {
            $formatos = collect($grupo['items'])
                ->map(fn ($i) => strtoupper(substr($i['codigo'], 5, 4)))
                ->unique();

            if ($formatos->count() > 1) {
                return response()->json([
                    'errors' => ['general' => ['Un pallet no puede contener productos de distinto formato.']],
                ], 422);
            }

            $limite = $configs[$formatos->first()] ?? null;

            if ($limite) {
                $total = collect($grupo['items'])->sum('cantidad');
                if ($total > $limite) {
                    return response()->json([
                        'errors' => ['general' => ["Un pallet del formato {$formatos->first()} supera el límite de {$limite} cajas (intentado: {$total})."]],
                    ], 422);
                }
            }
        }

        // --- Asignación real: UN correlativo por grupo, y solo en este momento ---
        $palletsReales = $this->palletService->generarSiguientes(count($data['grupos']));
        $resumenPallets = [];

        DB::transaction(function () use ($data, $palletsReales, &$resumenPallets) {
            foreach ($data['grupos'] as $idx => $grupo) {
                $pallet = $palletsReales[$idx];
                $resumenPallets[] = ['local' => $idx + 1, 'pallet' => $pallet];

                foreach ($grupo['items'] as $item) {
                    WmsIngreso::create([
                        'rdocum' => $data['rdocum'],
                        'rfecha' => $data['rfecha'],
                        'pallet' => $pallet,
                        'codigo' => $item['codigo'],
                        'clote' => $item['clote'] ?? null,
                        'descrip' => $item['descrip'] ?? null,
                        'descrip1' => $item['descrip1'] ?? null,
                        'cantidad' => $item['cantidad'],
                        'almacen' => '110',
                        'galpon' => $grupo['galpon'],
                        'ubicacion' => $grupo['ubicacion'],
                    ]);
                }
            }
        });

        return response()->json([
            'message' => 'Ingreso registrado correctamente.',
            'pallets' => $resumenPallets,
        ]);
    }
}