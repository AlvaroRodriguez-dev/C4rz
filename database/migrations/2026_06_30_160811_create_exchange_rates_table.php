<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exchange_rates', function (Blueprint $table) {
            $table->id();
            $table->date('date')->unique(); // Índice único para evitar duplicar la tasa del mismo día
            $table->decimal('rate', 10, 4); // 4 decimales da precisión para transacciones monetarias
            $table->boolean('is_manual')->default(false); // Para auditoría si alguien lo edita a mano
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exchange_rates');
    }
};
