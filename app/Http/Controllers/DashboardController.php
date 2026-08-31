<?php

namespace App\Http\Controllers;

use App\Models\Agencia;
use App\Models\ComercialContacto;

class DashboardController extends Controller
{
    public function index()
    {
        $totalContactos = ComercialContacto::count();
        $totalAgencias = Agencia::count();

        $porAgencia = ComercialContacto::selectRaw('agencia_id, count(*) as total')
            ->groupBy('agencia_id')
            ->with('agencia:id,descripcion')
            ->get()
            ->map(fn ($row) => [
                'label' => $row->agencia->descripcion ?? 'Sin agencia',
                'total' => $row->total,
            ]);

        $porCiudad = ComercialContacto::join('agencias', 'agencias.id', '=', 'comercial_contactos.agencia_id')
            ->whereNull('agencias.deleted_at')
            ->selectRaw('agencias.ciudad as ciudad, count(*) as total')
            ->groupBy('agencias.ciudad')
            ->orderBy('agencias.ciudad')
            ->get();

        return view('dashboard', compact('totalContactos', 'totalAgencias', 'porAgencia', 'porCiudad'));
    }
}