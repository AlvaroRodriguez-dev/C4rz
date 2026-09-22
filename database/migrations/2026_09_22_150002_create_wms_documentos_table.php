<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wms_documentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_tipo_registro')
                ->constrained('wms_tipo_documentos', 'id')
                ->restrictOnDelete();
            $table->foreignId('almacen_id')
                ->constrained('wms_almacenes')
                ->restrictOnDelete();

            $table->string('id_documento', 30)->unique();
            $table->string('agencia', 5);
            $table->string('agecodigo', 50)->nullable();
            $table->string('puntoVenta', 5)->nullable();
            $table->unsignedBigInteger('recibo')->nullable()->default(0);
            $table->integer('tipo_precio')->nullable()->default(0);
            $table->string('titulo');
            $table->string('inicial', 5);
            $table->string('database');
            $table->unsignedBigInteger('codigoSucursal')->nullable()->default(0);
            $table->unsignedBigInteger('codigoPuntoVenta')->nullable()->default(0);

            $table->unsignedBigInteger('created_id')->default(0);
            $table->unsignedBigInteger('updated_id')->default(0);
            $table->unsignedBigInteger('deleted_id')->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->string('tipo_documento')->nullable();

            $table->index(['almacen_id', 'id_tipo_registro']);
            $table->index(['agencia', 'agecodigo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wms_documentos');
    }
};