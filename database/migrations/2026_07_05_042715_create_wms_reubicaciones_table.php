<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wms_reubicaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('update_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('delete_id')->nullable()->constrained('users')->nullOnDelete();

            $table->enum('tipo', ['pallet_completo', 'completar_pallet']);
            $table->string('codigo', 30);
            $table->string('clote', 30)->nullable();
            $table->string('descrip', 60)->nullable();
            $table->string('descrip1', 60)->nullable();
            $table->unsignedInteger('cantidad');

            $table->string('pallet_origen', 30);
            $table->string('almacen_origen', 10);
            $table->string('galpon_origen', 20);
            $table->string('ubicacion_origen', 20);

            $table->string('pallet_destino', 30);
            $table->string('almacen_destino', 10);
            $table->string('galpon_destino', 20);
            $table->string('ubicacion_destino', 20);

            $table->string('observacion', 150)->nullable();

            $table->softDeletes();
            $table->timestamps();

            $table->index(['codigo', 'clote']);
            $table->index('pallet_origen');
            $table->index('pallet_destino');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wms_reubicaciones');
    }
};