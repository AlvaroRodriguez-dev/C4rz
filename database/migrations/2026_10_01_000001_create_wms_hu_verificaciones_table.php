<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wms_hu_verificaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hu_id')->constrained('wms_hu')->cascadeOnDelete();
            $table->foreignId('entrega_id')->constrained('wms_entregas_produccion')->cascadeOnDelete();
            $table->unsignedInteger('cantidad_esperada');
            $table->unsignedInteger('cantidad_verificada');
            $table->integer('diferencia')->default(0);
            $table->string('resultado', 30);
            $table->text('observacion')->nullable();
            $table->foreignId('verificado_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verificado_at')->nullable();
            $table->foreignId('created_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('update_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique('hu_id');
            $table->index(['entrega_id', 'resultado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wms_hu_verificaciones');
    }
};
