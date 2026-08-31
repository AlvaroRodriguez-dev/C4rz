<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wms_ingresos', function (Blueprint $table) {
            $table->id();
            $table->string('rdocum', 20);        // referencia a la nota (trazabilidad)
            $table->date('rfecha');               // fecha de la nota
            $table->string('pallet', 30);
            $table->string('codigo', 30);
            $table->string('descrip', 60)->nullable();
            $table->string('descrip1', 60)->nullable();
            $table->unsignedInteger('cantidad');
            $table->string('almacen', 10)->default('110');
            $table->string('galpon', 20);
            $table->string('ubicacion', 20);
            $table->timestamps();

            $table->index('rdocum');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wms_ingresos');
    }
};