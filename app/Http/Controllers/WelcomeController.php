<?php

namespace App\Http\Controllers;

use App\Models\Agencia;

class WelcomeController extends Controller
{
    public function index()
    {
        $agenciasPorCiudad = Agencia::orderBy('ciudad')
            ->orderBy('descripcion')
            ->get()
            ->groupBy('ciudad');

        return view('welcome', compact('agenciasPorCiudad'));
    }
}
