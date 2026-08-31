<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wms_salidas', function (Blueprint $table) {
            $table->id();
            $table->string('tipo_registro', 5);
            $table->string('id_registro', 20);
            $table->string('glosa', 150)->nullable();
            $table->string('pallet', 30);
            $table->string('codigo', 30);
            $table->string('clote', 30)->nullable();
            $table->string('descrip', 60)->nullable();
            $table->string('descrip1', 60)->nullable();
            $table->unsignedInteger('cantidad');
            $table->string('almacen', 10)->default('110');
            $table->string('galpon', 20);
            $table->string('ubicacion', 20);
            $table->timestamps();

            $table->index('id_registro');
            $table->index(['codigo', 'clote', 'pallet']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wms_salidas');
    }
};