<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wms_hu_detalle', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hu_id')
                ->constrained('wms_hu')
                ->cascadeOnDelete();
            $table->foreignId('entrega_detalle_id')
                ->nullable()
                ->constrained('wms_entregas_detalle')
                ->nullOnDelete();
            $table->string('codigo', 30);
            $table->string('lote', 30)->nullable();
            $table->string('descripcion', 60)->nullable();
            $table->string('descripcion2', 60)->nullable();
            $table->string('formato', 20)->nullable();
            $table->string('calidad', 20)->nullable();
            $table->unsignedInteger('cantidad')->default(0);
            $table->timestamps();

            $table->index(['hu_id', 'codigo']);
            $table->index(['codigo', 'lote']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wms_hu_detalle');
    }
};