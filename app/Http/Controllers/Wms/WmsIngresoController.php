<?php

namespace App\Http\Controllers\Wms;

use App\Http\Controllers\Controller;
use App\Models\WmsConfigPallet;
use App\Models\WmsEntregaProduccion;
use App\Models\WmsHu;
use App\Models\WmsIngreso;
use App\Services\PalletCorrelativoService;
use App\Services\WmsContextService;
use App\Services\WmsUbicacionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class WmsIngresoController extends Controller
{
    public function __construct(
        private PalletCorrelativoService $palletService,
        private WmsContextService $context,
        private WmsUbicacionService $ubicacionService
    ) {
    }

    public function create()
    {
        $almacen = $this->context->almacen();

        return view('wms.ingresos.create', compact('almacen'));
    }

    /**
     * Nueva etapa: recupera las entregas que ya fueron paletizadas.
     * No modifica el flujo historico de ingreso; prepara la recuperacion
     * del documento HU para que el usuario solo complete ubicaciones.
     */
    public function paletizados()
    {
        return view('wms.ingresos.paletizados');
    }

    public function buscarPaletizados(Request $request)
    {
        $almacen = $this->context->almacen();
        $search = trim((string) $request->query('q'));

        $entregas = WmsEntregaProduccion::query()
            ->with('documento')
            ->where('almacen_id', $almacen->id)
            ->whereIn('estado', ['PALETIZADA'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($sub) use ($search) {
                    $sub->where('folio_fisico', 'like', "%{$search}%")
                        ->orWhere('origen', 'like', "%{$search}%")
                        ->orWhere('documento_id', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('id')
            ->limit(30)
            ->get();

        return response()->json([
            'results' => $entregas->map(function (WmsEntregaProduccion $entrega) {
                return [
                    'id' => $entrega->id,
                    'text' => sprintf(
                        '%s · Folio %s · %s · %s',
                        $entrega->documento?->id_documento ?? 'SIN DOCUMENTO',
                        $entrega->folio_fisico ?? '—',
                        $entrega->origen ?? '—',
                        optional($entrega->fecha_entrega)->format('d/m/Y')
                    ),
                    'documento' => $entrega->documento?->id_documento,
                    'folio_fisico' => $entrega->folio_fisico,
                    'origen' => $entrega->origen,
                    'fecha' => optional($entrega->fecha_entrega)->format('d/m/Y'),
                ];
            })->values(),
        ]);
    }

    public function detallePaletizado(WmsEntregaProduccion $entrega)
    {
        $this->validarAlmacenEntrega($entrega);

        if (!in_array($entrega->estado, ['PALETIZADA', 'UBICADA'], true)) {
            return response()->json([
                'message' => 'La entrega todavía no está disponible para ingreso desde HU.',
            ], 422);
        }

        $entrega->load([
            'documento',
            'almacen',
            'hu' => fn ($query) => $query
                ->with('detalles')
                ->orderBy('id'),
        ]);

        return response()->json([
            'entrega' => [
                'id' => $entrega->id,
                'documento' => $entrega->documento?->id_documento,
                'folio_fisico' => $entrega->folio_fisico,
                'origen' => $entrega->origen,
                'fecha' => optional($entrega->fecha_entrega)->format('d/m/Y'),
                'estado' => $entrega->estado,
                'almacen' => $entrega->almacen?->codigo,
            ],
            'hus' => $entrega->hu->map(function (WmsHu $hu) {
                return [
                    'id' => $hu->id,
                    'numero' => $hu->numero,
                    'formato' => $hu->formato,
                    'tipo' => $hu->tipo,
                    'capacidad' => (int) $hu->capacidad_estandar,
                    'cantidad' => (int) $hu->cantidad_total,
                    'estado' => $hu->estado,
                    'ubicacion_id' => $hu->ubicacion_id,
                    'ubicacion' => $hu->ubicacion?->codigo,
                    'detalles' => $hu->detalles->map(fn ($detalle) => [
                        'codigo' => $detalle->codigo,
                        'lote' => $detalle->lote,
                        'formato' => $detalle->formato,
                        'cantidad' => (int) $detalle->cantidad,
                    ])->values(),
                ];
            })->values(),
        ]);
    }

    /**
     * Registra unicamente la ubicacion de los HUs ya creados por paletizacion.
     * No crea ni modifica registros en wms_ingresos.
     */
    public function ubicarPaletizado(Request $request, WmsEntregaProduccion $entrega)
    {
        $this->validarAlmacenEntrega($entrega);

        $validator = Validator::make($request->all(), [
            'hus' => ['required', 'array', 'min:1'],
            'hus.*.id' => ['required', 'integer'],
            'hus.*.galpon' => ['required', 'string', 'max:20'],
            'hus.*.ubicacion' => ['required', 'string', 'max:20'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $almacen = $this->context->almacen();

        DB::transaction(function () use ($data, $entrega, $almacen) {
            $hus = WmsHu::query()
                ->where('entrega_id', $entrega->id)
                ->where('almacen_id', $almacen->id)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($data['hus'] as $item) {
                $hu = $hus->get((int) $item['id']);

                if (!$hu) {
                    throw ValidationException::withMessages([
                        'hus' => ["El HU {$item['id']} no pertenece a la entrega seleccionada."],
                    ]);
                }

                $ubicacion = $this->ubicacionService->validarNormal(
                    $almacen->id,
                    $item['galpon'],
                    $item['ubicacion']
                );

                $hu->update([
                    'ubicacion_id' => $ubicacion->id,
                    'ubicado_at' => now(),
                    'estado' => 'UBICADO',
                    'update_id' => auth()->id(),
                ]);
            }

            $pendientesUbicacion = $hus->filter(fn (WmsHu $hu) => !$hu->ubicacion_id)->isNotEmpty();

            if (!$pendientesUbicacion) {
                $entrega->update([
                    'estado' => 'UBICADA',
                    'update_id' => auth()->id(),
                ]);
            }
        });

        return response()->json([
            'message' => 'Ubicaciones registradas correctamente. Los HUs mantienen sus pallets y cantidades originales.',
        ]);
    }

    public function buscarNotas(Request $request)
    {
        //$q = trim((string) $request->get('q'));
        $q = trim((string) $request->query('q'));

        $notas = DB::connection('sisinvconsolidado2026')
            ->table('recep')
            ->where('PROCODIGO', 'like', 'IP%')
            ->where('AGECODIGO', (int) $this->context->codigo())
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
        $almacen = $this->context->almacen();

        foreach ($data['grupos'] as $idx => $grupo) {
            try {
                $ubicacion = $this->ubicacionService->validarNormal(
                    $almacen->id,
                    $grupo['galpon'],
                    $grupo['ubicacion']
                );
            } catch (ValidationException $e) {
                return response()->json(['errors' => $e->errors()], 422);
            }

            $data['grupos'][$idx]['galpon'] = $ubicacion->galpon->codigo;
            $data['grupos'][$idx]['ubicacion'] = $ubicacion->codigo;
        }

        $yaRegistrado = WmsIngreso::where('rdocum', $data['rdocum'])
            ->select('codigo', 'clote', DB::raw('SUM(cantidad) as total'))
            ->groupBy('codigo', 'clote')
            ->get()
            ->keyBy(fn ($r) => "{$r->codigo}|{$r->clote}");

        foreach ($data['grupos'] as $grupo) {
            foreach ($grupo['items'] as $item) {
                $key = "{$item['codigo']}|" . ($item['clote'] ?? '');
                if (isset($yaRegistrado[$key])) {
                    return response()->json(['errors' => ['general' => ["El producto {$item['codigo']} (lote {$item['clote']}) de esta nota ya fue registrado anteriormente."]]], 422);
                }
            }
        }

        $configs = WmsConfigPallet::pluck('cajas_x_pallet', 'codigo');

        foreach ($data['grupos'] as $grupo) {
            $formatos = collect($grupo['items'])->map(fn ($i) => strtoupper(substr($i['codigo'], 5, 4)))->unique();
            if ($formatos->count() > 1) {
                return response()->json(['errors' => ['general' => ['Un pallet no puede contener productos de distinto formato.']]], 422);
            }
            $limite = $configs[$formatos->first()] ?? null;
            if ($limite) {
                $total = collect($grupo['items'])->sum('cantidad');
                if ($total > $limite) {
                    return response()->json(['errors' => ['general' => ["Un pallet del formato {$formatos->first()} supera el límite de {$limite} cajas (intentado: {$total})."]]], 422);
                }
            }
        }

        $palletsReales = $this->palletService->generarSiguientes($almacen, count($data['grupos']));
        $resumenPallets = [];

        DB::transaction(function () use ($data, $almacen, $palletsReales, &$resumenPallets) {
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
                        'almacen' => $almacen->codigo,
                        'galpon' => $grupo['galpon'],
                        'ubicacion' => $grupo['ubicacion'],
                    ]);
                }
            }
        });

        return response()->json(['message' => 'Ingreso registrado correctamente.', 'pallets' => $resumenPallets]);
    }

    private function validarAlmacenEntrega(WmsEntregaProduccion $entrega): void
    {
        $almacen = $this->context->almacen();

        if ((int) $entrega->almacen_id !== (int) $almacen->id) {
            abort(403, 'La entrega no pertenece al almacén operativo del usuario.');
        }
    }
}
