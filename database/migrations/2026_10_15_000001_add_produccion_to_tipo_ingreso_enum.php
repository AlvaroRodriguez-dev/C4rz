<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE wms_ingresos MODIFY tipo_ingreso ENUM('nota', 'ajuste', 'produccion') NOT NULL DEFAULT 'nota'");
    }

    public function down(): void
    {
        // Se conserva el valor 'produccion' para no invalidar ingresos de producción ya registrados.
    }
};
