<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wms_galpon_rangos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('galpon_id')
                ->constrained('wms_galpones')
                ->cascadeOnDelete();
            $table->unsignedInteger('desde');
            $table->unsignedInteger('hasta');
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->unique(['galpon_id', 'desde', 'hasta']);
            $table->index(['galpon_id', 'activo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wms_galpon_rangos');
    }
};
