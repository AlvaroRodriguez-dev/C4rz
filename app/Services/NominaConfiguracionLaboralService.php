<?php

namespace App\Services;

use App\Models\NominaConfiguracionLaboral;
use App\Models\NominaPersonal;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class NominaConfiguracionLaboralService
{
    public function obtenerDesdeRrhh(string $license): array
    {
        $connection = DB::connection('pgsql_rrhh');
        $schema = env('PG_RRHH_SCHEMA', '2026');

        $table = static function (string $name) use ($schema): string {
            $schema = str_replace('"', '""', $schema);
            $name = str_replace('"', '""', $name);

            return '"' . $schema . '"."' . $name . '"';
        };

        $sql = sprintf(
            'SELECT
                p."LICENSE" AS license,
                p."AREA_ID" AS area_id_externo,
                a."CODE" AS area_codigo,
                a."DESCRIPTION" AS area_nombre,
                p."SECTION_ID" AS seccion_id_externo,
                s."DESCRIPTION" AS seccion_nombre,
                p."CHARGE_ID" AS cargo_id_externo,
                c."DESCRIPTION" AS cargo_nombre,
                p."IDJERARQUIA" AS jerarquia_id_externo,
                j."DESCRIPTION" AS jerarquia_nombre,
                p."AGECODIGO" AS agencia_codigo,
                ag."AGENOMBRE" AS agencia_nombre,
                p."CIUDAD" AS ciudad,
                p."DATEI" AS fecha_ingreso,
                p."DATEF" AS fecha_retiro
            FROM %s p
            LEFT JOIN %s a ON a."ID" = p."AREA_ID"
            LEFT JOIN %s s ON s."ID" = p."SECTION_ID"
            LEFT JOIN %s c ON c."ID" = p."CHARGE_ID"
            LEFT JOIN %s j ON j."ID" = p."IDJERARQUIA"
            LEFT JOIN %s ag ON ag."AGECODIGO" = p."AGECODIGO"
            WHERE p."LICENSE" = ?
              AND p."DELETED_AT" IS NULL
            LIMIT 1',
            $table('rrhh_personal'),
            $table('rrhh_area'),
            $table('rrhh_seccion'),
            $table('rrhh_cargo'),
            $table('rrhh_jerarquia'),
            $table('agencias')
        );

        $rows = $connection->select($sql, [$license]);

        if (empty($rows)) {
            throw new InvalidArgumentException(
                "El LICENSE [{$license}] no corresponde a un trabajador activo en RRHH."
            );
        }

        return (array) $rows[0];
    }

    public function registrarActual(string $license): NominaConfiguracionLaboral
    {
        $rrhh = $this->obtenerDesdeRrhh($license);

        $personal = NominaPersonal::where('license', $license)->first();

        if (!$personal) {
            throw new InvalidArgumentException(
                "El LICENSE [{$license}] debe estar registrado en la nómina antes de registrar su configuración laboral."
            );
        }

        $fechaInicio = $rrhh['fecha_ingreso'] ?? now()->toDateString();

        return NominaConfiguracionLaboral::updateOrCreate(
            [
                'rh_personal_id' => $personal->id,
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => $rrhh['fecha_retiro'],
            ],
            [
                'area_id_externo' => $rrhh['area_id_externo'],
                'area_codigo' => $rrhh['area_codigo'],
                'area_nombre' => $rrhh['area_nombre'],
                'seccion_id_externo' => $rrhh['seccion_id_externo'],
                'seccion_nombre' => $rrhh['seccion_nombre'],
                'cargo_id_externo' => $rrhh['cargo_id_externo'],
                'cargo_nombre' => $rrhh['cargo_nombre'],
                'jerarquia_id_externo' => $rrhh['jerarquia_id_externo'],
                'jerarquia_nombre' => $rrhh['jerarquia_nombre'],
                'agencia_codigo' => $rrhh['agencia_codigo'],
                'agencia_nombre' => $rrhh['agencia_nombre'],
                'ciudad' => $rrhh['ciudad'],
                'fecha_ingreso' => $rrhh['fecha_ingreso'],
                'fecha_retiro' => $rrhh['fecha_retiro'],
            ]
        );
    }
}
