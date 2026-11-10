<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('indicadores_ocs_importaciones', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('periodo_anio');
            $table->unsignedTinyInteger('periodo_mes');
            $table->string('archivo_original');
            $table->string('archivo_hash', 64);
            $table->unsignedInteger('total_registros')->default(0);
            $table->unsignedInteger('total_computadoras')->default(0);
            $table->unsignedInteger('so_sin_soporte')->default(0);
            $table->unsignedInteger('so_pendiente_verificacion')->default(0);
            $table->unsignedInteger('ram_hasta_4gb')->default(0);
            $table->unsignedInteger('antiguedad_mayor_5')->default(0);
            $table->unsignedInteger('sin_fecha_compra')->default(0);
            $table->decimal('porcentaje_so_sin_soporte', 7, 2)->default(0);
            $table->decimal('porcentaje_ram_hasta_4gb', 7, 2)->default(0);
            $table->decimal('porcentaje_antiguedad_mayor_5', 7, 2)->default(0);
            $table->string('semaforo_so_sin_soporte', 20);
            $table->string('semaforo_ram_hasta_4gb', 20);
            $table->string('semaforo_antiguedad_mayor_5', 20);
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['periodo_anio', 'periodo_mes']);
            $table->unique('archivo_hash');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('indicadores_ocs_importaciones');
    }
};
