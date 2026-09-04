<?php

namespace App\Http\Controllers\Wms;

use App\Http\Controllers\Controller;
use App\Models\WmsIngreso;
use App\Models\WmsOrdenTrabajo;
use App\Models\WmsSalida;
use App\Services\LoteExcepcionService;
use App\Services\SaldoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class WmsSalidaController extends Controller
{

    public function __construct(private SaldoService $saldoService, private LoteExcepcionService $loteExcepcionService) {}
    private function getSaldosPorPallet(string $codigo, ?string $clote): array
    {
        return $this->saldoService->calcular(['codigo' => $codigo])
            ->filter(fn($s) => $s['clote'] == $clote)
            ->map(function ($s) {
                $reservado = $this->saldoService->reservadoEnOtsPendientes(
                    $s['codigo'],
                    $s['clote'],
                    $s['pallet'],
                    $s['almacen'],
                    $s['galpon'],
                    $s['ubicacion']
                );

                return [
                    'pallet' => $s['pallet'],
                    'almacen' => $s['almacen'],
                    'galpon' => $s['galpon'],
                    'ubicacion' => $s['ubicacion'],
                    'saldo' => $s['saldo'] - $reservado, // <-- descuenta lo ya reservado en OT pendientes
                ];
            })
            ->filter(fn($s) => $s['saldo'] > 0)
            ->values()->all();
    }

    private function getSaldoPalletActual(string $codigo, ?string $clote, string $pallet, string $almacen, string $galpon, string $ubicacion): int
    {
        return $this->saldoService->saldoDeGrupo($codigo, $clote, $pallet, $almacen, $galpon, $ubicacion);
    }
    public function create()
    {
        return view('wms.salidas.create');
    }

    /**
     * AJAX - Búsqueda de notas de despacho para el Select2.
     * Tabla: log_registro (faboce2026)
     */
    public function buscarNotas(Request $request)
    {
        $q = trim((string) $request->get('q'));

        $notas = DB::connection('faboce2026')
            ->table('log_registro')
            ->whereBetween('tipo_registro', [1, 3])
            ->where('agencia', 110)
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('id', 'like', "%{$q}%")
                        ->orWhere('glosa', 'like', "%{$q}%");
                });
            })
            ->select('id', 'tipo_registro', 'agencia', 'glosa', 'fecha')
            ->orderByDesc('id')
            ->limit(30)
            ->get();

        $resultados = $notas->map(function ($nota) {
            $fechaFormateada = $nota->fecha
                ? \Carbon\Carbon::parse($nota->fecha)->format('d/m/Y')
                : 'S/F';

            return [
                'id' => $nota->id,
                'text' => "Tipo {$nota->tipo_registro} · #{$nota->id} · {$fechaFormateada} · Agencia {$nota->agencia} · {$nota->glosa}",
                'tipo_registro' => $nota->tipo_registro,
                'glosa' => $nota->glosa,
                'fecha' => $nota->fecha,
            ];
        });

        return response()->json(['results' => $resultados]);
    }

    /**
     * AJAX - Detalle de la nota de despacho + ubicaciones con saldo disponible.
     */
    public function detalleNota(string $id)
    {
        // Obtenemos la fecha de la nota para decidir si exige despacho 100% completo
        $nota = DB::connection('faboce2026')
            ->table('log_registro')
            ->where('id', $id)
            ->select('fecha')
            ->first();

        $fechaCorte = \Illuminate\Support\Carbon::parse(config('wms.salida_parcial_fecha_corte'));
        $exigeDespachoCompleto = $nota && \Illuminate\Support\Carbon::parse($nota->fecha)->gte($fechaCorte);

        $detalles = DB::connection('faboce2026')
            ->table('log_registro_detalle')
            ->where('id_registro', $id)
            ->where('cantidad_despacho', '>', 0)
            ->select('id', 'codigo', 'lote', 'cantidad_despacho', 'factura', 'nota', 'tdocum')
            ->get();

        if ($detalles->isEmpty()) {
            return response()->json(['items' => [], 'total_cajas_nota' => 0, 'exige_despacho_completo' => $exigeDespachoCompleto]);
        }

        $totalCajasNota = (int) $detalles->sum('cantidad_despacho');

        $codigos = $detalles->pluck('codigo')->unique()->values();

        $stock = DB::connection('sisinvconsolidado2026')
            ->table('stock')
            ->whereIn('CODIGO', $codigos)
            ->select('CODIGO', 'DESCRIP', 'DESCRIP1')
            ->get()
            ->keyBy('CODIGO');

        // Cantidad ya efectivamente descontada (OT ya chequeada -> wms_salidas real)
        $yaDespachado = WmsSalida::where('id_registro', $id)
            ->select('codigo', 'lote_declarado', DB::raw('SUM(cantidad) as total'))
            ->groupBy('codigo', 'lote_declarado')
            ->get()
            ->keyBy(fn($r) => "{$r->codigo}|{$r->lote_declarado}");

        // NUEVO: cantidad ya comprometida en Órdenes de Trabajo de ESTA nota que aún
        // están pendientes de verificación (todavía no descontaron stock, pero ya
        // "reservaron" ese cupo de la nota y no deben poder duplicarse).
        $reservadoEnOt = \App\Models\WmsOrdenTrabajoDetalle::query()
            ->whereHas('ordenTrabajo', fn($q) => $q->where('estado', 'pendiente')->where('id_registro', $id))
            ->select('codigo', 'lote_declarado', DB::raw('SUM(cantidad) as total'))
            ->groupBy('codigo', 'lote_declarado')
            ->get()
            ->keyBy(fn($r) => "{$r->codigo}|{$r->lote_declarado}");

        $items = [];

        foreach ($detalles as $detalle) {
            $stockInfo = $stock->get($detalle->codigo);
            $descrip = $stockInfo->DESCRIP ?? '';
            $descrip1 = $stockInfo->DESCRIP1 ?? '';

            $key = "{$detalle->codigo}|{$detalle->lote}";

            $procesadoReal = (int) ($yaDespachado[$key]->total ?? 0);
            $reservado = (int) ($reservadoEnOt[$key]->total ?? 0);
            $procesado = $procesadoReal + $reservado;   // <-- ahora considera ambos

            $cantidadOriginal = (int) $detalle->cantidad_despacho;
            $pendiente = $cantidadOriginal - $procesado;

            $saldoLoteDeclarado = $this->loteExcepcionService->stockTotal($detalle->codigo, $detalle->lote);
            $requiereCambioLote = $pendiente > 0 && $saldoLoteDeclarado < $pendiente;
            $cambioLotePermitido = $this->loteExcepcionService->esElegibleParaCambioLote($detalle->lote);
            $cambioLoteBloqueado = $requiereCambioLote && !$cambioLotePermitido;

            $items[] = [
                'id_detalle' => $detalle->id,
                'documento' => $this->resolverDocumento($detalle),
                'codigo' => $detalle->codigo,
                'descripcion' => trim($descrip . ' ' . $descrip1),
                'descrip' => $descrip,
                'descrip1' => $descrip1,
                'clote' => $detalle->lote,
                'fecha_produccion_lote' => optional($this->loteExcepcionService->fechaProduccion($detalle->lote))->format('d/m/Y') ?? 'S/F',
                'cantidad_despacho' => $cantidadOriginal,
                'cantidad_procesada' => $procesado,
                'cantidad_reservada_ot' => $reservado,
                'cantidad_pendiente' => max($pendiente, 0),
                'completo' => $pendiente <= 0,
                'saldo_lote_declarado' => $saldoLoteDeclarado,
                'requiere_cambio_lote' => $requiereCambioLote,
                'cambio_lote_permitido' => $cambioLotePermitido,
                'cambio_lote_bloqueado' => $cambioLoteBloqueado,
                'ubicaciones' => (!$requiereCambioLote && $pendiente > 0)
                    ? $this->getSaldosPorPallet($detalle->codigo, $detalle->lote)
                    : [],
            ];
        }

        return response()->json([
            'items' => $items,
            'total_cajas_nota' => $totalCajasNota,
            'exige_despacho_completo' => $exigeDespachoCompleto,
            'fecha_nota' => $nota ? \Illuminate\Support\Carbon::parse($nota->fecha)->format('d/m/Y') : null,
        ]);
    }

    /** AJAX - Lotes alternativos con stock, excluyendo el lote declarado en la nota. */
    public function lotesAlternativos(Request $request)
    {
        $codigo = $request->get('codigo');
        $loteExcluir = $request->get('lote_excluir');

        return response()->json(['lotes' => $this->loteExcepcionService->lotesAlternativos($codigo, $loteExcluir)]);
    }

    /** AJAX - Pallets con saldo de un código+lote específico (tras elegir el lote sustituto). */
    public function ubicacionesPorLote(Request $request)
    {
        $codigo = $request->get('codigo');
        $clote = $request->get('clote');

        return response()->json(['ubicaciones' => $this->getSaldosPorPallet($codigo, $clote)]);
    }

    /**
     * Determina la etiqueta del documento a despachar según qué campo tiene valor.
     * AJUSTAR nombres de columna (factura/nota/tdocum) si en tu tabla real son distintos.
     */
    private function resolverDocumento(object $detalle): string
    {
        if (!empty($detalle->factura)) {
            return 'F-' . $detalle->factura;
        }
        if (!empty($detalle->nota)) {
            return 'NE-' . $detalle->nota;
        }
        if (!empty($detalle->tdocum)) {
            return 'TR-' . $detalle->tdocum;
        }

        return 'S/D';
    }


    /**
     * Ya NO crea WmsSalida directamente. Crea una Orden de Trabajo pendiente
     * de verificación por el montacarguista.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tipo_registro' => ['required'],
            'id_registro' => ['required'],
            'glosa' => ['nullable', 'string', 'max:150'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.codigo' => ['required', 'string', 'max:30'],
            'lines.*.descrip' => ['nullable', 'string', 'max:60'],
            'lines.*.descrip1' => ['nullable', 'string', 'max:60'],
            'lines.*.lote_declarado' => ['nullable', 'string', 'max:30'],
            'lines.*.clote' => ['nullable', 'string', 'max:30'],
            'lines.*.cantidad_despacho' => ['required', 'integer', 'min:1'],
            // Camino normal (sin cambio de lote, o cambio a UN solo lote): 'salidas'
            'lines.*.salidas' => ['required_without:lines.*.lotes_aplicados', 'array', 'min:1'],
            'lines.*.salidas.*.pallet' => ['required_with:lines.*.salidas', 'string', 'max:30'],
            'lines.*.salidas.*.almacen' => ['nullable', 'string', 'max:10'],
            'lines.*.salidas.*.galpon' => ['required_with:lines.*.salidas', 'string', 'max:20'],
            'lines.*.salidas.*.ubicacion' => ['required_with:lines.*.salidas', 'string', 'max:20'],
            'lines.*.salidas.*.cantidad' => ['required_with:lines.*.salidas', 'integer', 'min:1'],
            // Camino de cambio de lote MÚLTIPLE: 'lotes_aplicados'
            'lines.*.lotes_aplicados' => ['required_without:lines.*.salidas', 'array', 'min:1'],
            'lines.*.lotes_aplicados.*.clote' => ['required_with:lines.*.lotes_aplicados', 'string', 'max:30'],
            'lines.*.lotes_aplicados.*.salidas' => ['required_with:lines.*.lotes_aplicados', 'array', 'min:1'],
            'lines.*.lotes_aplicados.*.salidas.*.pallet' => ['required', 'string', 'max:30'],
            'lines.*.lotes_aplicados.*.salidas.*.almacen' => ['nullable', 'string', 'max:10'],
            'lines.*.lotes_aplicados.*.salidas.*.galpon' => ['required', 'string', 'max:20'],
            'lines.*.lotes_aplicados.*.salidas.*.ubicacion' => ['required', 'string', 'max:20'],
            'lines.*.lotes_aplicados.*.salidas.*.cantidad' => ['required', 'integer', 'min:1'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();

        // --- NUEVO: si la nota exige despacho completo, validar que TODO quede cubierto ---
        $nota = DB::connection('faboce2026')
            ->table('log_registro')
            ->where('id', $data['id_registro'])
            ->select('fecha')
            ->first();

        $fechaCorte = \Illuminate\Support\Carbon::parse(config('wms.salida_parcial_fecha_corte'));
        $exigeDespachoCompleto = $nota && \Illuminate\Support\Carbon::parse($nota->fecha)->gte($fechaCorte);

        if ($exigeDespachoCompleto) {
            $todosLosItems = DB::connection('faboce2026')
                ->table('log_registro_detalle')
                ->where('id_registro', $data['id_registro'])
                ->where('cantidad_despacho', '>', 0)
                ->select('codigo', 'lote', 'cantidad_despacho')
                ->get();

            // Agrupa por código+lote: puede haber varias líneas de la nota con la misma
            // combinación (referenciadas por distintos documentos internos del ERP, como
            // en tu ejemplo TR-...09 y TR-...10).
            $totalPorGrupo = $todosLosItems
                ->groupBy(fn($i) => "{$i->codigo}|{$i->lote}")
                ->map(fn($g) => $g->sum('cantidad_despacho'));

            // Agrupa lo que el usuario asignó AHORA, por la misma clave.
            $asignadoPorGrupo = collect($data['lines'])
                ->groupBy(fn($l) => "{$l['codigo']}|" . ($l['lote_declarado'] ?? $l['clote'] ?? ''))
                ->map(function ($lineas) {
                    return $lineas->sum(function ($l) {
                        if (!empty($l['lotes_aplicados'])) {
                            return collect($l['lotes_aplicados'])->sum(fn($x) => collect($x['salidas'])->sum('cantidad'));
                        }
                        return collect($l['salidas'] ?? [])->sum('cantidad');
                    });
                });

            foreach ($totalPorGrupo as $clave => $totalOriginalGrupo) {
                [$codigoGrupo, $loteGrupo] = explode('|', $clave, 2);

                $yaDespachadoReal = WmsSalida::where('id_registro', $data['id_registro'])
                    ->where('codigo', $codigoGrupo)
                    ->where('lote_declarado', $loteGrupo)
                    ->sum('cantidad');

                $yaReservadoOt = \App\Models\WmsOrdenTrabajoDetalle::query()
                    ->whereHas('ordenTrabajo', fn($q) => $q->where('estado', 'pendiente')->where('id_registro', $data['id_registro']))
                    ->where('codigo', $codigoGrupo)
                    ->where('lote_declarado', $loteGrupo)
                    ->sum('cantidad');

                $pendienteAntes = $totalOriginalGrupo - $yaDespachadoReal - $yaReservadoOt;
                $asignadoAhora = $asignadoPorGrupo[$clave] ?? 0;

                if ($pendienteAntes > 0 && $asignadoAhora < $pendienteAntes) {
                    return response()->json([
                        'errors' => ['general' => [
                            "Esta nota tiene fecha " . \Illuminate\Support\Carbon::parse($nota->fecha)->format('d/m/Y') .
                                " y requiere despacho completo en una sola operación. El producto {$codigoGrupo} (lote {$loteGrupo}) " .
                                "quedaría con " . ($pendienteAntes - $asignadoAhora) . " cajas sin asignar."
                        ]],
                    ], 422);
                }
            }
        }

        foreach ($data['lines'] as $line) {
            $loteDeclarado = $line['lote_declarado'] ?? $line['clote'] ?? null;

            // Cantidad realmente pendiente en este momento (ya despachado + ya reservado en otra OT)
            $yaDespachadoReal = WmsSalida::where('id_registro', $data['id_registro'])
                ->where('codigo', $line['codigo'])
                ->where('lote_declarado', $loteDeclarado)
                ->sum('cantidad');

            $yaReservadoOt = \App\Models\WmsOrdenTrabajoDetalle::query()
                ->whereHas('ordenTrabajo', fn($q) => $q->where('estado', 'pendiente')->where('id_registro', $data['id_registro']))
                ->where('codigo', $line['codigo'])
                ->where('lote_declarado', $loteDeclarado)
                ->sum('cantidad');

            $pendienteReal = $line['cantidad_despacho'] - $yaDespachadoReal - $yaReservadoOt;

            // --- CAMINO: Cambio de Lote MÚLTIPLE ---
            if (!empty($line['lotes_aplicados'])) {
                if (!$this->loteExcepcionService->esElegibleParaCambioLote($loteDeclarado)) {
                    return response()->json([
                        'errors' => ['general' => ["El lote {$loteDeclarado} no admite cambio de lote (producción posterior a la fecha límite permitida)."]],
                    ], 422);
                }

                $totalAsignado = collect($line['lotes_aplicados'])
                    ->sum(fn($l) => collect($l['salidas'])->sum('cantidad'));

                if ($totalAsignado != $pendienteReal) {
                    return response()->json([
                        'errors' => ['general' => ["El código {$line['codigo']}: el cambio de lote debe cubrir exactamente el 100% del pedido pendiente ({$pendienteReal} cajas). Asignado: {$totalAsignado}."]],
                    ], 422);
                }

                foreach ($line['lotes_aplicados'] as $asignacion) {
                    $subtotal = collect($asignacion['salidas'])->sum('cantidad');
                    $validacion = $this->loteExcepcionService->validar($line['codigo'], $asignacion['clote'], $subtotal);

                    if (!$validacion['valido']) {
                        return response()->json(['errors' => ['general' => [$validacion['mensaje']]]], 422);
                    }

                    foreach ($asignacion['salidas'] as $salida) {
                        $saldoReal = $this->getSaldoPalletActual(
                            $line['codigo'],
                            $asignacion['clote'],
                            $salida['pallet'],
                            $salida['almacen'] ?? '110',
                            $salida['galpon'],
                            $salida['ubicacion']
                        );

                        if ($salida['cantidad'] > $saldoReal) {
                            return response()->json([
                                'errors' => ['general' => ["El pallet {$salida['pallet']} ({$line['codigo']}, lote {$asignacion['clote']}) no tiene saldo suficiente. Disponible: {$saldoReal}."]],
                            ], 422);
                        }
                    }
                }

                continue;
            }

            // --- CAMINO: normal, o cambio de lote a UN solo lote (compatibilidad con lo ya existente) ---
            $loteFisico = $line['clote'] ?? null;
            $totalPedido = collect($line['salidas'])->sum('cantidad');

            if ($totalPedido > $pendienteReal) {
                return response()->json([
                    'errors' => ['general' => ["El código {$line['codigo']} (lote {$loteDeclarado}) ya no tiene cupo pendiente en esta nota. Pendiente real: {$pendienteReal}."]],
                ], 422);
            }

            if ($loteFisico !== $loteDeclarado) {
                if (!$this->loteExcepcionService->esElegibleParaCambioLote($loteDeclarado)) {
                    return response()->json([
                        'errors' => ['general' => ["El lote {$loteDeclarado} no admite cambio de lote (producción posterior a la fecha límite permitida)."]],
                    ], 422);
                }

                $validacion = $this->loteExcepcionService->validar($line['codigo'], $loteFisico, $totalPedido);
                if (!$validacion['valido']) {
                    return response()->json(['errors' => ['general' => [$validacion['mensaje']]]], 422);
                }
            }

            foreach ($line['salidas'] as $salida) {
                $saldoReal = $this->getSaldoPalletActual(
                    $line['codigo'],
                    $loteFisico,
                    $salida['pallet'],
                    $salida['almacen'] ?? '110',
                    $salida['galpon'],
                    $salida['ubicacion']
                );

                if ($salida['cantidad'] > $saldoReal) {
                    return response()->json([
                        'errors' => ['general' => ["El pallet {$salida['pallet']} ({$line['codigo']}) no tiene saldo suficiente. Disponible: {$saldoReal}."]],
                    ], 422);
                }
            }
        }

        $ordenTrabajo = null;

        DB::transaction(function () use ($data, &$ordenTrabajo) {
            $ordenTrabajo = WmsOrdenTrabajo::create([
                'tipo_registro' => $data['tipo_registro'],
                'id_registro' => $data['id_registro'],
                'glosa' => $data['glosa'] ?? null,
                'estado' => 'pendiente',
            ]);

            foreach ($data['lines'] as $line) {
                $loteDeclarado = $line['lote_declarado'] ?? $line['clote'] ?? null;

                // --- Cambio de lote MÚLTIPLE ---
                if (!empty($line['lotes_aplicados'])) {
                    foreach ($line['lotes_aplicados'] as $asignacion) {
                        foreach ($asignacion['salidas'] as $salida) {
                            if ($salida['cantidad'] <= 0) continue;

                            $ordenTrabajo->detalles()->create([
                                'pallet' => $salida['pallet'],
                                'codigo' => $line['codigo'],
                                'clote' => $asignacion['clote'],
                                'lote_declarado' => $loteDeclarado,
                                'es_excepcion_lote' => true,
                                'descrip' => $line['descrip'] ?? null,
                                'descrip1' => $line['descrip1'] ?? null,
                                'cantidad' => $salida['cantidad'],
                                'almacen_origen' => $salida['almacen'] ?? '110',
                                'galpon_origen' => $salida['galpon'],
                                'ubicacion_origen' => $salida['ubicacion'],
                            ]);
                        }

                        $this->loteExcepcionService->registrarExcepcion(
                            $data['tipo_registro'],
                            $data['id_registro'],
                            $line['codigo'],
                            $line['descrip'] ?? null,
                            $line['descrip1'] ?? null,
                            $loteDeclarado,
                            $asignacion['clote'],
                            collect($asignacion['salidas'])->sum('cantidad')
                        );
                    }
                    continue;
                }

                // --- Normal / cambio de lote simple ---
                $loteFisico = $line['clote'] ?? null;
                $esExcepcion = $loteFisico !== $loteDeclarado;
                $totalLinea = collect($line['salidas'])->sum('cantidad');

                foreach ($line['salidas'] as $salida) {
                    if ($salida['cantidad'] <= 0) continue;

                    $ordenTrabajo->detalles()->create([
                        'pallet' => $salida['pallet'],
                        'codigo' => $line['codigo'],
                        'clote' => $loteFisico,
                        'lote_declarado' => $loteDeclarado,
                        'es_excepcion_lote' => $esExcepcion,
                        'descrip' => $line['descrip'] ?? null,
                        'descrip1' => $line['descrip1'] ?? null,
                        'cantidad' => $salida['cantidad'],
                        'almacen_origen' => $salida['almacen'] ?? '110',
                        'galpon_origen' => $salida['galpon'],
                        'ubicacion_origen' => $salida['ubicacion'],
                    ]);
                }

                if ($esExcepcion) {
                    $this->loteExcepcionService->registrarExcepcion(
                        $data['tipo_registro'],
                        $data['id_registro'],
                        $line['codigo'],
                        $line['descrip'] ?? null,
                        $line['descrip1'] ?? null,
                        $loteDeclarado,
                        $loteFisico,
                        $totalLinea
                    );
                }
            }
        });

        $hayExcepciones = collect($data['lines'])->contains(function ($l) {
            if (!empty($l['lotes_aplicados'])) return true;
            return ($l['clote'] ?? null) !== ($l['lote_declarado'] ?? $l['clote'] ?? null);
        });

        return response()->json([
            'message' => 'Orden de Trabajo generada correctamente.' . ($hayExcepciones ? ' Se aplicó cambio de lote por excepción en uno o más productos.' : ''),
            'orden_trabajo_id' => $ordenTrabajo->id,
            'hay_excepciones' => $hayExcepciones,
            'ticket_url' => $hayExcepciones
                ? route('wms.salidas.ticket-variacion-lote', ['tipoRegistro' => $data['tipo_registro'], 'idRegistro' => $data['id_registro']])
                : null,
        ]);
    }

    private function getSaldoDisponibleConReserva(string $codigo, ?string $clote, string $pallet, string $almacen, string $galpon, string $ubicacion): int
    {
        $saldoReal = $this->saldoService->saldoDeGrupo($codigo, $clote, $pallet, $almacen, $galpon, $ubicacion);
        $reservado = $this->saldoService->reservadoEnOtsPendientes($codigo, $clote, $pallet, $almacen, $galpon, $ubicacion);

        return $saldoReal - $reservado;
    }

    public function distribucionAutomatica(Request $request)
    {
        $codigo = $request->get('codigo');
        $loteExcluir = $request->get('lote_excluir');
        $cantidad = (int) $request->get('cantidad');

        return response()->json(
            $this->loteExcepcionService->distribuirAutomatico($codigo, $loteExcluir, $cantidad)
        );
    }
}
