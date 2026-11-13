<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wms_usuario_almacenes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->foreignId('almacen_id')
                ->constrained('wms_almacenes')
                ->cascadeOnDelete();
            $table->boolean('activo')->default(true);
            $table->boolean('es_principal')->default(false);
            $table->timestamps();

            $table->unique(['user_id', 'almacen_id']);
            $table->index(['user_id', 'activo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wms_usuario_almacenes');
    }
};
