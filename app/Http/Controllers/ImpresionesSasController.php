<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
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

        $detalles = DB::connection('faboce2026')
            ->table('log_registro_detalle')
            ->where('id_registro', $numero)
            ->orderBy('id')
            ->get();

        // Solo lectura: enriquecemos la descripción del producto con el catálogo
        // que ya utiliza el módulo WMS. No se modifica ninguna tabla.
        $codigos = $detalles->pluck('codigo')->filter()->unique()->values();

        $catalogo = $codigos->isNotEmpty()
            ? DB::connection('sisinvconsolidado2026')
                ->table('stock')
                ->whereIn('CODIGO', $codigos)
                ->select('CODIGO', 'DESCRIP', 'DESCRIP1')
                ->get()
                ->keyBy('CODIGO')
            : collect();

        $detalles = $detalles->map(function ($detalle) use ($catalogo) {
            $item = (array) $detalle;
            $stock = $catalogo->get($detalle->codigo);

            if ($stock) {
                $item['descrip'] = $stock->DESCRIP ?? '';
                $item['descrip1'] = $stock->DESCRIP1 ?? '';
                $item['producto'] = trim(($stock->DESCRIP ?? '') . ' ' . ($stock->DESCRIP1 ?? '') . ' - ' . ($detalle->lote ?? ''));
            }

            return (object) $item;
        });

        if (!$registro && $detalles->isEmpty()) {
            return back()
                ->withInput()
                ->with('error', "No se encontraron registros para el documento {$numero}.");
        }

        Session::put('impresiones_sas', [
            'numero_documento' => $numero,
            'registro' => $registro ? (array) $registro : null,
            'detalles' => $detalles->map(fn ($item) => (array) $item)->values()->all(),
            'token' => (string) Str::uuid(),
        ]);

        return redirect()->route('impresiones-sas.resultado');
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
     * Guarda únicamente la versión de trabajo que se utilizará posteriormente
     * para generar el PDF. Nunca actualiza faboce2026.
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

        $datos['edicion'] = [
            'cabecera' => $payload['cabecera'],
            'detalles' => $payload['detalles'] ?? [],
        ];

        Session::put('impresiones_sas', $datos);

        return redirect()
            ->route('impresiones-sas.resultado')
            ->with('success', 'Cambios guardados temporalmente. No se modificó la base de datos.');
    }
}
