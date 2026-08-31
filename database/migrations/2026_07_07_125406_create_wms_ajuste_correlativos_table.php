<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wms_ajuste_correlativos', function (Blueprint $table) {
            $table->id();
            $table->string('anio', 4);
            $table->unsignedInteger('correlativo')->default(0);
            $table->timestamps();

            $table->unique('anio');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wms_ajuste_correlativos');
    }
};