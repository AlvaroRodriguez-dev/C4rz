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
}
