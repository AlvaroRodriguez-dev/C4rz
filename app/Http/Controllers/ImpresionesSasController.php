<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class ImpresionesSasController extends Controller
{
    public function index()
    {
        return view('impresiones-sas.index');
    }

    public function buscar(Request $request)
    {
        $data = $request->validate([
            'numero_documento' => ['required', 'string', 'max:100'],
        ]);

        $numero = trim($data['numero_documento']);

        $registro = DB::connection('faboce2026')
            ->table('log_registro')
            ->where('id', $numero)
            ->first();

        $detallesRaw = DB::connection('faboce2026')
            ->table('log_registro_detalle')
            ->where('id_registro', $numero)
            ->orderBy('id')
            ->get();

        if (!$registro && $detallesRaw->isEmpty()) {
            return back()
                ->withInput()
                ->with('error', "No se encontraron registros para el documento {$numero}.");
        }

        /*
         * El PDF histórico recibe un objeto normalizado ($cuerpo) y no imprime
         * directamente log_registro_detalle. En esta nueva pantalla hacemos la
         * misma separación: datos de origen -> datos normalizados -> edición.
         *
         * Todo es SOLO lectura.
         */
        $catalogo = $this->obtenerCatalogoProductos($detallesRaw);

        $detalles = $detallesRaw->map(function ($detalle) use ($catalogo) {
            return $this->normalizarDetalle($detalle, $catalogo);
        })->values()->all();

        $logistica = $this->obtenerDatosLogistica($registro);
        $cabecera = $this->normalizarCabecera($registro, $detalles, $logistica);

        Session::put('impresiones_sas', [
            'numero_documento' => $numero,
            'registro' => $registro ? (array) $registro : null,
            'cabecera' => $cabecera,
            'detalles' => $detalles,
            'token' => (string) Str::uuid(),
        ]);

        return redirect()->route('impresiones-sas.resultado');
    }

    /**
     * Diagnóstico temporal y SOLO LECTURA para identificar las tablas/campos
     * que utiliza el SAS histórico. No consulta ni modifica datos de negocio.
     */
    public function diagnosticoFuentes(string $id)
    {
        $db = config('database.connections.faboce2026.database', 'faboce2026');

        $tablasCandidatas = DB::connection('faboce2026')
            ->table('information_schema.tables')
            ->where('table_schema', $db)
            ->where(function ($q) {
                $q->where('table_name', 'like', '%entreg%')
                    ->orWhere('table_name', 'like', '%pendiente%')
                    ->orWhere('table_name', 'like', '%trasp%')
                    ->orWhere('table_name', 'like', '%transito%')
                    ->orWhere('table_name', 'like', '%venta%')
                    ->orWhere('table_name', 'like', '%recep%')
                    ->orWhere('table_name', 'like', '%registro%');
            })
            ->orderBy('table_name')
            ->pluck('table_name');

        $resultado = [];

        foreach ($tablasCandidatas as $tabla) {
            $columnas = DB::connection('faboce2026')
                ->table('information_schema.columns')
                ->where('table_schema', $db)
                ->where('table_name', $tabla)
                ->orderBy('ordinal_position')
                ->get(['column_name', 'data_type']);

            $interes = $columnas->filter(function ($columna) {
                return preg_match(
                    '/(id|doc|edoc|tdoc|fact|nota|codigo|clote|lote|cant|cantidad|entreg|acum|saldo|metro|m2|importe|imp|precio|peso|viaje|flete|agencia|age|empresa|nit|placa|conductor|telefono|descripcion|glosa|fecha)/i',
                    $columna->column_name
                );
            })->values();

            if ($interes->isNotEmpty()) {
                $resultado[] = [
                    'tabla' => $tabla,
                    'columnas_interesantes' => $interes,
                ];
            }
        }

        return response()->json([
            'modo' => 'SOLO_LECTURA',
            'documento' => $id,
            'base' => $db,
            'nota' => 'Este diagnóstico consulta únicamente information_schema. No realiza INSERT, UPDATE ni DELETE.',
            'tablas' => $resultado,
        ]);
    }

    /**
     * Diagnóstico de datos SOLO LECTURA para reconstruir la impresión histórica.
     * No ejecuta INSERT, UPDATE, DELETE ni incrementos.
     */
    /**
     * Segundo diagnóstico SOLO LECTURA: reconstruye las relaciones del registro
     * y sus documentos de origen. No modifica ninguna tabla.
     */
    public function diagnosticoDatos(string $id)
    {
        $db = config('database.connections.faboce2026.database', 'faboce2026');
        $cn = DB::connection('faboce2026');

        $registro = $cn->table('log_registro')->where('id', $id)->first();
        $detalles = $cn->table('log_registro_detalle')
            ->where('id_registro', $id)
            ->orderBy('id')
            ->get();

        $notas = $detalles->flatMap(function ($d) {
            return collect([$d->nota ?? null, $d->factura ?? null, $d->TDOCUM ?? null])
                ->filter(fn ($v) => $v !== null && $v !== '');
        })->unique()->values();

        $ventas = collect();
        $ventas1 = collect();
        if ($notas->isNotEmpty()) {
            $ventas = $cn->table('sasinv_ventas')
                ->where(function ($q) use ($notas) {
                    foreach ($notas as $nota) {
                        $q->orWhere('VDOCUM', $nota)
                            ->orWhere('VDOCUMA', $nota)
                            ->orWhere('VFACTURA', $nota);
                    }
                })->limit(100)->get();

            $ventas1 = $cn->table('sasinv_ventas1')
                ->where(function ($q) use ($notas) {
                    foreach ($notas as $nota) {
                        $q->orWhere('VDOCUM', $nota)->orWhere('VDOCUMA', $nota);
                    }
                })->limit(200)->get();
        }

        // La cabecera nos dio id_relacion = 7678. Inspeccionamos esa relación.
        $relacion = null;
        $flete = collect();
        $relacionColumnas = $cn->table('information_schema.columns')
            ->where('table_schema', $db)
            ->where('table_name', 'log_empresa_camion_conductor_flete')
            ->orderBy('ordinal_position')
            ->pluck('column_name');

        if ($relacionColumnas->contains('id') && $registro && $registro->id_relacion !== null) {
            $relacion = $cn->table('log_empresa_camion_conductor_flete')
                ->where('id', $registro->id_relacion)
                ->first();
        }

        if ($relacion && isset($relacion->id_flete)) {
            $flete = $cn->table('log_flete_detalle')
                ->where('id_flete', $relacion->id_flete)
                ->get();
        }

        // Recuperamos también los registros reales relacionados con el documento.
        // Todo mediante SELECT y usando las claves encontradas en log_registro.
        $datosLogistica = $this->obtenerDatosLogistica($registro);

        // Identificamos otras tablas de logística que puedan contener agencias,
        // empresas, camiones, conductores o relaciones, sin asumir nombres.
        $tablasLogistica = $cn->table('information_schema.tables')
            ->where('table_schema', $db)
            ->where(function ($q) {
                $q->where('table_name', 'like', '%agenc%')
                    ->orWhere('table_name', 'like', '%camion%')
                    ->orWhere('table_name', 'like', '%conductor%')
                    ->orWhere('table_name', 'like', '%empresa%')
                    ->orWhere('table_name', 'like', '%flete%');
            })
            ->orderBy('table_name')
            ->pluck('table_name');

        $estructuraLogistica = [];
        foreach ($tablasLogistica as $tabla) {
            $cols = $cn->table('information_schema.columns')
                ->where('table_schema', $db)
                ->where('table_name', $tabla)
                ->orderBy('ordinal_position')
                ->get(['column_name', 'data_type']);
            $estructuraLogistica[] = ['tabla' => $tabla, 'columnas' => $cols];
        }

        return response()->json([
            'modo' => 'SOLO_LECTURA',
            'documento' => $id,
            'base' => $db,
            'nota' => 'Diagnóstico de datos: únicamente SELECT. No realiza INSERT, UPDATE, DELETE ni incrementos.',
            'log_registro' => $registro,
            'log_registro_detalle' => $detalles,
            'valores_busqueda_notas' => $notas,
            'sasinv_ventas' => $ventas,
            'sasinv_ventas1' => $ventas1,
            'relacion_log_empresa_camion_conductor_flete' => $relacion,
            'columnas_relacion' => $relacionColumnas,
            'log_flete_detalle' => $flete,
            'empresa_transporte' => $datosLogistica['empresa_transporte'] ?? null,
            'camion' => $datosLogistica['camion'] ?? null,
            'conductor' => $datosLogistica['conductor'] ?? null,
            'agencias' => $datosLogistica['agencias'] ?? collect(),
            'flete' => $datosLogistica['flete'] ?? null,
            'flete_detalle_ruta' => $datosLogistica['flete_detalle'] ?? collect(),
            'estructura_tablas_logistica' => $estructuraLogistica,
        ]);
    }

    public function generarPdf()
    {
        $datos = Session::get('impresiones_sas');

        if (!$datos) {
            return redirect()
                ->route('impresiones-sas.index')
                ->with('error', 'La sesión de impresión ha expirado. Realiza nuevamente la búsqueda.');
        }

        $cabecera = $datos['cabecera'] ?? [];
        $detalles = $datos['detalles'] ?? [];

        /*
         * El PDF utiliza exactamente la copia temporal guardada en sesión.
         * No vuelve a consultar ni modificar los registros de faboce2026.
         */
        $totalImpBs = $this->sumarDetalles($detalles, 'impbs');
        $tipoCambio = $this->numero($cabecera['tipo_cambio'] ?? null);
        $totalUsd = ($tipoCambio !== null && $tipoCambio > 0)
            ? $totalImpBs / $tipoCambio
            : null;

        $totales = [
            'facturada' => $this->sumarDetalles($detalles, 'facturada'),
            'acumulada' => $this->sumarDetalles($detalles, 'acumulada'),
            'entregada' => $this->sumarDetalles($detalles, 'entregada'),
            'saldo' => $this->sumarDetalles($detalles, 'saldo'),
            'metros' => $this->sumarDetalles($detalles, 'metros'),
            'impbs' => $totalImpBs,
        ];

        $pdf = Pdf::loadView('impresiones-sas.pdf', [
            'cabecera' => $cabecera,
            'detalles' => $detalles,
            'totales' => $totales,
            'totalUsd' => $totalUsd,
        ])->setPaper('letter', 'portrait');

        $id = $cabecera['id'] ?? ($datos['numero_documento'] ?? 'documento');

        return $pdf->stream('Transito-' . $id . '.pdf');
    }

    public function resultado()
    {
        $datos = Session::get('impresiones_sas');

        if (!$datos) {
            return redirect()
                ->route('impresiones-sas.index')
                ->with('error', 'Primero debes buscar un número de documento.');
        }

        return view('impresiones-sas.resultado', $datos);
    }

    /**
     * Guarda únicamente la versión de trabajo que posteriormente utilizará el PDF.
     * Nunca actualiza faboce2026.
     */
    public function guardarEdicion(Request $request)
    {
        $datos = Session::get('impresiones_sas');

        if (!$datos) {
            return redirect()
                ->route('impresiones-sas.index')
                ->with('error', 'La sesión de impresión ha expirado. Realiza nuevamente la búsqueda.');
        }

        $payload = $request->validate([
            'cabecera' => ['required', 'array'],
            'cabecera.*' => ['nullable', 'string', 'max:1000'],
            'detalles' => ['nullable', 'array'],
            'detalles.*' => ['array'],
            'detalles.*.*' => ['nullable', 'string', 'max:1000'],
        ]);

        $datos['cabecera'] = array_merge($datos['cabecera'] ?? [], $payload['cabecera']);
        $datos['detalles'] = $this->mezclarEdicionDetalles(
            $datos['detalles'] ?? [],
            $payload['detalles'] ?? []
        );

        /*
         * Se mantiene también una copia explícita de la edición. Esto permite
         * distinguir los datos originales de los datos que el usuario modificó.
         */
        $datos['edicion'] = [
            'cabecera' => $datos['cabecera'],
            'detalles' => $datos['detalles'],
        ];

        Session::put('impresiones_sas', $datos);

        return redirect()
            ->route('impresiones-sas.resultado')
            ->with('success', 'Cambios guardados temporalmente. No se modificó la base de datos.');
    }

    private function obtenerCatalogoProductos($detalles)
    {
        $codigos = $detalles
            ->pluck('codigo')
            ->filter(fn ($codigo) => $codigo !== null && $codigo !== '')
            ->unique()
            ->values();

        if ($codigos->isEmpty()) {
            return collect();
        }

        return DB::connection('sisinvconsolidado2026')
            ->table('stock')
            ->whereIn('CODIGO', $codigos)
            ->select('CODIGO', 'DESCRIP', 'DESCRIP1')
            ->get()
            ->keyBy('CODIGO');
    }

    /**
     * Convierte log_registro_detalle al mismo lenguaje de campos que utiliza
     * la vista PDF histórica.
     *
     * Cuando el dato exacto no está disponible en log_registro_detalle, se deja
     * vacío en vez de inventarlo. Esto es importante porque facturada,
     * acumulada, entregada, saldo, metros e impbs históricamente eran calculados
     * por RegistroClass antes de llegar a la vista PDF.
     */
    private function normalizarDetalle($detalle, $catalogo): array
    {
        $fila = (array) $detalle;
        $stock = $catalogo->get($fila['codigo'] ?? null);

        $producto = $this->primerValor($fila, [
            'producto',
            'descrip',
            'descripcion',
        ], $stock->DESCRIP ?? '');

        $descrip1 = $this->primerValor($fila, [
            'descrip1',
        ], $stock->DESCRIP1 ?? '');

        $lote = $this->primerValor($fila, [
            'lote',
            'clote',
        ], '');

        return array_merge($fila, [
            // Campos utilizados por la impresión histórica.
            'factnota' => $this->primerValor($fila, [
                'factnota',
                'fact_nota',
                'factura',
                'nota',
                'tdocum',
            ], ''),
            'viaje' => $this->primerValor($fila, [
                'viaje',
                'nro_viaje',
                'numero_viaje',
            ], ''),
            'producto' => $producto,
            'descrip1' => $descrip1,
            'lote' => $lote,

            // No se inventan cálculos. Si el dato ya existe con alguno de estos
            // nombres, se conserva para que pueda editarse/imprimirse.
            'facturada' => $this->primerValor($fila, [
                'facturada',
                'cantidad_facturada',
                'cajas_facturadas',
            ], ''),
            'acumulada' => $this->primerValor($fila, [
                'acumulada',
                'cantidad_acumulada',
                'cajas_acumuladas',
            ], ''),
            'entregada' => $this->primerValor($fila, [
                'entregada',
                'cantidad_entregada',
            ], ''),
            'saldo' => $this->primerValor($fila, [
                'saldo',
                'cantidad_saldo',
            ], ''),
            'metros' => $this->primerValor($fila, [
                'metros',
                'm2',
                'metro2',
            ], ''),
            'impbs' => $this->primerValor($fila, [
                'impbs',
                'imp_bs',
                'importe_bs',
                'importe',
            ], ''),
        ]);
    }

    /**
     * Obtiene los datos relacionados de logística mediante SELECT.
     * Los valores de búsqueda salen del registro y de su relación; no hay
     * valores de prueba ni escrituras sobre faboce2026.
     */
    private function obtenerDatosLogistica($registro): array
    {
        $resultado = [
            'relacion' => null,
            'empresa_transporte' => null,
            'camion' => null,
            'conductor' => null,
            'agencias' => collect(),
            'flete' => null,
            'flete_detalle' => collect(),
        ];

        if (!$registro) {
            return $resultado;
        }

        $cn = DB::connection('faboce2026');

        if (($registro->id_relacion ?? null) !== null) {
            $resultado['relacion'] = $cn
                ->table('log_empresa_camion_conductor_flete')
                ->where('id', $registro->id_relacion)
                ->first();
        }

        $relacion = $resultado['relacion'];

        if ($relacion) {
            if (($relacion->nit ?? null) !== null && $relacion->nit !== '') {
                $resultado['empresa_transporte'] = $cn
                    ->table('log_empresa_transporte')
                    ->where('nit', $relacion->nit)
                    ->first();
            }

            if (($relacion->placa ?? null) !== null && $relacion->placa !== '') {
                $resultado['camion'] = $cn
                    ->table('log_camiones')
                    ->where('placa', $relacion->placa)
                    ->first();
            }

            if (($relacion->carnet_identidad ?? null) !== null && $relacion->carnet_identidad !== '') {
                $resultado['conductor'] = $cn
                    ->table('log_conductores')
                    ->where('carnet_identidad', $relacion->carnet_identidad)
                    ->first();
            }

            if (($relacion->id_flete ?? null) !== null) {
                $resultado['flete'] = $cn
                    ->table('log_flete')
                    ->where('id', $relacion->id_flete)
                    ->first();

                $queryFlete = $cn
                    ->table('log_flete_detalle')
                    ->where('id_flete', $relacion->id_flete);

                if (($registro->agencia ?? null) !== null) {
                    $queryFlete->where('agencia_origen', $registro->agencia);
                }

                if (($registro->destino ?? null) !== null) {
                    $queryFlete->where('agencia_destino', $registro->destino);
                }

                $resultado['flete_detalle'] = $queryFlete->get();
            }
        }

        $codigosAgencia = collect([
            $registro->agencia ?? null,
            $registro->destino ?? null,
        ])->filter(fn ($v) => $v !== null && $v !== '')->unique()->values();

        if ($codigosAgencia->isNotEmpty()) {
            $resultado['agencias'] = $cn
                ->table('agencias')
                ->whereIn('AGECODIGO', $codigosAgencia)
                ->get();
        }

        return $resultado;
    }

    private function normalizarCabecera($registro, array $detalles, array $logistica = []): array
    {
        $fila = $registro ? (array) $registro : [];

        $origen = $this->primerValor($fila, [
            'origen',
            'agencia_origen',
            'agecodigo',
        ], '');

        $destino = $this->primerValor($fila, [
            'destino',
            'agencia_destino',
            'agecodigodes',
        ], '');

        $regional = $this->primerValor($fila, [
            'regional',
            'ageregional',
        ], '');

        $relacion = $logistica['relacion'] ?? null;
        $empresa = $logistica['empresa_transporte'] ?? null;
        $camion = $logistica['camion'] ?? null;
        $conductor = $logistica['conductor'] ?? null;
        $flete = $logistica['flete'] ?? null;
        $fleteDetalle = $logistica['flete_detalle'] ?? collect();
        $agencias = $logistica['agencias'] ?? collect();

        $agenciaOrigen = $agencias->firstWhere('AGECODIGO', $origen);
        $agenciaDestino = $agencias->firstWhere('AGECODIGO', $destino);

        $nit = $this->primerValor($fila, [
            'nit',
            'nit_transportista',
        ], $relacion->nit ?? '');

        $razonSocial = $this->primerValor($fila, [
            'razon_social',
            'empresa',
            'nombre_empresa',
        ], $empresa->razon_social ?? '');

        $nombre = $this->primerValor($fila, [
            'nombre',
            'conductor',
            'nombre_conductor',
            'transportista',
        ], $conductor->nombre ?? '');

        $telefono = $this->primerValor($fila, [
            'telefono',
            'celular',
            'telefono_conductor',
        ], $conductor->telefono ?? '');

        $placa = $this->primerValor($fila, [
            'placa',
            'placa_camion',
        ], $camion->placa ?? ($relacion->placa ?? ''));

        $descripcionCamion = $this->primerValor($fila, [
            'descripcionc',
            'descripcion_camion',
            'camion',
        ], $camion->descripcion ?? '');

        /*
         * El quintal histórico se calculaba con cantidad_despacho / 46 * peso.
         * Solo lo calculamos cuando ambos datos existen en los detalles.
         */
        $quintales = 0;
        $hayPeso = false;

        foreach ($detalles as $detalle) {
            $cantidad = $this->numero($detalle['cantidad_despacho'] ?? null);
            $peso = $this->numero($detalle['peso'] ?? null);

            if ($peso !== null) {
                $hayPeso = true;
                $quintales += (($cantidad ?? 0) / 46) * $peso;
            }
        }

        return [
            'id' => $this->primerValor($fila, ['id'], ''),
            'fechad' => $this->primerValor($fila, ['fechad', 'fecha'], ''),
            'fechar' => $this->primerValor($fila, ['fechar', 'created_at'], ''),
            'glosa' => $this->primerValor($fila, ['glosa', 'observacion'], ''),
            'nit' => $nit,
            'razon_social' => $razonSocial,
            'placa' => $placa,
            'descripcionc' => $descripcionCamion,
            'carnet_identidad' => $this->primerValor($fila, [
                'carnet_identidad',
                'ci',
                'carnet',
            ], $conductor->carnet_identidad ?? ($relacion->carnet_identidad ?? '')),
            'nombre' => $nombre,
            'telefono' => $telefono,
            'id_flete' => $this->primerValor($fila, ['id_flete'], $relacion->id_flete ?? ''),
            'descripcionf' => $this->primerValor($fila, [
                'descripcionf',
                'flete',
            ], $flete->descripcion ?? ''),
            'regional' => $regional,
            'origen' => $origen,
            'destino' => $destino,
            'origen_nombre' => $agenciaOrigen->AGENOMBRE ?? '',
            'destino_nombre' => $agenciaDestino->AGENOMBRE ?? '',
            'flete_tramo_corto' => optional($fleteDetalle->first())->tramo_corto,
            'flete_tramo_corto_palet' => optional($fleteDetalle->first())->tramo_corto_palet,
            'usuario' => auth()->user()->name ?? '',
            'quintales' => $hayPeso ? number_format($quintales, 2, '.', '') : '',
            'tipo_documento' => $this->primerValor($fila, [
                'tipo_documento',
                'tipo',
            ], 'F'),

            // Campos auxiliares para la pantalla y el futuro PDF.
            'titulo' => 'DESPACHO DE PRODUCTO TERMINADO',
            'subtitulo' => $this->construirSubtitulo($origen, $destino),
            'empresa' => trim($nit . ($nit && $razonSocial ? ' - ' : '') . $razonSocial),
            'transportista' => $nombre,
            'camion' => trim($placa . ($placa && $descripcionCamion ? ' - ' : '') . $descripcionCamion),
            'empresa_transporte' => $razonSocial,
            'observacion' => $this->primerValor($fila, ['observacion'], ''),
        ];
    }

    private function construirSubtitulo($origen, $destino): string
    {
        $partes = array_values(array_filter([
            $destino,
            $origen,
        ], fn ($valor) => $valor !== null && trim((string) $valor) !== ''));

        return implode(' - ', $partes);
    }

    private function primerValor(array $fila, array $campos, $default = '')
    {
        foreach ($campos as $campo) {
            if (array_key_exists($campo, $fila) && $fila[$campo] !== null && $fila[$campo] !== '') {
                return $fila[$campo];
            }
        }

        return $default;
    }

    private function numero($valor): ?float
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        if (is_numeric($valor)) {
            return (float) $valor;
        }

        $normalizado = str_replace(',', '', (string) $valor);

        return is_numeric($normalizado) ? (float) $normalizado : null;
    }

    private function sumarDetalles(array $detalles, string $campo): float
    {
        return array_reduce($detalles, function ($total, $detalle) use ($campo) {
            $numero = $this->numero($detalle[$campo] ?? null);
            return $total + ($numero ?? 0);
        }, 0.0);
    }

    private function mezclarEdicionDetalles(array $originales, array $editados): array
    {
        foreach ($editados as $indice => $edicion) {
            if (!isset($originales[$indice])) {
                continue;
            }

            $originales[$indice] = array_merge($originales[$indice], $edicion);
        }

        return array_values($originales);
    }
}
