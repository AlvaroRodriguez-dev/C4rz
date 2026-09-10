<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nomina_personal', function (Blueprint $table) {
            $table->id();
            $table->string('license', 30)->unique();
            $table->string('estado', 30)->default('ACTIVO');
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->index('estado');
        });

        Schema::create('nomina_categorias', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->unique();
            $table->string('nombre', 150);
            $table->text('descripcion')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index('activo');
        });

        Schema::create('nomina_configuraciones_laborales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nomina_personal_id')->constrained('nomina_personal')->cascadeOnDelete();
            $table->date('fecha_inicio');
            $table->date('fecha_fin')->nullable();
            $table->string('area', 100)->nullable();
            $table->string('regional', 100)->nullable();
            $table->string('centro_costo', 100)->nullable();
            $table->string('tipo_contrato', 100)->nullable();
            $table->string('clasificacion_laboral', 100)->nullable();
            $table->string('codigo_simec', 50)->nullable();
            $table->string('codigo_seguro_social', 80)->nullable();
            $table->date('fecha_ingreso')->nullable();
            $table->date('fecha_retiro')->nullable();
            $table->boolean('es_fiscal')->default(false);
            $table->boolean('es_interna')->default(false);
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->index(['nomina_personal_id', 'fecha_inicio', 'fecha_fin'], 'nomina_lab_vigencia_idx');
            $table->index(['centro_costo', 'regional']);
        });

        Schema::create('nomina_configuraciones_salariales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nomina_personal_id')->constrained('nomina_personal')->cascadeOnDelete();
            $table->date('fecha_inicio');
            $table->date('fecha_fin')->nullable();
            $table->decimal('haber_basico', 14, 2)->default(0);
            $table->foreignId('categoria_id')->nullable()->constrained('nomina_categorias')->nullOnDelete();
            $table->string('modalidad_remuneracion', 80)->nullable();
            $table->decimal('salario_cotizable', 14, 2)->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->index(['nomina_personal_id', 'fecha_inicio', 'fecha_fin'], 'nomina_sal_vigencia_idx');
            $table->index('categoria_id');
        });

        Schema::create('nomina_personal_conceptos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nomina_personal_id')->constrained('nomina_personal')->cascadeOnDelete();
            $table->foreignId('concepto_id')->constrained('nomina_conceptos');
            $table->date('fecha_inicio');
            $table->date('fecha_fin')->nullable();
            $table->string('tipo_valor', 30)->default('IMPORTE'); // IMPORTE, PORCENTAJE, CANTIDAD
            $table->decimal('valor', 14, 6)->default(0);
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->index(['nomina_personal_id', 'concepto_id', 'fecha_inicio'], 'nomina_personal_concepto_vigencia_idx');
            $table->index(['concepto_id', 'fecha_inicio', 'fecha_fin'], 'nomina_concepto_vigencia_idx');
        });

        Schema::create('nomina_cuentas_pago', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nomina_personal_id')->constrained('nomina_personal')->cascadeOnDelete();
            $table->date('fecha_inicio');
            $table->date('fecha_fin')->nullable();
            $table->string('institucion_bancaria', 150)->nullable();
            $table->string('cuenta_bancaria', 100)->nullable();
            $table->string('tipo_pago', 30)->default('CUENTA'); // CUENTA, EFECTIVO
            $table->boolean('principal')->default(true);
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->index(['nomina_personal_id', 'fecha_inicio', 'fecha_fin'], 'nomina_pago_vigencia_idx');
            $table->index(['nomina_personal_id', 'principal']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nomina_cuentas_pago');
        Schema::dropIfExists('nomina_personal_conceptos');
        Schema::dropIfExists('nomina_configuraciones_salariales');
        Schema::dropIfExists('nomina_configuraciones_laborales');
        Schema::dropIfExists('nomina_categorias');
        Schema::dropIfExists('nomina_personal');
    }
};
