<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('indicadores_procesadores', function (Blueprint $table) {
            $table->id();
            $table->string('fabricante', 40);
            $table->string('modelo', 120);
            $table->string('modelo_normalizado', 120)->unique();
            $table->date('fecha_lanzamiento');
            $table->string('precision_fecha', 20)->default('mes');
            $table->string('fuente_url', 500)->nullable();
            $table->date('verificado_en')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->index(['fabricante', 'activo']);
        });

        Schema::table('indicadores_ocs_equipos', function (Blueprint $table) {
            $table->foreignId('procesador_id')->nullable()->after('tipo_cpu')
                ->constrained('indicadores_procesadores')->nullOnDelete();
            $table->string('origen_fecha_compra', 30)->nullable()->after('fecha_compra_estimada');
        });
    }

    public function down(): void
    {
        Schema::table('indicadores_ocs_equipos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('procesador_id');
            $table->dropColumn('origen_fecha_compra');
        });
        Schema::dropIfExists('indicadores_procesadores');
    }
};
