<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wms_ubicaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('almacen_id')
                ->constrained('wms_almacenes')
                ->cascadeOnDelete();
            $table->foreignId('galpon_id')
                ->nullable()
                ->constrained('wms_galpones')
                ->nullOnDelete();
            $table->string('codigo', 30);
            $table->string('tipo', 20)->default('NORMAL');
            $table->unsignedInteger('numero')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->unique(['almacen_id', 'codigo']);
            $table->index(['almacen_id', 'tipo', 'activo']);
            $table->index(['galpon_id', 'numero']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wms_ubicaciones');
    }
};
