<?php

namespace Database\Seeders;

use App\Models\NominaConcepto;
use Illuminate\Database\Seeder;

class NominaConceptosSeeder extends Seeder
{
    public function run(): void
    {
        $conceptos = [
            ['codigo' => 'HABER_BASICO', 'nombre' => 'Haber Básico', 'tipo' => 'INGRESO', 'origen' => 'CALCULADO', 'descripcion' => 'Remuneración básica según la configuración salarial vigente.'],
            ['codigo' => 'BON_ANT', 'nombre' => 'Bono de Antigüedad', 'tipo' => 'INGRESO', 'origen' => 'CALCULADO', 'descripcion' => 'Concepto asociado a la antigüedad del trabajador.'],
            ['codigo' => 'BON_CAT', 'nombre' => 'Bono de Categoría', 'tipo' => 'INGRESO', 'origen' => 'CALCULADO', 'descripcion' => 'Bono asociado a la categoría salarial.'],
            ['codigo' => 'BON_FIJO', 'nombre' => 'Bono Fijo', 'tipo' => 'INGRESO', 'origen' => 'MANUAL', 'descripcion' => 'Bono fijo registrado como novedad cuando corresponda.'],
            ['codigo' => 'HRS_NOCT', 'nombre' => 'Horas Nocturnas', 'tipo' => 'INGRESO', 'origen' => 'CALCULADO', 'descripcion' => 'Pago correspondiente a horas nocturnas aprobadas.'],
            ['codigo' => 'HRS_EXTRA', 'nombre' => 'Horas Extra', 'tipo' => 'INGRESO', 'origen' => 'CALCULADO', 'descripcion' => 'Pago correspondiente a horas extra aprobadas.'],
            ['codigo' => 'HRS_DOM', 'nombre' => 'Horas Dominicales', 'tipo' => 'INGRESO', 'origen' => 'CALCULADO', 'descripcion' => 'Pago correspondiente a horas dominicales aprobadas.'],
            ['codigo' => 'HRS_FERIADO', 'nombre' => 'Horas Feriado', 'tipo' => 'INGRESO', 'origen' => 'CALCULADO', 'descripcion' => 'Pago correspondiente a horas de feriado aprobadas.'],
            ['codigo' => 'AFP', 'nombre' => 'Aportes AFP', 'tipo' => 'FISCAL', 'origen' => 'CALCULADO', 'descripcion' => 'Concepto fiscal de aportes y retenciones aplicables.'],
            ['codigo' => 'ANTICIPO', 'nombre' => 'Anticipo', 'tipo' => 'DESCUENTO', 'origen' => 'MANUAL', 'descripcion' => 'Descuento por anticipo autorizado.'],
            ['codigo' => 'RCIVA', 'nombre' => 'RC-IVA', 'tipo' => 'FISCAL', 'origen' => 'CALCULADO', 'descripcion' => 'Concepto fiscal correspondiente al RC-IVA.'],
        ];

        foreach ($conceptos as $concepto) {
            NominaConcepto::firstOrCreate(
                ['codigo' => $concepto['codigo']],
                $concepto + ['activo' => true]
            );
        }
    }
}
