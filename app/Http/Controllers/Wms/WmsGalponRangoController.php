<?php

namespace App\Http\Controllers\Wms;

use App\Http\Controllers\Controller;
use App\Models\WmsGalpon;
use App\Models\WmsGalponRango;
use App\Models\WmsUbicacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WmsGalponRangoController extends Controller
{
    private const MAX_POSICIONES_POR_TRAMO = 10000;

    public function index(WmsGalpon $galpon)
    {
        $galpon->load('almacen');

        $rangos = $galpon->rangos()
            ->orderBy('desde')
            ->get();

        $rangos->each(function (WmsGalponRango $rango) use ($galpon) {
            $rango->posiciones_generadas = WmsUbicacion::query()
                ->where('almacen_id', $galpon->almacen_id)
                ->where('galpon_id', $galpon->id)
                ->where('tipo', 'NORMAL')
                ->whereBetween('numero', [$rango->desde, $rango->hasta])
                ->count();
        });

        $totalPosiciones = WmsUbicacion::query()
            ->where('almacen_id', $galpon->almacen_id)
            ->where('galpon_id', $galpon->id)
            ->where('tipo', 'NORMAL')
            ->count();

        return view('wms.galpones.rangos.index', compact(
            'galpon',
            'rangos',
            'totalPosiciones'
        ));
    }

    public function create(WmsGalpon $galpon)
    {
        $galpon->load('almacen');

        return view('wms.galpones.rangos.create', compact('galpon'));
    }

    public function store(Request $request, WmsGalpon $galpon)
    {
        $validated = $request->validate([
            'desde' => ['required', 'integer', 'min:1', 'max:4294967295'],
            'hasta' => ['required', 'integer', 'min:1', 'max:4294967295'],
        ], [
            'desde.required' => 'Debe indicar la posición inicial.',
            'hasta.required' => 'Debe indicar la posición final.',
            'desde.min' => 'La posición inicial debe ser mayor que cero.',
            'hasta.min' => 'La posición final debe ser mayor que cero.',
        ]);

        $desde = (int) $validated['desde'];
        $hasta = (int) $validated['hasta'];

        if ($desde > $hasta) {
            throw ValidationException::withMessages([
                'desde' => 'La posición inicial no puede ser mayor que la posición final.',
            ]);
        }

        $cantidad = $hasta - $desde + 1;

        if ($cantidad > self::MAX_POSICIONES_POR_TRAMO) {
            throw ValidationException::withMessages([
                'hasta' => 'Un solo tramo no puede generar más de ' . number_format(self::MAX_POSICIONES_POR_TRAMO, 0, ',', '.') . ' posiciones.',
            ]);
        }

        if (!$galpon->activo) {
            throw ValidationException::withMessages([
                'desde' => 'No se pueden agregar tramos a un galpón inactivo.',
            ]);
        }

        if (!$galpon->almacen()->where('tipo', 'PRINCIPAL')->where('activo', true)->exists()) {
            throw ValidationException::withMessages([
                'desde' => 'El almacén principal del galpón no está activo.',
            ]);
        }

        DB::transaction(function () use ($galpon, $desde, $hasta, $cantidad) {
            // Bloqueamos el galpón durante la validación + generación para evitar
            // dos configuraciones simultáneas sobre el mismo maestro.
            WmsGalpon::query()
                ->whereKey($galpon->id)
                ->lockForUpdate()
                ->firstOrFail();

            $rangoExistente = WmsGalponRango::query()
                ->where('galpon_id', $galpon->id)
                ->where(function ($query) use ($desde, $hasta) {
                    $query->where('desde', '<=', $hasta)
                        ->where('hasta', '>=', $desde);
                })
                ->orderBy('desde')
                ->first();

            if ($rangoExistente) {
                throw ValidationException::withMessages([
                    'desde' => "El tramo {$desde}-{$hasta} se superpone con el tramo existente {$rangoExistente->desde}-{$rangoExistente->hasta}.",
                ]);
            }

            $conflicto = WmsUbicacion::query()
                ->where('almacen_id', $galpon->almacen_id)
                ->where('tipo', 'NORMAL')
                ->whereBetween('numero', [$desde, $hasta])
                ->where(function ($query) use ($galpon) {
                    $query->whereNull('galpon_id')
                        ->orWhere('galpon_id', '<>', $galpon->id);
                })
                ->orderBy('numero')
                ->first();

            if ($conflicto) {
                throw ValidationException::withMessages([
                    'desde' => "La posición {$conflicto->numero} ya está asignada a otro galpón del almacén. No se puede utilizar en este tramo.",
                ]);
            }

            $ahora = now();

            WmsGalponRango::create([
                'galpon_id' => $galpon->id,
                'desde' => $desde,
                'hasta' => $hasta,
                'activo' => true,
            ]);

            $existentes = WmsUbicacion::query()
                ->where('almacen_id', $galpon->almacen_id)
                ->where('galpon_id', $galpon->id)
                ->where('tipo', 'NORMAL')
                ->whereBetween('numero', [$desde, $hasta])
                ->pluck('numero')
                ->map(fn ($numero) => (int) $numero)
                ->flip();

            $nuevas = [];

            for ($numero = $desde; $numero <= $hasta; $numero++) {
                if ($existentes->has($numero)) {
                    continue;
                }

                $nuevas[] = [
                    'almacen_id' => $galpon->almacen_id,
                    'galpon_id' => $galpon->id,
                    'codigo' => (string) $numero,
                    'tipo' => 'NORMAL',
                    'numero' => $numero,
                    'activo' => true,
                    'created_at' => $ahora,
                    'updated_at' => $ahora,
                ];
            }

            foreach (array_chunk($nuevas, 500) as $lote) {
                DB::table('wms_ubicaciones')->insert($lote);
            }

            // $cantidad se utiliza como control de rango; las posiciones ya
            // existentes se conservan sin modificar su estado.
            unset($cantidad);
        }, 3);

        return redirect()
            ->route('wms.galpones.rangos.index', $galpon)
            ->with('success', "Tramo {$desde}-{$hasta} registrado correctamente. Las posiciones nuevas fueron generadas sin modificar las existentes.");
    }
}
