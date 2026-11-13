<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wms_hu', function (Blueprint $table) {
            $table->id();
            $table->string('numero', 30)->unique();
            $table->foreignId('entrega_id')
                ->nullable()
                ->constrained('wms_entregas_produccion')
                ->nullOnDelete();
            $table->foreignId('almacen_id')
                ->constrained('wms_almacenes')
                ->restrictOnDelete();
            $table->foreignId('ubicacion_id')
                ->nullable()
                ->constrained('wms_ubicaciones')
                ->restrictOnDelete();
            $table->string('formato', 20)->nullable();
            $table->unsignedInteger('capacidad_estandar')->nullable();
            $table->unsignedInteger('cantidad_total')->default(0);
            $table->string('tipo', 20)->default('NORMAL'); // COMPLETO / SALDO se determina por cantidad
            $table->string('estado', 30)->default('EN_RECEPCION');
            $table->timestamp('ubicado_at')->nullable();
            $table->foreignId('created_id')->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->foreignId('update_id')->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamps();

            $table->index(['almacen_id', 'estado']);
            $table->index(['entrega_id', 'estado']);
            $table->index(['ubicacion_id', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wms_hu');
    }
};