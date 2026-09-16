<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // SOLICITUD -> EVALUACION -> COTIZACION -> COMPARACION -> APROBACION
        // -> ORDEN DE COMPRA -> RECEPCION -> SUMINISTROS -> IDENTIFICACION -> ACTIVO
        Schema::create('it_solicitudes', function (Blueprint $table) {
            $table->id();
            $table->string('numero', 30)->unique();
            $table->unsignedBigInteger('solicitante_id')->index(); // rrhh_personal.id
            $table->unsignedBigInteger('area_id')->nullable()->index(); // RRHH area_id
            $table->unsignedBigInteger('ubicacion_id')->nullable();
            $table->date('fecha_solicitud');
            $table->string('prioridad', 20)->default('NORMAL');
            $table->string('estado', 30)->default('PENDIENTE');
            $table->string('motivo', 255)->nullable();
            $table->text('descripcion')->nullable();
            $table->unsignedBigInteger('responsable_id')->nullable()->index(); // users.id
            $table->timestamp('fecha_cierre')->nullable();
            $table->text('observaciones')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->index(['estado', 'prioridad']);
            $table->foreign('ubicacion_id')->references('id')->on('it_ubicaciones')->restrictOnDelete();
            $table->foreign('responsable_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('it_solicitud_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('solicitud_id')->constrained('it_solicitudes')->cascadeOnDelete();
            $table->string('tipo_item', 20); // ACTIVO, COMPONENTE, ACCESORIO, SERVICIO
            $table->foreignId('tipo_activo_id')->nullable()->constrained('it_tipos_activo')->restrictOnDelete();
            $table->string('descripcion_solicitada', 255);
            $table->unsignedInteger('cantidad')->default(1);
            $table->text('especificaciones')->nullable();
            $table->string('unidad', 30)->default('UNIDAD');
            $table->string('estado', 30)->default('PENDIENTE');
            $table->unsignedInteger('cantidad_aprobada')->nullable();
            $table->unsignedInteger('cantidad_comprada')->default(0);
            $table->unsignedInteger('cantidad_recibida')->default(0);
            $table->unsignedInteger('cantidad_identificada')->default(0);
            $table->text('observaciones')->nullable();
            $table->timestamps();
            $table->index(['solicitud_id', 'estado']);
            $table->index(['tipo_item', 'tipo_activo_id']);
        });

        Schema::create('it_evaluaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('solicitud_id')->constrained('it_solicitudes')->cascadeOnDelete();
            $table->unsignedBigInteger('evaluador_id')->index(); // users.id
            $table->dateTime('fecha_evaluacion');
            $table->string('resultado', 30); // REASIGNACION, STOCK, REPARACION, COMPRA, MEJORA, OTRO
            $table->text('justificacion')->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();
            $table->foreign('evaluador_id')->references('id')->on('users')->restrictOnDelete();
        });

        Schema::create('it_evaluacion_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evaluacion_id')->constrained('it_evaluaciones')->cascadeOnDelete();
            $table->foreignId('solicitud_detalle_id')->constrained('it_solicitud_detalles')->restrictOnDelete();
            $table->string('resultado', 30);
            $table->unsignedInteger('cantidad')->default(0);
            $table->text('observaciones')->nullable();
            $table->timestamps();
            $table->unique(['evaluacion_id', 'solicitud_detalle_id'], 'it_eval_det_uq');
        });

        Schema::create('it_cotizaciones', function (Blueprint $table) {
            $table->id();
            $table->string('numero', 40)->unique();
            $table->unsignedBigInteger('proveedor_id')->index(); // maestro externo
            $table->date('fecha');
            $table->date('fecha_vencimiento')->nullable();
            $table->string('moneda', 10)->default('BOB');
            $table->decimal('tipo_cambio', 15, 6)->nullable();
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('impuestos', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);
            $table->string('estado', 30)->default('REGISTRADA');
            $table->string('archivo', 500)->nullable();
            $table->text('observaciones')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('it_cotizacion_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cotizacion_id')->constrained('it_cotizaciones')->cascadeOnDelete();
            $table->string('descripcion', 255);
            $table->unsignedInteger('cantidad')->default(1);
            $table->string('unidad', 30)->default('UNIDAD');
            $table->decimal('precio_unitario', 15, 2)->default(0);
            $table->decimal('descuento', 15, 2)->default(0);
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->text('especificaciones')->nullable();
            $table->timestamps();
            $table->index(['cotizacion_id']);
        });

        Schema::create('it_cotizacion_detalle_solicitudes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cotizacion_detalle_id')->constrained('it_cotizacion_detalles')->cascadeOnDelete();
            $table->foreignId('solicitud_detalle_id')->constrained('it_solicitud_detalles')->restrictOnDelete();
            $table->unsignedInteger('cantidad')->default(1);
            $table->timestamps();
            $table->unique(['cotizacion_detalle_id', 'solicitud_detalle_id'], 'it_cot_sol_uq');
        });

        Schema::create('it_comparaciones', function (Blueprint $table) {
            $table->id();
            $table->string('numero', 40)->unique();
            $table->date('fecha');
            $table->unsignedBigInteger('responsable_id')->index();
            $table->string('estado', 30)->default('EN_ELABORACION');
            $table->unsignedBigInteger('cotizacion_recomendada_id')->nullable();
            $table->text('criterio_recomendacion')->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();
            $table->foreign('responsable_id')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('cotizacion_recomendada_id')->references('id')->on('it_cotizaciones')->nullOnDelete();
        });

        Schema::create('it_comparacion_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('comparacion_id')->constrained('it_comparaciones')->cascadeOnDelete();
            $table->foreignId('cotizacion_id')->constrained('it_cotizaciones')->restrictOnDelete();
            $table->decimal('total_evaluado', 15, 2)->default(0);
            $table->unsignedInteger('puntaje')->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();
            $table->unique(['comparacion_id', 'cotizacion_id'], 'it_comp_cot_uq');
        });

        Schema::create('it_aprobaciones', function (Blueprint $table) {
            $table->id();
            $table->string('tipo_documento', 30); // SOLICITUD, COMPRA, COMPARACION, ORDEN
            $table->unsignedBigInteger('documento_id');
            $table->string('tipo_aprobacion', 30); // PRESUPUESTO, SGAF, GERENCIA_GENERAL, etc.
            $table->unsignedBigInteger('aprobador_id')->nullable()->index();
            $table->string('estado', 20)->default('PENDIENTE');
            $table->dateTime('fecha_decision')->nullable();
            $table->text('comentario')->nullable();
            $table->string('archivo', 500)->nullable();
            $table->timestamps();
            $table->index(['tipo_documento', 'documento_id', 'estado']);
            $table->foreign('aprobador_id')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('it_ordenes_compra', function (Blueprint $table) {
            $table->id();
            $table->string('numero', 40)->unique();
            $table->unsignedBigInteger('proveedor_id')->index(); // maestro externo
            $table->foreignId('comparacion_id')->nullable()->constrained('it_comparaciones')->nullOnDelete();
            $table->date('fecha');
            $table->string('moneda', 10)->default('BOB');
            $table->decimal('tipo_cambio', 15, 6)->nullable();
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('impuestos', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);
            $table->string('estado', 30)->default('EMITIDA');
            $table->date('fecha_entrega_estimada')->nullable();
            $table->text('condiciones')->nullable();
            $table->text('observaciones')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('it_orden_compra_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('orden_compra_id')->constrained('it_ordenes_compra')->cascadeOnDelete();
            $table->string('descripcion', 255);
            $table->string('tipo_item', 20);
            $table->foreignId('tipo_activo_id')->nullable()->constrained('it_tipos_activo')->restrictOnDelete();
            $table->unsignedInteger('cantidad')->default(1);
            $table->string('unidad', 30)->default('UNIDAD');
            $table->decimal('precio_unitario', 15, 2)->default(0);
            $table->decimal('descuento', 15, 2)->default(0);
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->unsignedInteger('cantidad_recibida')->default(0);
            $table->text('especificaciones')->nullable();
            $table->timestamps();
            $table->index(['orden_compra_id', 'tipo_item']);
        });

        Schema::create('it_orden_detalle_solicitudes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('orden_compra_detalle_id')->constrained('it_orden_compra_detalles')->cascadeOnDelete();
            $table->foreignId('solicitud_detalle_id')->constrained('it_solicitud_detalles')->restrictOnDelete();
            $table->unsignedInteger('cantidad')->default(1);
            $table->timestamps();
            $table->unique(['orden_compra_detalle_id', 'solicitud_detalle_id'], 'it_ord_sol_uq');
        });

        Schema::create('it_recepciones', function (Blueprint $table) {
            $table->id();
            $table->string('numero', 40)->unique();
            $table->foreignId('orden_compra_id')->nullable()->constrained('it_ordenes_compra')->nullOnDelete();
            $table->date('fecha');
            $table->unsignedBigInteger('recibido_por')->index();
            $table->string('estado', 30)->default('PENDIENTE_IDENTIFICACION');
            $table->string('documento_suministros', 50)->nullable()->index();
            $table->unsignedInteger('estado_suministros')->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();
            $table->foreign('recibido_por')->references('id')->on('users')->restrictOnDelete();
        });

        Schema::create('it_recepcion_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recepcion_id')->constrained('it_recepciones')->cascadeOnDelete();
            $table->foreignId('orden_compra_detalle_id')->nullable()->constrained('it_orden_compra_detalles')->nullOnDelete();
            $table->string('codigo_suministros', 100)->nullable();
            $table->string('descripcion', 255);
            $table->unsignedInteger('cantidad_recibida')->default(0);
            $table->unsignedInteger('cantidad_identificada')->default(0);
            $table->unsignedInteger('cantidad_pendiente')->default(0);
            $table->decimal('costo_unitario', 15, 2)->nullable();
            $table->unsignedBigInteger('suministros_rdocum')->nullable()->index();
            $table->unsignedBigInteger('suministros_orden')->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();
            $table->index(['suministros_rdocum', 'suministros_orden'], 'it_recep_simec_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('it_recepcion_detalles');
        Schema::dropIfExists('it_recepciones');
        Schema::dropIfExists('it_orden_detalle_solicitudes');
        Schema::dropIfExists('it_orden_compra_detalles');
        Schema::dropIfExists('it_ordenes_compra');
        Schema::dropIfExists('it_aprobaciones');
        Schema::dropIfExists('it_comparacion_detalles');
        Schema::dropIfExists('it_comparaciones');
        Schema::dropIfExists('it_cotizacion_detalle_solicitudes');
        Schema::dropIfExists('it_cotizacion_detalles');
        Schema::dropIfExists('it_cotizaciones');
        Schema::dropIfExists('it_evaluacion_detalles');
        Schema::dropIfExists('it_evaluaciones');
        Schema::dropIfExists('it_solicitud_detalles');
        Schema::dropIfExists('it_solicitudes');
    }
};
