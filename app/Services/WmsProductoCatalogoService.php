<?php

namespace App\Services;

use Illuminate\Support\Collection;
use RuntimeException;

class WmsProductoCatalogoService
{
    private const PLANTAS_POR_ALMACEN = [
        '110' => '6C',
        '240' => '6S',
        '250' => '6P',
        '440' => '6T',
    ];

    private const CALIDADES = [
        'EXTRA' => '1',
        'COMERCIAL' => '2',
        'ECONOMICO' => '3',
    ];

    private const CALIDADES_INVERTIDAS = [
        '1' => 'EXTRA',
        '2' => 'COMERCIAL',
        '3' => 'ECONOMICO',
    ];

    public function buscar($almacen, string $texto, string $formato, ?string $calidad = null): Collection
    {
        $codigoAlmacen = trim((string) $almacen->codigo);
        $planta = self::PLANTAS_POR_ALMACEN[$codigoAlmacen] ?? null;

        if (!$planta) {
            throw new RuntimeException('El almacén operativo no tiene una planta de producción configurada.');
        }

        $formato = strtoupper(trim($formato));
        if ($formato === '') {
            return collect();
        }

        $calidadCodigo = null;
        if ($calidad !== null && trim($calidad) !== '') {
            $calidadNormalizada = strtoupper(trim($calidad));
            $calidadCodigo = self::CALIDADES[$calidadNormalizada] ?? null;

            if (!$calidadCodigo) {
                throw new RuntimeException('La calidad seleccionada no es válida para una liberación de producción.');
            }
        }

        $query = \DB::connection('sisinvconsolidado2026')
            ->table('stock')
            ->where('CODIGO', 'like', $planta . '%')
            ->whereRaw('UPPER(SUBSTRING(CODIGO, 6, 4)) = ?', [$formato]);

        if ($calidadCodigo !== null) {
            $query->whereRaw('UPPER(SUBSTRING(CODIGO, 5, 1)) = ?', [$calidadCodigo]);
        }

        return $query
            ->when(trim($texto) !== '', function ($query) use ($texto) {
                $termino = trim($texto);
                $query->where(function ($sub) use ($termino) {
                    $sub->where('CODIGO', 'like', '%' . $termino . '%')
                        ->orWhere('DESCRIP', 'like', '%' . $termino . '%');
                });
            })
            ->select('CODIGO', 'DESCRIP', 'DESCRIP1')
            ->orderBy('DESCRIP')
            ->orderBy('CODIGO')
            ->limit(50)
            ->get()
            ->map(function ($producto) {
                $codigo = strtoupper(trim((string) $producto->CODIGO));
                $calidad = self::CALIDADES_INVERTIDAS[$this->calidadCodigo($codigo)] ?? 'OTRO';
                $descripcion = trim((string) $producto->DESCRIP);

                return [
                    'id' => $codigo,
                    'text' => $descripcion . ' · ' . $calidad . ' · ' . $codigo,
                    'codigo' => $codigo,
                    'descripcion' => $descripcion,
                    'descripcion2' => null,
                    'modelo' => substr($codigo, -4),
                    'calidad' => $calidad,
                    'es_ap' => stripos($descripcion, 'AP') !== false,
                ];
            });
    }

    public function validar($almacen, string $codigo, string $formato, ?string $calidad = null): object
    {
        $codigo = strtoupper(trim($codigo));
        $productos = $this->buscar($almacen, $codigo, $formato, $calidad);
        $producto = $productos->firstWhere('codigo', $codigo);

        if (!$producto) {
            throw new RuntimeException('El producto seleccionado no corresponde al almacén, formato o calidad indicados.');
        }

        return (object) $producto;
    }

    private function calidadCodigo(string $codigo): string
    {
        return strtoupper(substr($codigo, 4, 1));
    }
}
