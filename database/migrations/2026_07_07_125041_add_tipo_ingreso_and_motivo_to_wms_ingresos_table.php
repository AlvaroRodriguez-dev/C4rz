<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wms_ingresos', function (Blueprint $table) {
            $table->enum('tipo_ingreso', ['nota', 'ajuste'])->default('nota')->after('id');
            $table->string('motivo', 200)->nullable()->after('rfecha');

            // rdocum ya existe; para 'ajuste' guardará el código sintético (ej. AJUSTE-2026-00001)
        });
    }

    public function down(): void
    {
        Schema::table('wms_ingresos', function (Blueprint $table) {
            $table->dropColumn(['tipo_ingreso', 'motivo']);
        });
    }
};