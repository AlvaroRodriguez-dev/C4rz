<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wms_entregas_detalle', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entrega_id')
                ->constrained('wms_entregas_produccion')
                ->cascadeOnDelete();
            $table->unsignedInteger('orden')->default(1);
            $table->string('codigo', 30);
            $table->string('descripcion', 60)->nullable();
            $table->string('descripcion2', 60)->nullable();
            $table->string('calidad', 20)->nullable();
            $table->string('modelo', 30)->nullable();
            $table->string('formato', 20)->nullable();
            $table->string('lote', 30);
            $table->unsignedInteger('cantidad_declarada')->default(0);
            $table->unsignedInteger('cantidad_paletizada')->default(0);
            $table->string('tono', 10)->nullable();
            $table->string('calibre', 10)->nullable();
            $table->string('estado', 20)->default('PENDIENTE');
            $table->text('observacion')->nullable();
            $table->timestamps();

            $table->index(['entrega_id', 'estado']);
            $table->index(['codigo', 'lote']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wms_entregas_detalle');
    }
};