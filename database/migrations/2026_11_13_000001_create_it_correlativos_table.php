<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('it_correlativos', function (Blueprint $table) {
            $table->id();
            $table->string('tipo', 30);
            $table->unsignedSmallInteger('anio');
            $table->unsignedBigInteger('ultimo_numero')->default(0);
            $table->timestamps();
            $table->unique(['tipo', 'anio'], 'it_correlativos_tipo_anio_uq');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('it_correlativos');
    }
};
