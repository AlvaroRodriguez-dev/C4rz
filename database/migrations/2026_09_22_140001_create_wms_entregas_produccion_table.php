<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wms_entregas_produccion', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 30)->unique(); // Ej.: EAPT-008845
            $table->string('folio_fisico', 30)->nullable();
            $table->foreignId('almacen_id')
                ->constrained('wms_almacenes')
                ->restrictOnDelete();
            $table->date('fecha_entrega');
            $table->date('fecha_recepcion')->nullable();
            $table->string('origen', 100)->nullable();
            $table->string('turno_hora', 30)->nullable();
            $table->unsignedInteger('total_declarado')->default(0);
            $table->unsignedInteger('total_fisico')->default(0);
            $table->string('estado', 30)->default('PENDIENTE_VERIFICACION');
            $table->string('rdocum_sas', 20)->nullable()->unique();
            $table->timestamp('posteado_erp_at')->nullable();
            $table->text('observaciones')->nullable();
            $table->text('error_integracion')->nullable();
            $table->foreignId('created_id')->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->foreignId('update_id')->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamps();

            $table->index(['almacen_id', 'estado']);
            $table->index(['fecha_entrega', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wms_entregas_produccion');
    }
};