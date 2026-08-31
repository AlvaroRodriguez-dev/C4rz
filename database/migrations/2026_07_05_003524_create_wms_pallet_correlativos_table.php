<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wms_pallet_correlativos', function (Blueprint $table) {
            $table->id();
            $table->string('anio', 2);
            $table->unsignedInteger('correlativo');
            $table->timestamps();

            $table->unique('anio');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wms_pallet_correlativos');
    }
};