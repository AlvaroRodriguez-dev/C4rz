<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wms_orden_trabajo_detalle', function (Blueprint $table) {
            $table->id();
            $table->foreignId('orden_trabajo_id')->constrained('wms_ordenes_trabajo')->cascadeOnDelete();

            $table->string('pallet', 30);
            $table->string('codigo', 30);
            $table->string('clote', 30)->nullable();
            $table->string('descrip', 60)->nullable();
            $table->string('descrip1', 60)->nullable();
            $table->unsignedInteger('cantidad'); // cantidad autorizada a despachar de esa combinación

            $table->string('almacen_origen', 10);
            $table->string('galpon_origen', 20);
            $table->string('ubicacion_origen', 20);

            $table->boolean('chequeado')->default(false);
            $table->foreignId('chequeado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('chequeado_at')->nullable();

            $table->timestamps();

            $table->index('pallet');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wms_orden_trabajo_detalle');
    }
};