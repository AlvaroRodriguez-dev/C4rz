<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wms_hu_discrepancias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hu_id')->constrained('wms_hu')->restrictOnDelete();
            $table->foreignId('entrega_id')->constrained('wms_entregas_produccion')->restrictOnDelete();
            $table->foreignId('hu_detalle_id')->nullable()->constrained('wms_hu_detalle')->nullOnDelete();
            $table->string('tipo', 30);
            $table->text('valor_esperado')->nullable();
            $table->text('valor_observado');
            $table->text('observacion')->nullable();
            $table->string('estado', 30)->default('PENDIENTE_CORRECCION');
            $table->foreignId('reportado_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reportado_at')->nullable();
            $table->foreignId('corregido_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('corregido_at')->nullable();
            $table->text('valor_corregido')->nullable();
            $table->timestamps();

            $table->index(['entrega_id', 'estado']);
            $table->index(['hu_id', 'tipo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wms_hu_discrepancias');
    }
};
