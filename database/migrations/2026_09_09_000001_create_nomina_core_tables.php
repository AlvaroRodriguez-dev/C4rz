<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nomina_periodos', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('anio');
            $table->unsignedTinyInteger('mes');
            $table->string('descripcion', 100);
            $table->string('estado', 30)->default('DRAFT');
            $table->unsignedBigInteger('version_vigente_id')->nullable();
            $table->timestamp('fecha_cierre')->nullable();
            $table->unsignedBigInteger('cerrado_por')->nullable();
            $table->timestamps();

            $table->unique(['anio', 'mes']);
            $table->index('estado');
        });

        Schema::create('nomina_importaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('periodo_id')->constrained('nomina_periodos')->cascadeOnDelete();
            $table->string('archivo_nombre', 255);
            $table->string('archivo_hash', 64);
            $table->unsignedInteger('total_filas')->default(0);
            $table->unsignedInteger('filas_validas')->default(0);
            $table->unsignedInteger('filas_con_error')->default(0);
            $table->string('estado', 30)->default('PENDIENTE');
            $table->text('observaciones')->nullable();
            $table->unsignedBigInteger('importado_por')->nullable();
            $table->timestamps();

            $table->index(['periodo_id', 'estado']);
            $table->index('archivo_hash');
        });

        Schema::create('nomina_entradas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('periodo_id')->constrained('nomina_periodos')->cascadeOnDelete();
            $table->foreignId('importacion_id')->nullable()->constrained('nomina_importaciones')->nullOnDelete();
            $table->string('ci', 30);
            $table->string('nombre_apellido', 180);
            $table->decimal('horas_trabajadas', 10, 2)->default(0);
            $table->decimal('horas_nocturnas', 10, 2)->default(0);
            $table->decimal('horas_dominicales', 10, 2)->default(0);
            $table->decimal('horas_feriados', 10, 2)->default(0);
            $table->decimal('horas_extra', 10, 2)->default(0);
            $table->decimal('dias_faltas', 8, 2)->default(0);
            $table->unsignedInteger('minutos_atrasados')->default(0);
            $table->decimal('dias_vacaciones', 8, 2)->default(0);
            $table->decimal('dias_bajas_medicas', 8, 2)->default(0);
            $table->decimal('dias_salario_dominical', 8, 2)->default(0);
            $table->string('estado', 30)->default('VALIDADA');
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->unique(['periodo_id', 'ci']);
            $table->index(['periodo_id', 'estado']);
            $table->index('ci');
        });

        Schema::create('nomina_conceptos', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->unique();
            $table->string('nombre', 150);
            $table->string('tipo', 30); // INGRESO, DESCUENTO, FISCAL
            $table->string('origen', 30)->default('CALCULADO'); // MANUAL, IMPORTADO, CALCULADO
            $table->boolean('activo')->default(true);
            $table->text('descripcion')->nullable();
            $table->timestamps();
        });

        Schema::create('nomina_parametros', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 80)->unique();
            $table->string('nombre', 150);
            $table->decimal('valor_numerico', 14, 6)->nullable();
            $table->string('valor_texto', 255)->nullable();
            $table->date('vigente_desde');
            $table->date('vigente_hasta')->nullable();
            $table->boolean('activo')->default(true);
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->index(['codigo', 'vigente_desde', 'vigente_hasta']);
        });

        Schema::create('nomina_horas_especiales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('periodo_id')->constrained('nomina_periodos')->cascadeOnDelete();
            $table->string('ci', 30);
            $table->string('tipo', 30); // NOCTURNA, EXTRA, DOMINICAL, FERIADO
            $table->decimal('horas_registradas', 10, 2)->default(0);
            $table->decimal('horas_aprobadas', 10, 2)->default(0);
            $table->string('estado', 30)->default('REGISTRADA');
            $table->unsignedBigInteger('jefe_aprobador_id')->nullable();
            $table->timestamp('jefe_aprobado_at')->nullable();
            $table->unsignedBigInteger('rrhh_aprobador_id')->nullable();
            $table->timestamp('rrhh_aprobado_at')->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->index(['periodo_id', 'ci', 'tipo']);
            $table->index('estado');
        });

        Schema::create('nomina_bonos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('periodo_id')->constrained('nomina_periodos')->cascadeOnDelete();
            $table->string('ci', 30);
            $table->foreignId('concepto_id')->constrained('nomina_conceptos');
            $table->decimal('importe', 14, 2);
            $table->string('estado', 30)->default('PENDIENTE');
            $table->unsignedBigInteger('autorizado_por')->nullable();
            $table->timestamp('autorizado_at')->nullable();
            $table->text('motivo')->nullable();
            $table->timestamps();

            $table->index(['periodo_id', 'ci']);
        });

        Schema::create('nomina_descuentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('periodo_id')->constrained('nomina_periodos')->cascadeOnDelete();
            $table->string('ci', 30);
            $table->foreignId('concepto_id')->constrained('nomina_conceptos');
            $table->decimal('importe', 14, 2);
            $table->string('origen', 30)->default('MANUAL'); // MANUAL, ATRASOS, FALTAS, CALCULADO
            $table->string('estado', 30)->default('PENDIENTE');
            $table->unsignedBigInteger('registrado_por')->nullable();
            $table->text('motivo')->nullable();
            $table->timestamps();

            $table->index(['periodo_id', 'ci']);
        });

        Schema::create('nomina_corridas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('periodo_id')->constrained('nomina_periodos')->cascadeOnDelete();
            $table->unsignedInteger('numero')->default(1);
            $table->string('estado', 30)->default('GENERADA');
            $table->unsignedBigInteger('ejecutado_por')->nullable();
            $table->timestamp('ejecutado_at')->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->unique(['periodo_id', 'numero']);
        });

        Schema::create('nomina_versiones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('periodo_id')->constrained('nomina_periodos')->cascadeOnDelete();
            $table->foreignId('corrida_id')->constrained('nomina_corridas')->cascadeOnDelete();
            $table->unsignedInteger('numero');
            $table->string('estado', 30)->default('BORRADOR');
            $table->unsignedBigInteger('creada_por')->nullable();
            $table->timestamp('creada_at')->nullable();
            $table->text('motivo')->nullable();
            $table->timestamps();

            $table->unique(['periodo_id', 'numero']);
            $table->index(['periodo_id', 'estado']);
        });

        Schema::create('nomina_resultados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('version_id')->constrained('nomina_versiones')->cascadeOnDelete();
            $table->string('ci', 30);
            $table->string('nombre_apellido', 180);
            $table->decimal('total_ingresos', 14, 2)->default(0);
            $table->decimal('total_descuentos', 14, 2)->default(0);
            $table->decimal('total_fiscal', 14, 2)->default(0);
            $table->decimal('liquido_pagable', 14, 2)->default(0);
            $table->timestamps();

            $table->unique(['version_id', 'ci']);
            $table->index('ci');
        });

        Schema::create('nomina_resultado_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resultado_id')->constrained('nomina_resultados')->cascadeOnDelete();
            $table->foreignId('concepto_id')->constrained('nomina_conceptos');
            $table->decimal('cantidad', 14, 4)->nullable();
            $table->decimal('base', 14, 4)->nullable();
            $table->decimal('tasa', 14, 6)->nullable();
            $table->decimal('importe', 14, 2)->default(0);
            $table->text('formula_aplicada')->nullable();
            $table->timestamps();

            $table->index(['resultado_id', 'concepto_id']);
        });

        Schema::create('nomina_aprobaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('periodo_id')->constrained('nomina_periodos')->cascadeOnDelete();
            $table->foreignId('version_id')->nullable()->constrained('nomina_versiones')->nullOnDelete();
            $table->string('etapa', 30); // RRHH, SGAF, GG
            $table->string('accion', 30); // APROBADO, RECHAZADO
            $table->unsignedBigInteger('usuario_id');
            $table->timestamp('fecha')->useCurrent();
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->index(['periodo_id', 'etapa']);
        });

        Schema::create('nomina_auditoria', function (Blueprint $table) {
            $table->id();
            $table->foreignId('periodo_id')->nullable()->constrained('nomina_periodos')->nullOnDelete();
            $table->string('entidad', 100);
            $table->unsignedBigInteger('entidad_id')->nullable();
            $table->string('accion', 30);
            $table->unsignedBigInteger('usuario_id')->nullable();
            $table->json('datos_anteriores')->nullable();
            $table->json('datos_nuevos')->nullable();
            $table->text('motivo')->nullable();
            $table->timestamp('fecha')->useCurrent();
            $table->timestamps();

            $table->index(['entidad', 'entidad_id']);
            $table->index(['periodo_id', 'fecha']);
        });
    }

    public function down(): void
    {
        $tables = [
            'nomina_auditoria',
            'nomina_aprobaciones',
            'nomina_resultado_detalles',
            'nomina_resultados',
            'nomina_versiones',
            'nomina_corridas',
            'nomina_descuentos',
            'nomina_bonos',
            'nomina_horas_especiales',
            'nomina_parametros',
            'nomina_conceptos',
            'nomina_entradas',
            'nomina_importaciones',
            'nomina_periodos',
        ];

        foreach ($tables as $table) {
            Schema::dropIfExists($table);
        }
    }
};