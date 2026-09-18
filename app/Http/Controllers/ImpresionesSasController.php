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

        $cabecera = $this->normalizarCabecera($registro, $detalles);

        Session::put('impresiones_sas', [
            'numero_documento' => $numero,
            'registro' => $registro ? (array) $registro : null,
            'cabecera' => $cabecera,
            'detalles' => $detalles,
            'token' => (string) Str::uuid(),
        ]);

        return redirect()->route('impresiones-sas.resultado');
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
                'cantidad_despacho',
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

    private function normalizarCabecera($registro, array $detalles): array
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

        $nit = $this->primerValor($fila, [
            'nit',
            'nit_transportista',
        ], '');

        $razonSocial = $this->primerValor($fila, [
            'razon_social',
            'empresa',
            'nombre_empresa',
        ], '');

        $nombre = $this->primerValor($fila, [
            'nombre',
            'conductor',
            'nombre_conductor',
            'transportista',
        ], '');

        $telefono = $this->primerValor($fila, [
            'telefono',
            'celular',
            'telefono_conductor',
        ], '');

        $placa = $this->primerValor($fila, [
            'placa',
            'placa_camion',
        ], '');

        $descripcionCamion = $this->primerValor($fila, [
            'descripcionc',
            'descripcion_camion',
            'camion',
        ], '');

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
            ], ''),
            'nombre' => $nombre,
            'telefono' => $telefono,
            'id_flete' => $this->primerValor($fila, ['id_flete'], ''),
            'descripcionf' => $this->primerValor($fila, [
                'descripcionf',
                'flete',
            ], ''),
            'regional' => $regional,
            'origen' => $origen,
            'destino' => $destino,
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
