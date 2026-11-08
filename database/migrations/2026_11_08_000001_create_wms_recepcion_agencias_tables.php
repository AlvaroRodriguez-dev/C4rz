<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wms_recepciones_agencia', function (Blueprint $table) {
            $table->id();
            $table->foreignId('almacen_id')->constrained('wms_almacenes')->restrictOnDelete();
            $table->string('camion', 80)->nullable();
            $table->dateTime('fecha_recepcion');
            $table->string('estado', 20)->default('abierta');
            $table->string('observacion', 500)->nullable();
            $table->foreignId('created_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('update_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['almacen_id', 'estado', 'fecha_recepcion'], 'idx_wms_recep_agencia_estado');
        });

        Schema::create('wms_recepcion_agencia_documentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recepcion_id')->constrained('wms_recepciones_agencia')->cascadeOnDelete();
            $table->string('tipo_registro', 5);
            $table->string('id_registro', 20);
            $table->string('glosa', 150)->nullable();
            $table->date('fecha_documento')->nullable();
            $table->timestamps();
            $table->unique(['tipo_registro', 'id_registro'], 'uq_wms_recep_agencia_documento');
            $table->index('recepcion_id');
        });

        Schema::create('wms_recepcion_agencia_pallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recepcion_id')->constrained('wms_recepciones_agencia')->cascadeOnDelete();
            $table->foreignId('documento_id')->constrained('wms_recepcion_agencia_documentos')->restrictOnDelete();
            $table->string('pallet', 30);
            $table->string('estado', 24)->default('pendiente_descarga');
            $table->foreignId('ubicacion_temporal_id')->nullable()->constrained('wms_ubicaciones')->nullOnDelete();
            $table->foreignId('ubicacion_final_id')->nullable()->constrained('wms_ubicaciones')->nullOnDelete();
            $table->dateTime('descargado_at')->nullable();
            $table->foreignId('descargado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('verificado_at')->nullable();
            $table->foreignId('verificado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('ingresado_at')->nullable();
            $table->foreignId('ingresado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['documento_id', 'pallet'], 'uq_wms_recep_doc_pallet');
            $table->index(['recepcion_id', 'estado'], 'idx_wms_recep_pallet_estado');
        });

        Schema::create('wms_recepcion_agencia_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pallet_id')->constrained('wms_recepcion_agencia_pallets')->cascadeOnDelete();
            $table->string('codigo_esperado', 30)->nullable();
            $table->string('lote_esperado', 30)->nullable();
            $table->unsignedInteger('cantidad_esperada')->default(0);
            $table->string('descrip', 60)->nullable();
            $table->string('descrip1', 60)->nullable();
            $table->string('codigo_fisico', 30)->nullable();
            $table->string('lote_fisico', 30)->nullable();
            $table->unsignedInteger('cantidad_fisica')->nullable();
            $table->string('observacion', 500)->nullable();
            $table->boolean('es_adicional')->default(false);
            $table->timestamps();
            $table->index('pallet_id');
        });

        Schema::create('wms_recepcion_agencia_observaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pallet_id')->constrained('wms_recepcion_agencia_pallets')->cascadeOnDelete();
            $table->foreignId('detalle_id')->nullable()->constrained('wms_recepcion_agencia_detalles')->nullOnDelete();
            $table->string('tipo', 30);
            $table->string('descripcion', 1000);
            $table->string('estado', 20)->default('pendiente');
            $table->foreignId('autorizado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('autorizado_at')->nullable();
            $table->string('comentario_autorizacion', 1000)->nullable();
            $table->timestamps();
            $table->index(['pallet_id', 'estado'], 'idx_wms_recep_obs_estado');
        });

        DB::statement("
            ALTER TABLE wms_ingresos
            MODIFY tipo_ingreso
            ENUM('nota', 'ajuste', 'produccion', 'recepcion', 'recepcion_agencia')
            NOT NULL DEFAULT 'nota'
        ");
    }

    public function down(): void
    {
        if (DB::table('wms_ingresos')->where('tipo_ingreso', 'recepcion_agencia')->exists()) {
            throw new RuntimeException('No se puede revertir: existen ingresos de recepción de agencias.');
        }

        Schema::dropIfExists('wms_recepcion_agencia_observaciones');
        Schema::dropIfExists('wms_recepcion_agencia_detalles');
        Schema::dropIfExists('wms_recepcion_agencia_pallets');
        Schema::dropIfExists('wms_recepcion_agencia_documentos');
        Schema::dropIfExists('wms_recepciones_agencia');

        DB::statement("
            ALTER TABLE wms_ingresos
            MODIFY tipo_ingreso
            ENUM('nota', 'ajuste', 'produccion', 'recepcion')
            NOT NULL DEFAULT 'nota'
        ");
    }
};
