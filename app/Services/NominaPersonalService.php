<?php

namespace App\Services;

use App\Models\NominaPersonal;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class NominaPersonalService
{
    private const RRHH_CONNECTION = 'pgsql_rrhh';
    private const RRHH_TABLE = 'rrhh_personal';

    /**
     * Busca un trabajador activo en RRHH utilizando únicamente LICENSE.
     *
     * Para esta versión de Nómina, RRHH es solamente la fuente de identidad:
     * LICENSE + nombre completo. No se exponen aquí otros atributos laborales.
     */
    public function findByLicense(string $license): ?object
    {
        $license = trim($license);

        if ($license === '') {
            throw new InvalidArgumentException('El LICENSE es obligatorio.');
        }

        return $this->rrhhQuery()
            ->where('LICENSE', $license)
            ->whereNull('DELETED_AT')
            ->first();
    }

    /**
     * Verifica si un LICENSE corresponde a un trabajador activo de RRHH.
     */
    public function existsActive(string $license): bool
    {
        return $this->findByLicense($license) !== null;
    }

    /**
     * Obtiene los primeros trabajadores activos para diagnósticos/control.
     * Nunca devuelve atributos distintos de identidad.
     */
    public function active(int $limit = 10): array
    {
        $limit = max(1, min($limit, 100));

        return $this->rrhhQuery()
            ->whereNull('DELETED_AT')
            ->orderBy('LICENSE')
            ->limit($limit)
            ->get()
            ->map(fn (object $personal) => $this->normalize($personal))
            ->values()
            ->all();
    }

    /**
     * Normaliza la respuesta externa a la representación utilizada por Nómina.
     */
    public function normalize(object $personal): array
    {
        $name = trim((string) ($personal->NAME ?? ''));
        $lastname = trim((string) ($personal->LASTNAME ?? ''));

        return [
            'license' => trim((string) ($personal->LICENSE ?? '')),
            'nombre_completo' => trim($name . ' ' . $lastname),
        ];
    }

    /**
     * Registra la identidad en el maestro propio de Nómina después de validarla
     * contra RRHH. No copia datos laborales ni salariales de RRHH.
     */
    public function register(string $license): NominaPersonal
    {
        $personal = $this->findByLicense($license);

        if ($personal === null) {
            throw new InvalidArgumentException(
                "El LICENSE [{$license}] no corresponde a un trabajador activo en RRHH."
            );
        }

        return NominaPersonal::updateOrCreate(
            ['license' => trim($license)],
            ['estado' => 'ACTIVO']
        );
    }

    private function rrhhQuery(): Builder
    {
        $schema = env('PG_RRHH_SCHEMA', '2026');
        $qualifiedTable = $this->quoteIdentifier($schema) . '.' . $this->quoteIdentifier(self::RRHH_TABLE);

        return DB::connection(self::RRHH_CONNECTION)
            ->table($qualifiedTable)
            ->select(['LICENSE', 'NAME', 'LASTNAME']);
    }

    private function quoteIdentifier(string $identifier): string
    {
        return '"' . str_replace('"', '""', $identifier) . '"';
    }
}
