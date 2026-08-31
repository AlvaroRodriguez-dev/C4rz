<?php

namespace App\Http\Controllers\Wms;

use App\Http\Controllers\Controller;
use App\Models\WmsConfigPallet;
use App\Models\WmsIngreso;
use App\Services\AjusteCorrelativoService;
use App\Services\PalletCorrelativoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class WmsIngresoAjusteController extends Controller
{
    public function __construct(
        private PalletCorrelativoService $palletService,
        private AjusteCorrelativoService $ajusteService
    ) {}

    public function create()
    {
        return view('wms.ingresos.ajuste');
    }

    /** AJAX - Select2: formatos registrados en CONFIGURAR (para armar pallet MIXTO). */
    public function buscarFormatos(Request $request)
    {
        $q = trim((string) $request->get('q'));

        $formatos = WmsConfigPallet::query()
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('codigo', 'like', "%{$q}%")
                        ->orWhere('descripcion', 'like', "%{$q}%");
                });
            })
            ->orderBy('codigo')
            ->limit(30)
            ->get();

        $resultados = $formatos->map(fn($f) => [
            'id' => $f->codigo,
            'text' => "{$f->codigo} · {$f->descripcion} ({$f->cajas_x_pallet} cajas/pallet)",
            'cajas_x_pallet' => $f->cajas_x_pallet,
        ]);

        return response()->json(['results' => $resultados]);
    }

    /**
     * AJAX - Select2: buscar productos en el catálogo.
     * Si se pasa 'formato', filtra solo productos cuyo CODIGO (posiciones 6-9) coincida.
     */
    public function buscarProductos(Request $request)
    {
        $q = trim((string) $request->get('q'));
        $formato = $request->get('formato');

        $query = DB::connection('sisinvconsolidado2026')
            ->table('stock')
            ->where('CODIGO', 'like', '6C%')   // <-- nuevo: solo códigos que inician con '6C'
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('CODIGO', 'like', "%{$q}%")
                        ->orWhere('DESCRIP', 'like', "%{$q}%")
                        ->orWhere('DESCRIP1', 'like', "%{$q}%");
                });
            })
            ->when($formato, function ($query) use ($formato) {
                $query->whereRaw('UPPER(SUBSTRING(CODIGO, 6, 4)) = ?', [strtoupper($formato)]);
            })
            ->select('CODIGO', 'DESCRIP', 'DESCRIP1')
            ->limit(30);

        $productos = $query->get();

        $resultados = $productos->map(fn($p) => [
            'id' => $p->CODIGO,
            'text' => trim("{$p->CODIGO} · {$p->DESCRIP} {$p->DESCRIP1}"),
            'descrip' => $p->DESCRIP,
            'descrip1' => $p->DESCRIP1,
        ]);

        return response()->json(['results' => $resultados]);
    }

    /**
     * AJAX - Para modo PALLET COMPLETO: dado un código de producto,
     * determina automáticamente su formato y el límite de cajas x pallet.
     */
    public function obtenerLimite(string $codigo)
    {
        $formatoCodigo = strtoupper(substr($codigo, 5, 4));
        $config = WmsConfigPallet::find($formatoCodigo);

        if (!$config) {
            return response()->json([
                'encontrado' => false,
                'formato_codigo' => $formatoCodigo,
                'mensaje' => "El producto no tiene una configuración de Cajas x Pallet registrada (formato {$formatoCodigo}). Regístrala primero en CONFIGURAR.",
            ]);
        }

        return response()->json([
            'encontrado' => true,
            'formato_codigo' => $config->codigo,
            'cajas_x_pallet' => $config->cajas_x_pallet,
        ]);
    }

    /**
     * Guarda UN pallet completo (mixto o completo) en una sola operación atómica.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'motivo' => ['required', 'string', 'max:200'],
            'tipo_pallet' => ['required', 'in:mixto,completo'],
            'formato_codigo' => ['required_if:tipo_pallet,mixto', 'nullable', 'string', 'max:4'],
            'galpon' => ['required', 'string', 'max:20'],
            'ubicacion' => ['required', 'string', 'max:20'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.codigo' => ['required', 'string', 'max:30'],
            'items.*.clote' => ['nullable', 'string', 'max:30'],
            'items.*.cantidad' => ['required', 'integer', 'min:1'],
        ], [
            'formato_codigo.required_if' => 'Debes seleccionar el formato del pallet mixto.',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();

        if ($data['tipo_pallet'] === 'completo' && count($data['items']) > 1) {
            return response()->json(['errors' => ['general' => ['Un pallet completo solo admite un producto.']]], 422);
        }

        // (misma validación de formato y límite que ya teníamos)
        if ($data['tipo_pallet'] === 'mixto') {
            $config = WmsConfigPallet::find(strtoupper($data['formato_codigo']));
            if (!$config) {
                return response()->json(['errors' => ['general' => ['El formato seleccionado no existe.']]], 422);
            }
            $limite = $config->cajas_x_pallet;

            foreach ($data['items'] as $item) {
                $formatoItem = strtoupper(substr($item['codigo'], 5, 4));
                if ($formatoItem !== strtoupper($config->codigo)) {
                    return response()->json([
                        'errors' => ['general' => ["El producto {$item['codigo']} no corresponde al formato {$config->codigo} seleccionado."]],
                    ], 422);
                }
            }
        } else {
            $item = $data['items'][0];
            $formatoCodigo = strtoupper(substr($item['codigo'], 5, 4));
            $config = WmsConfigPallet::find($formatoCodigo);

            if (!$config) {
                return response()->json([
                    'errors' => ['general' => ["El producto no tiene configuración de Cajas x Pallet (formato {$formatoCodigo})."]],
                ], 422);
            }
            $limite = $config->cajas_x_pallet;
        }

        $totalCantidad = collect($data['items'])->sum('cantidad');

        if ($totalCantidad > $limite) {
            return response()->json([
                'errors' => ['general' => ["La cantidad total ({$totalCantidad}) supera el límite del pallet ({$limite} cajas)."]],
            ], 422);
        }

        $codigos = collect($data['items'])->pluck('codigo')->unique()->values();
        $stock = DB::connection('sisinvconsolidado2026')
            ->table('stock')
            ->whereIn('CODIGO', $codigos)
            ->select('CODIGO', 'DESCRIP', 'DESCRIP1')
            ->get()
            ->keyBy('CODIGO');

        $pallet = null;
        $codigoAjuste = null;

        DB::transaction(function () use ($data, $stock, &$pallet, &$codigoAjuste) {
            // El pallet se genera AQUÍ, recién cuando ya se validó todo y se va a guardar
            $pallet = $this->palletService->generarSiguiente();
            $codigoAjuste = $this->ajusteService->generarSiguiente();

            foreach ($data['items'] as $item) {
                $stockInfo = $stock->get($item['codigo']);

                WmsIngreso::create([
                    'rdocum' => $codigoAjuste,
                    'rfecha' => now()->toDateString(),
                    'tipo_ingreso' => 'ajuste',
                    'motivo' => $data['motivo'],
                    'pallet' => $pallet,
                    'codigo' => $item['codigo'],
                    'clote' => $item['clote'] ?? null,
                    'descrip' => $stockInfo->DESCRIP ?? null,
                    'descrip1' => $stockInfo->DESCRIP1 ?? null,
                    'cantidad' => $item['cantidad'],
                    'almacen' => '110',
                    'galpon' => $data['galpon'],
                    'ubicacion' => $data['ubicacion'],
                ]);
            }
        });

        return response()->json([
            'message' => "✅ Pallet N° {$pallet} guardado correctamente (Ajuste {$codigoAjuste}).",
            'pallet' => $pallet,
            'codigo_ajuste' => $codigoAjuste,
        ]);
    }

    /** AJAX - Reserva y devuelve el siguiente número de pallet (para mostrarlo mientras se arma). */
    /*
    public function reservarPallet()
    {
        return response()->json(['pallet' => $this->palletService->generarSiguiente()]);
    } */
}
