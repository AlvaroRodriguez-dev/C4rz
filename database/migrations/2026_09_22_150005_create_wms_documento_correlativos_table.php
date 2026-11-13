<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wms_documento_correlativos', function (Blueprint $table) {
            $table->id();

            $table->foreignId('almacen_id')
                ->constrained('wms_almacenes')
                ->restrictOnDelete();

            $table->foreignId('id_tipo_registro')
                ->constrained('wms_tipo_documentos', 'id')
                ->restrictOnDelete();

            $table->string('talonario', 1);
            $table->unsignedSmallInteger('anio');
            $table->unsignedTinyInteger('mes');
            $table->unsignedInteger('ultimo_correlativo')->default(0);

            $table->timestamps();

            $table->unique(
                ['almacen_id', 'id_tipo_registro', 'talonario', 'anio', 'mes'],
                'wms_documento_correlativos_unico'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wms_documento_correlativos');
    }
};
