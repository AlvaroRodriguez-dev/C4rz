<?php

namespace App\Services;

use App\Models\WmsAlmacen;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class WmsProduccionFuenteService
{
    private const CONNECTION = 'sisinvconsolidado2026';

    public function buscarDocumentos(
        WmsAlmacen $almacen,
        ?string $busqueda = null,
        int $limite = 30
    ): Collection {
        $q = trim((string) $busqueda);

        return DB::connection(self::CONNECTION)
            ->table('recep')
            ->where('AGECODIGO', (int) $almacen->codigo)
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('RDOCUM', 'like', "%{$q}%")
                        ->orWhere('RNOMBRE', 'like', "%{$q}%");
                });
            })
            ->select([
                'RDOCUM',
                'RFECHA',
                'RNOMBRE',
                'AGECODIGO',
                'PROCODIGO',
            ])
            ->orderByDesc('RFECHA')
            ->limit($limite)
            ->get();
    }

    public function detalleDocumento(
        WmsAlmacen $almacen,
        string $rdocum
    ): array {
        $rdocum = trim($rdocum);

        if ($rdocum === '') {
            throw new InvalidArgumentException('El documento de origen es obligatorio.');
        }

        $cabecera = DB::connection(self::CONNECTION)
            ->table('recep')
            ->where('RDOCUM', $rdocum)
            ->where('AGECODIGO', (int) $almacen->codigo)
            ->select([
                'RDOCUM',
                'RFECHA',
                'RNOMBRE',
                'AGECODIGO',
                'PROCODIGO',
            ])
            ->first();

        if (!$cabecera) {
            return [
                'cabecera' => null,
                'detalles' => collect(),
            ];
        }

        $detalles = DB::connection(self::CONNECTION)
            ->table('recep1 as r1')
            ->leftJoin('stock as s', 's.CODIGO', '=', 'r1.CODIGO')
            ->where('r1.RDOCUM', $rdocum)
            ->select([
                'r1.RDOCUM',
                'r1.CODIGO',
                'r1.CLOTE',
                'r1.RCANTIDAD',
                's.DESCRIP',
                's.DESCRIP1',
            ])
            ->orderBy('r1.CODIGO')
            ->orderBy('r1.CLOTE')
            ->get();

        return [
            'cabecera' => $cabecera,
            'detalles' => $detalles,
        ];
    }
}
