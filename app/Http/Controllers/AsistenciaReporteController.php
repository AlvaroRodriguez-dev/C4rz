<?php
namespace App\Http\Controllers;

use App\Models\AsistenciaApp;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AsistenciaReporteController extends Controller
{
    public function index(Request $request)
    {
        $usuarios = User::whereNotNull('license')
                        ->orderBy('name')
                        ->get(['id', 'name', 'email', 'license']);

        $marcajes  = collect();
        $filtros   = [];

        if ($request->isMethod('post')) {
            $request->validate([
                'fecha_ini'  => 'required|date',
                'fecha_fin'  => 'required|date|after_or_equal:fecha_ini',
                'user_id'    => 'nullable|exists:users,id',
            ]);

            $query = AsistenciaApp::with('user')
                ->whereBetween('fecha_servidor', [
                    $request->fecha_ini . ' 00:00:00',
                    $request->fecha_fin . ' 23:59:59',
                ])
                ->orderBy('fecha_servidor');

            if ($request->user_id) {
                $query->where('user_id', $request->user_id);
            }

            $marcajes = $query->get()->map(function ($m) {
                return [
                    'id'             => $m->id,
                    'nombre'         => $m->name . ' ' . $m->lastname,
                    'license'        => $m->license,
                    'tipo'           => $m->tipo,
                    'fecha_servidor' => $m->fecha_servidor,
                    'fecha_cliente'  => $m->fecha_cliente,
                    'latitud'        => $m->latitud,
                    'longitud'       => $m->longitud,
                    'direccion'      => $m->direccion,
                    'foto_url' => asset('storage/' . $m->foto),
                ];
            });

            $filtros = $request->only(['fecha_ini', 'fecha_fin', 'user_id']);
        }

        return view('asistencia.reporte', compact('usuarios', 'marcajes', 'filtros'));
    }
}