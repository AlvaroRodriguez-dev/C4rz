
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            ALTER TABLE wms_ingresos
            MODIFY tipo_ingreso
            ENUM('nota', 'ajuste', 'produccion', 'recepcion')
            NOT NULL DEFAULT 'nota'
        ");
    }

    public function down(): void
    {
        // No eliminar el tipo si ya existen ingresos de recepción.
        if (
            DB::table('wms_ingresos')
                ->where('tipo_ingreso', 'recepcion')
                ->exists()
        ) {
            throw new RuntimeException(
                'No se puede revertir la migración: existen ingresos de tipo recepcion.'
            );
        }

        DB::statement("
            ALTER TABLE wms_ingresos
            MODIFY tipo_ingreso
            ENUM('nota', 'ajuste', 'produccion')
            NOT NULL DEFAULT 'nota'
        ");
    }
};
