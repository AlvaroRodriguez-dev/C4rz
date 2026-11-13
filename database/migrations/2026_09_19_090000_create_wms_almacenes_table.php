<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wms_almacenes', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 10)->unique();
            $table->string('nombre', 100);
            $table->string('tipo', 10)->default('PRINCIPAL');
            $table->foreignId('almacen_padre_id')->nullable()
                ->constrained('wms_almacenes')
                ->nullOnDelete();
            $table->string('prefijo_documento', 5)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index(['tipo', 'activo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wms_almacenes');
    }
};
