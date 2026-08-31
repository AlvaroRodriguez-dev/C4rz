<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wms_config_pallets', function (Blueprint $table) {
            $table->string('codigo', 4)->primary();
            $table->string('descripcion', 20);
            $table->unsignedInteger('cajas_x_pallet')->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wms_config_pallets');
    }
};