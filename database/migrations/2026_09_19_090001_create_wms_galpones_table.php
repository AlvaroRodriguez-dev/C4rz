<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wms_galpones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('almacen_id')
                ->constrained('wms_almacenes')
                ->cascadeOnDelete();
            $table->string('codigo', 20);
            $table->string('nombre', 100);
            $table->unsignedInteger('desde_ubicacion')->nullable();
            $table->unsignedInteger('hasta_ubicacion')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->unique(['almacen_id', 'codigo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wms_galpones');
    }
};
